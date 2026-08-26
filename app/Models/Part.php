<?php

namespace App\Models;

use App\Enums\PartStatus;
use Database\Factories\PartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Part extends Model
{
    /** @use HasFactory<PartFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'internal_code',
        'related_asset_id',
        'name',
        'brand_id',
        'serial_number',
        'part_number',
        'status',
        'in_inventory',
        'quantity',
        'specifications',
        'assembled',
        'notes',
        'purchase_date',
        'decommissioned_at',
        'decommission_reason',
        'responsible_id',
        'invoice_url',
        'needs_label',
    ];

    protected function casts(): array
    {
        return [
            'status' => PartStatus::class,
            'in_inventory' => 'boolean',
            'assembled' => 'boolean',
            'needs_label' => 'boolean',
            'purchase_date' => 'date',
            'decommissioned_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $part): void {
            if (! $part->public_id) {
                $part->public_id = (string) Str::ulid();
            }
        });
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function relatedAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'related_asset_id');
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<ResponsiblePerson, $this>
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(ResponsiblePerson::class, 'responsible_id');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
