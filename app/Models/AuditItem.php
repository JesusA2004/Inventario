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

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function foundBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'found_branch_id');
    }

    public function foundDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'found_department_id');
    }

    public function foundResponsible(): BelongsTo
    {
        return $this->belongsTo(ResponsiblePerson::class, 'found_responsible_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
