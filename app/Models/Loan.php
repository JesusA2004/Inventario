<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    protected $fillable = [
        'asset_id',
        'company_id',
        'assigned_to_responsible_id',
        'delivered_by_responsible_id',
        'received_by_responsible_id',
        'reason',
        'loan_date',
        'expected_return_date',
        'delivered_confirmed',
        'received_confirmed',
        'actual_return_date',
        'status',
        'return_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoanStatus::class,
            'loan_date' => 'date',
            'expected_return_date' => 'date',
            'actual_return_date' => 'date',
            'delivered_confirmed' => 'boolean',
            'received_confirmed' => 'boolean',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(ResponsiblePerson::class, 'assigned_to_responsible_id');
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(ResponsiblePerson::class, 'delivered_by_responsible_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(ResponsiblePerson::class, 'received_by_responsible_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return $this->status === LoanStatus::Prestado
            && $this->expected_return_date !== null
            && $this->expected_return_date->isPast();
    }
}
