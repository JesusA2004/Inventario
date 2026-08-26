<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyNoticeVersion extends Model
{
    protected $fillable = [
        'version',
        'body',
        'active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function current(): ?self
    {
        return self::query()->where('active', true)->orderByDesc('version')->first();
    }
}
