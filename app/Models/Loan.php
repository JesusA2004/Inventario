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

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<ResponsiblePerson, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(ResponsiblePerson::class, 'assigned_to_responsible_id');
    }

    /**
     * @return BelongsTo<ResponsiblePerson, $this>
     */
    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(ResponsiblePerson::class, 'delivered_by_responsible_id');
    }

    /**
     * @return BelongsTo<ResponsiblePerson, $this>
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(ResponsiblePerson::class, 'received_by_responsible_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
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
