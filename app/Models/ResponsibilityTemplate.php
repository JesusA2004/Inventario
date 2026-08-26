<?php

namespace App\Models;

use App\Enums\LegalReviewStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponsibilityTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'title',
        'document_title',
        'recipient_block',
        'receiver_label',
        'grantor_label',
        'version',
        'body',
        'active',
        'legal_review_status',
        'created_by',
    ];

    /**
     * Token reservado dentro de `body` que marca dónde va la tabla de
     * características del equipo (headers "Nombre de la característica" /
     * "Descripción detallada"). Nunca se resuelve como un placeholder de
     * datos: el renderer de PDF lo sustituye directamente por la tabla HTML,
     * ya que su contenido es editable por responsiva (ver
     * AssetResponsibilityService::updateCharacteristics()), no fijo en la
     * plantilla.
     */
    public const string CHARACTERISTICS_TABLE_TOKEN = '{{ characteristics_table }}';

    /**
     * Token reservado que marca un salto de página forzado justo antes del
     * párrafo que lo contiene: el formato real de MR LANA usa 2 páginas y no
     * se debe dejar que dompdf decida dónde partir el texto.
     */
    public const string PAGE_BREAK_TOKEN = '{{ page_break }}';

    public const string DEFAULT_RECEIVER_LABEL = 'RECEPTOR';

    public const string DEFAULT_GRANTOR_LABEL = 'OTORGANTE';

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'legal_review_status' => LegalReviewStatus::class,
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Solo una plantilla marcada explícitamente como "approved" puede usarse
     * para enviar una responsiva a firma digital (ver
     * AssetResponsibilityController::send()). Una plantilla en "draft" sigue
     * pudiendo generar el PDF de vista previa, pero con la banda de
     * "pendiente de validación jurídica".
     */
    public function canBeSentForSignature(): bool
    {
        return $this->legal_review_status === LegalReviewStatus::Approved;
    }

    /**
     * @param  Builder<ResponsibilityTemplate>  $query
     * @return Builder<ResponsibilityTemplate>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Plantilla activa aplicable a una empresa: la propia de la empresa si
     * existe, si no la global (company_id null). Prefiere una versión
     * "approved"; si ninguna está aprobada, cae en la última "draft" (nunca
     * deja de generar el documento, solo lo marca como pendiente de validar).
     */
    public static function resolveFor(?Company $company): ?self
    {
        $scoped = self::query()->active()->when(
            $company,
            fn ($query) => $query->where('company_id', $company->id),
            fn ($query) => $query->whereNull('company_id'),
        );

        $approved = (clone $scoped)->where('legal_review_status', LegalReviewStatus::Approved->value)
            ->orderByDesc('version')->first();

        if ($approved) {
            return $approved;
        }

        $companyDraft = (clone $scoped)->orderByDesc('version')->first();

        if ($companyDraft) {
            return $companyDraft;
        }

        // Sin plantilla propia de empresa: cae a la global.
        return self::query()->active()->whereNull('company_id')
            ->where('legal_review_status', LegalReviewStatus::Approved->value)
            ->orderByDesc('version')->first()
            ?? self::query()->active()->whereNull('company_id')->orderByDesc('version')->first();
    }
}
