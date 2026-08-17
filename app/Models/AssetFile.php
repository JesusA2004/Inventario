<?php

namespace App\Models;

use App\Enums\AssetFileType;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /**
     * The physical disk/path are implementation details: never serialize
     * them to the frontend, only the resolved `url` accessor below.
     */
    protected $hidden = [
        'path',
        'disk',
    ];

    protected $appends = [
        'url',
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

    /**
     * Photos live on the public disk (needed for thumbnails) and are served
     * directly. Facturas/documentos live on the private disk and can only be
     * reached through the authenticated download route.
     */
    protected function url(): Attribute
    {
        // El parámetro {asset} de la ruta se resuelve por public_id (ver
        // Asset::getRouteKeyName()), no por el id numérico: usar asset_id
        // aquí generaría una URL que nunca hace match y siempre da 404.
        return Attribute::get(fn () => $this->disk === 'public'
            ? Storage::disk('public')->url($this->path)
            : route('assets.files.download', ['asset' => $this->asset?->public_id, 'file' => $this->id]));
    }
}
