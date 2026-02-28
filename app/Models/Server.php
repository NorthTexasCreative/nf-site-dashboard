<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    protected $fillable = [
        'wpe_account_id',
        'name',
        'nickname',
    ];

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }
}
