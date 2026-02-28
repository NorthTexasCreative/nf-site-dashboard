<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WpeApiClient
{
    public function __construct(
        protected string $baseUrl,
        protected string $user,
        protected string $password
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            config('wpengine.base_url'),
            config('wpengine.user', ''),
            config('wpengine.password', '')
        );
    }

    /**
     * @return array{previous: ?string, next: ?string, count: int, results: array<int, array>}
     */
    public function getAccounts(int $limit = 100, int $offset = 0): array
    {
        $response = $this->request('GET', '/accounts', [
            'limit' => $limit,
            'offset' => $offset,
        ]);
        return $response;
    }

    /**
     * @return array{previous: ?string, next: ?string, count: int, results: array<int, array>}
     */
    public function getSites(int $limit = 100, int $offset = 0, ?string $accountId = null): array
    {
        $query = ['limit' => $limit, 'offset' => $offset];
        if ($accountId !== null) {
            $query['account_id'] = $accountId;
        }
        return $this->request('GET', '/sites', $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function getInstall(string $installId): array
    {
        return $this->request('GET', "/installs/{$installId}");
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    protected function request(string $method, string $path, array $query = []): array
    {
        $url = rtrim($this->baseUrl, '/') . $path;
        $response = Http::withBasicAuth($this->user, $this->password)
            ->acceptJson()
            ->get($url, $query);

        $response->throw();
        return $response->json();
    }
}
