<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SyncDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'platform',
        'client_version',
        'token_hash',
        'is_active',
        'last_synced_at',
        'last_seen_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array{device:self, plain_token:string}
     */
    public static function issueForUser(User $user, string $name, string $platform = 'unknown', ?string $clientVersion = null): array
    {
        $plainToken = 'sync_' . Str::random(64);

        $device = self::query()->create([
            'user_id' => $user->id,
            'name' => trim($name) !== '' ? trim($name) : ('Poste ' . strtoupper($platform)),
            'platform' => strtolower(trim($platform)) ?: 'unknown',
            'client_version' => $clientVersion,
            'token_hash' => hash('sha256', $plainToken),
            'is_active' => true,
        ]);

        return [
            'device' => $device,
            'plain_token' => $plainToken,
        ];
    }
}
