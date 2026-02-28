<?php

namespace App\Services\Wpe;

use App\Models\Environment;
use App\Models\Server;
use App\Models\Site;
use App\Services\WpeApiClient;
use Carbon\Carbon;

class WpeInventorySyncService
{
    public function __construct(
        protected WpeApiClient $client
    ) {}

    /**
     * @param  callable(string): void|null  $progress
     */
    public function run(?callable $progress = null): WpeSyncResult
    {
        $start = microtime(true);
        $warnings = [];

        $say = function (string $message) use ($progress): void {
            if ($progress !== null) {
                $progress($message);
            }
        };

        $accountsCount = $this->syncServers($say);
        $sitesCount = $this->syncSitesAndEnvironments($say);
        $envCount = $this->syncEnvironmentDetails($say, $warnings);

        $duration = microtime(true) - $start;

        return new WpeSyncResult(
            accounts_synced_count: $accountsCount,
            sites_synced_count: $sitesCount,
            environments_synced_count: $envCount,
            duration_seconds: round($duration, 2),
            warnings: $warnings,
        );
    }

    /**
     * @param  callable(string): void  $say
     */
    protected function syncServers(callable $say): int
    {
        $say('Syncing servers (accounts)...');
        $seenIds = [];
        $offset = 0;
        $limit = 100;

        do {
            $data = $this->client->getAccounts($limit, $offset);
            foreach ($data['results'] ?? [] as $account) {
                $id = (string) ($account['id'] ?? '');
                if ($id === '') {
                    continue;
                }
                $seenIds[] = $id;
                Server::updateOrCreate(
                    ['wpe_account_id' => $id],
                    [
                        'name' => (string) ($account['name'] ?? ''),
                        'nickname' => (string) ($account['nickname'] ?? $account['name'] ?? ''),
                    ]
                );
            }
            $offset += $limit;
        } while (isset($data['next']) && $data['next'] !== null);

        $count = count($seenIds);
        $say('  Servers synced: ' . $count);
        return $count;
    }

    /**
     * @param  callable(string): void  $say
     */
    protected function syncSitesAndEnvironments(callable $say): int
    {
        $say('Syncing sites and environment stubs...');
        $seenSiteIds = [];
        $offset = 0;
        $limit = 100;

        do {
            $data = $this->client->getSites($limit, $offset);
            foreach ($data['results'] ?? [] as $sitePayload) {
                $wpeSiteId = (string) ($sitePayload['id'] ?? '');
                if ($wpeSiteId === '') {
                    continue;
                }
                $seenSiteIds[] = $wpeSiteId;

                $accountId = (string) ($sitePayload['account']['id'] ?? '');
                $server = Server::where('wpe_account_id', $accountId)->first();
                if (! $server) {
                    continue;
                }

                $site = Site::updateOrCreate(
                    ['wpe_site_id' => $wpeSiteId],
                    [
                        'server_id' => $server->id,
                        'name' => (string) ($sitePayload['name'] ?? ''),
                        'group_name' => $sitePayload['group_name'] ?? null,
                        'tags' => $sitePayload['tags'] ?? null,
                        'created_at_wpe' => isset($sitePayload['created_at'])
                            ? Carbon::parse($sitePayload['created_at'])
                            : null,
                        'sandbox' => (bool) ($sitePayload['sandbox'] ?? false),
                        'transferable' => (bool) ($sitePayload['transferable'] ?? false),
                        'lifecycle_status' => 'active',
                        'last_synced_at' => now(),
                    ]
                );

                $installStubs = $sitePayload['installs'] ?? [];
                foreach ($installStubs as $install) {
                    $wpeInstallId = (string) ($install['id'] ?? '');
                    if ($wpeInstallId === '') {
                        continue;
                    }
                    $envType = (string) ($install['environment'] ?? 'development');
                    $env = Environment::firstOrCreate(
                        ['wpe_install_id' => $wpeInstallId],
                        [
                            'site_id' => $site->id,
                            'name' => (string) ($install['name'] ?? ''),
                            'environment' => $envType,
                            'cname' => $install['cname'] ?? null,
                            'php_version' => $install['php_version'] ?? null,
                            'is_multisite' => (bool) ($install['is_multisite'] ?? false),
                            'status' => null,
                            'primary_domain' => null,
                            'wp_version' => null,
                            'stable_ips' => null,
                            'lifecycle_status' => 'active',
                            'notes' => null,
                            'update_method' => null,
                            'update_schedule' => null,
                            'updates_schedule_set' => false,
                            'last_synced_at' => now(),
                        ]
                    );
                    if ($env->wasRecentlyCreated === false) {
                        $env->update([
                            'site_id' => $site->id,
                            'name' => (string) ($install['name'] ?? ''),
                            'environment' => $envType,
                            'cname' => $install['cname'] ?? null,
                            'php_version' => $install['php_version'] ?? null,
                            'is_multisite' => (bool) ($install['is_multisite'] ?? false),
                            'last_synced_at' => now(),
                        ]);
                    }
                }
            }
            $offset += $limit;
            $say('  Sites page: ' . (int) ($offset / $limit) . ' (' . count($seenSiteIds) . ' sites so far)');
        } while (isset($data['next']) && $data['next'] !== null);

        $say('  Sites/environments synced: ' . count($seenSiteIds) . ' sites');

        if (! empty($seenSiteIds)) {
            $marked = Site::whereNotIn('wpe_site_id', $seenSiteIds)->update([
                'lifecycle_status' => 'unknown',
                'last_synced_at' => now(),
            ]);
            if ($marked > 0) {
                $say('  Marked ' . $marked . ' missing site(s) as lifecycle_status=unknown');
            }
        }

        return count($seenSiteIds);
    }

    /**
     * @param  callable(string): void  $say
     * @param  array<string>  $warnings
     */
    protected function syncEnvironmentDetails(callable $say, array &$warnings): int
    {
        $environments = Environment::all();
        $total = $environments->count();
        $say('Fetching install details (' . $total . ' environments)...');

        $n = 0;
        foreach ($environments as $env) {
            $n++;
            if ($n % 10 === 0 || $n === $total) {
                $say('  Install details ' . $n . '/' . $total);
            }
            try {
                $data = $this->client->getInstall($env->wpe_install_id);
            } catch (\Throwable $e) {
                $warnings[] = "Install {$env->wpe_install_id} ({$env->name}): " . $e->getMessage();
                continue;
            }
            $env->update([
                'status' => $data['status'] ?? null,
                'primary_domain' => $data['primary_domain'] ?? null,
                'wp_version' => $data['wp_version'] ?? null,
                'stable_ips' => $data['stable_ips'] ?? null,
                'cname' => $data['cname'] ?? $env->cname,
                'php_version' => $data['php_version'] ?? $env->php_version,
                'is_multisite' => (bool) ($data['is_multisite'] ?? $env->is_multisite),
                'last_detail_sync_at' => now(),
                'last_synced_at' => now(),
            ]);
        }
        $say('  Install details complete.');
        return $total;
    }
}
