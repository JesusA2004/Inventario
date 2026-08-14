<?php

namespace App\Models;

use App\Enums\AssetFileType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AssetFile extends Model
{
    protected $fillable = [
        'asset_id',
        'type',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => AssetFileType::class,
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
