<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'internal_code',
        'asset_type_id',
        'name',
        'brand_id',
        'model',
        'serial_number',
        'status',
        'in_inventory',
        'current_responsible_id',
        'delivered_by_responsible_id',
        'components',
        'specifications',
        'notes',
        'invoice_url',
        'purchase_date',
        'acquired_at',
        'decommissioned_at',
        'decommission_reason',
        'decommission_notes',
        'last_reviewed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AssetStatus::class,
            'in_inventory' => 'boolean',
            'purchase_date' => 'date',
            'acquired_at' => 'date',
            'decommissioned_at' => 'date',
            'last_reviewed_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $asset): void {
            if (! $asset->public_id) {
                $asset->public_id = (string) Str::ulid();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function currentResponsible(): BelongsTo
    {
        return $this->belongsTo(ResponsiblePerson::class, 'current_responsible_id');
    }

    public function deliveredByResponsible(): BelongsTo
    {
        return $this->belongsTo(ResponsiblePerson::class, 'delivered_by_responsible_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(AssetFile::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class)->latest();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(AssetReview::class)->latest('reviewed_at');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class)->latest('loan_date');
    }

    public function parts(): HasMany
    {
        return $this->hasMany(Part::class, 'related_asset_id');
    }

    public function auditItems(): HasMany
    {
        return $this->hasMany(AuditItem::class);
    }

    public function activeLoan(): ?Loan
    {
        return $this->loans()->where('status', LoanStatus::Prestado)->first();
    }

    public function scopeInInventory($query)
    {
        return $query->where('in_inventory', true);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
