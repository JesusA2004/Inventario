<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDocumentSetting extends Model
{
    protected $fillable = [
        'company_id',
        'legal_business_name',
        'issuing_city',
        'institutional_receiver_name',
        'institutional_receiver_position',
        'default_grantor_name',
        'default_grantor_position',
        'default_grantor_email',
        'letterhead_path',
        'header_logo_path',
        'watermark_path',
        'footer_path',
        'active_responsibility_template_id',
        'privacy_contact_email',
        'privacy_notice_url',
        'signing_link_valid_days',
        'auto_generate_on_assignment',
    ];

    protected function casts(): array
    {
        return [
            'auto_generate_on_assignment' => 'boolean',
            'signing_link_valid_days' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<ResponsibilityTemplate, $this>
     */
    public function activeResponsibilityTemplate(): BelongsTo
    {
        return $this->belongsTo(ResponsibilityTemplate::class, 'active_responsibility_template_id');
    }

    /**
     * Config singleton por empresa, igual patrón que SystemSetting::current():
     * se crea perezosamente con sus valores por defecto la primera vez que
     * se necesita, así nunca hay que "sembrar" manualmente una fila por cada
     * empresa nueva.
     */
    public static function forCompany(Company $company): self
    {
        return self::query()->firstOrCreate(
            ['company_id' => $company->id],
            [
                'legal_business_name' => $company->legal_name ?? $company->name,
                'signing_link_valid_days' => 7,
                'auto_generate_on_assignment' => true,
            ],
        );
    }

    /** Razón social a usar en responsivas, con fallback a la empresa. */
    public function resolvedLegalBusinessName(Company $company): string
    {
        return $this->legal_business_name ?: ($company->legal_name ?: $company->name);
    }
}
