<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetReview extends Model
{
    protected $fillable = [
        'asset_id',
        'user_id',
        'reviewed_at',
        'physical_status',
        'location_ok',
        'responsible_ok',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'date',
            'location_ok' => 'boolean',
            'responsible_ok' => 'boolean',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
