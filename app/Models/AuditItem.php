<?php

namespace App\Models;

use App\Enums\AuditItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditItem extends Model
{
    protected $fillable = [
        'audit_id',
        'asset_id',
        'status',
        'found_branch_id',
        'found_department_id',
        'found_responsible_id',
        'comment',
        'checked_at',
        'checked_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => AuditItemStatus::class,
            'checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Audit, $this>
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function foundBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'found_branch_id');
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function foundDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'found_department_id');
    }

    /**
     * @return BelongsTo<ResponsiblePerson, $this>
     */
    public function foundResponsible(): BelongsTo
    {
        return $this->belongsTo(ResponsiblePerson::class, 'found_responsible_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
