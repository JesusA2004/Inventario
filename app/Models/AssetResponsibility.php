<?php

namespace App\Models;

use App\Enums\AssetResponsibilityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Documento histórico de entrega de un activo a un responsable. Una vez
 * creada, NUNCA depende en tiempo real de Asset/ResponsiblePerson/Company:
 * todo lo que se muestra en el PDF vive congelado en asset_snapshot,
 * legal_text_snapshot, legal_template_version y privacy_notice_version.
 *
 * Campos sensibles (rutas de archivos, hash de token, IP, user agent) nunca
 * deben llegar al frontend por accidente: $hidden los protege incluso si
 * algún día se serializa el modelo directo en vez de pasar por
 * AssetResponsibilityResource.
 */
class AssetResponsibility extends Model
{
    protected $fillable = [
        'public_id',
        'document_number',
        'asset_id',
        'company_id',
        'responsible_person_id',
        'created_by',
        'status',
        'receiver_name',
        'receiver_position',
        'receiver_email',
        'grantor_name',
        'grantor_position',
        'grantor_email',
        'issued_at',
        'city',
        'asset_snapshot',
        'legal_text_snapshot',
        'legal_template_version',
        'privacy_notice_version',
        'unsigned_pdf_path',
        'signed_pdf_path',
        'physical_copy_path',
        'sent_at',
        'viewed_at',
        'signed_at',
        'physical_received_at',
        'physical_received_by',
        'signature_path',
        'signature_sha256',
        'document_sha256',
        'signer_ip',
        'signer_user_agent',
        'signing_token_hash',
        'signing_token_expires_at',
        'consent_accepted_at',
        'privacy_accepted_at',
    ];

    protected $hidden = [
        'unsigned_pdf_path',
        'signed_pdf_path',
        'physical_copy_path',
        'signature_path',
        'signing_token_hash',
        'signer_ip',
        'signer_user_agent',
    ];

    protected function casts(): array
    {
        return [
            'status' => AssetResponsibilityStatus::class,
            'asset_snapshot' => 'array',
            'issued_at' => 'date',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'signed_at' => 'datetime',
            'physical_received_at' => 'datetime',
            'signing_token_expires_at' => 'datetime',
            'consent_accepted_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $responsibility): void {
            if (! $responsibility->public_id) {
                $responsibility->public_id = (string) Str::ulid();
            }
        });
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
    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(ResponsiblePerson::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function physicalReceivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'physical_received_by');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function isDraft(): bool
    {
        return $this->status === AssetResponsibilityStatus::Borrador;
    }

    public function hasSignedPdf(): bool
    {
        return (bool) $this->signed_pdf_path;
    }

    public function hasPhysicalCopy(): bool
    {
        return (bool) $this->physical_copy_path;
    }

    /**
     * Código corto de verificación mostrado en la copia firmada, derivado
     * del hash de la firma (no del hash del PDF final: ese se calcula sobre
     * los bytes ya renderizados, así que no puede depender de un valor que
     * el propio PDF necesita imprimir). Sin columna extra.
     */
    public function verificationCode(): ?string
    {
        return $this->signature_sha256 ? mb_strtoupper(substr($this->signature_sha256, 0, 8)) : null;
    }
}
