<?php

namespace App\Services\Responsibilities;

use App\Enums\AssetResponsibilityStatus;
use App\Enums\MovementType;
use App\Models\Asset;
use App\Models\AssetResponsibility;
use App\Models\CompanyDocumentSetting;
use App\Models\PrivacyNoticeVersion;
use App\Models\ResponsibilityTemplate;
use App\Models\ResponsiblePerson;
use App\Models\User;
use App\Services\AssetMovementService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Crea y evoluciona el documento histórico de responsiva. Reutiliza
 * AssetMovementService para la bitácora del activo (no duplica esa lógica).
 */
class AssetResponsibilityService
{
    public function __construct(
        private readonly AssetCharacteristicsBuilder $characteristics,
        private readonly AssetMovementService $movements,
        private readonly TemplatePlaceholderResolver $placeholders,
    ) {}

    /**
     * @param  array{grantor_name?: ?string, grantor_position?: ?string, grantor_email?: ?string, issued_at?: ?string, city?: ?string, reason?: ?string}  $overrides
     */
    public function createDraft(Asset $asset, ResponsiblePerson $responsible, array $overrides, User $creator): AssetResponsibility
    {
        $asset->loadMissing(['company', 'assetType', 'brand']);
        $company = $asset->company;

        if (! $company) {
            // No debería ocurrir: company_id es obligatorio en la tabla de
            // activos. Se valida de todas formas para no asumir un estado
            // imposible silenciosamente.
            throw ValidationException::withMessages([
                'company' => 'El activo no tiene una empresa asignada.',
            ]);
        }

        $settings = CompanyDocumentSetting::forCompany($company);
        $template = ResponsibilityTemplate::resolveFor($company);

        if (! $template) {
            throw ValidationException::withMessages([
                'template' => 'No existe una plantilla legal configurada. Créala en Configuración → Documentos / Responsivas.',
            ]);
        }

        $privacyNotice = PrivacyNoticeVersion::current();

        if (! $privacyNotice) {
            throw ValidationException::withMessages([
                'privacy_notice' => 'No existe un aviso de privacidad activo configurado.',
            ]);
        }

        // Si nadie configuró un otorgante fijo para la empresa (típico en
        // negocios pequeños donde no hay "gerente" formal, solo la persona
        // de sistemas que entrega el equipo), quien genera la responsiva es
        // el otorgante por defecto: así el botón nunca se bloquea por falta
        // de configuración, y sigue reflejando quién entrega hoy en día en
        // vez de un nombre fijo que puede quedar obsoleto.
        $grantorName = $overrides['grantor_name'] ?? $settings->default_grantor_name ?? $creator->name;

        if (blank($grantorName)) {
            throw ValidationException::withMessages([
                'grantor_name' => 'No hay un otorgante configurado. Configúralo en Configuración → Documentos / Responsivas.',
            ]);
        }

        $grantorPosition = $overrides['grantor_position'] ?? $settings->default_grantor_position;
        $grantorEmail = $overrides['grantor_email'] ?? $settings->default_grantor_email ?? $creator->email;
        $issuedAt = $overrides['issued_at'] ?? now()->toDateString();
        $city = $overrides['city'] ?? $settings->issuing_city;

        // El cast de Eloquent garantiza que $asset->status siempre es una
        // instancia real de AssetStatus.
        $status = $asset->status;
        $legalName = $settings->resolvedLegalBusinessName($company);

        // Placeholders {{ }} disponibles en recipient_block y body. Los que
        // no tienen un dato estructurado real en el activo (procesador,
        // RAM, almacenamiento, sistema operativo: Asset no tiene esas
        // columnas) resuelven vacíos en vez de inventar un valor; la fila
        // correspondiente en la tabla de características sigue siendo
        // editable a mano por responsiva en el flujo ya existente.
        $placeholderData = [
            'receiver_name' => $responsible->full_name,
            'receiver_position' => $responsible->position ?? '',
            'company_name' => $legalName,
            'grantor_name' => $grantorName,
            'grantor_position' => $grantorPosition ?? '',
            'issued_city' => $city ?? '',
            'issued_date_long' => SpanishDateFormatter::long(Carbon::parse($issuedAt)),
            'asset_name' => $asset->name,
            'asset_brand' => optional($asset->brand)->name ?? '',
            'asset_model' => $asset->model ?? '',
            'asset_processor' => '',
            'asset_ram' => '',
            'asset_storage' => '',
            'asset_os' => '',
            'asset_condition' => $status->label(),
        ];

        // El documento queda congelado con el texto YA resuelto: cambiar la
        // plantilla o los datos del responsable después nunca debe alterar
        // una responsiva ya emitida (mismo principio que el resto del
        // snapshot). El token {{ characteristics_table }} no forma parte de
        // $placeholderData a propósito, así que el resolver lo deja intacto
        // para que el renderer de PDF lo sustituya por la tabla en su lugar.
        $recipientBlock = $this->placeholders->resolve($template->recipient_block, $placeholderData);
        $resolvedBody = $this->placeholders->resolve($template->body, $placeholderData);

        $snapshot = [
            'document_title' => $template->document_title ?: 'RESPONSIVA DE EQUIPO',
            'recipient_block' => $recipientBlock,
            'receiver_label' => $template->receiver_label ?: ResponsibilityTemplate::DEFAULT_RECEIVER_LABEL,
            'grantor_label' => $template->grantor_label ?: ResponsibilityTemplate::DEFAULT_GRANTOR_LABEL,
            'company' => [
                'name' => $company->name,
                'legal_name' => $legalName,
            ],
            'asset' => [
                'internal_code' => $asset->internal_code,
                'name' => $asset->name,
                'type' => $asset->assetType->name,
                'brand' => $asset->brand?->name,
                'model' => $asset->model,
                'serial_number' => $asset->serial_number,
                'status' => $status->label(),
            ],
            'characteristics' => $this->characteristics->build($asset),
            'legal_review_status' => $template->legal_review_status->value,
            'institutional_receiver_name' => $settings->institutional_receiver_name,
            'institutional_receiver_position' => $settings->institutional_receiver_position,
        ];

        $reason = $overrides['reason'] ?? null;

        return DB::transaction(function () use (
            $asset, $company, $responsible, $creator, $template, $privacyNotice,
            $grantorName, $grantorPosition, $grantorEmail, $issuedAt, $city, $snapshot, $reason, $resolvedBody,
        ) {
            $responsibility = AssetResponsibility::create([
                'asset_id' => $asset->id,
                'company_id' => $company->id,
                'responsible_person_id' => $responsible->id,
                'created_by' => $creator->id,
                'status' => AssetResponsibilityStatus::Borrador,
                'receiver_name' => $responsible->full_name,
                'receiver_position' => $responsible->position,
                'receiver_email' => $responsible->email,
                'grantor_name' => $grantorName,
                'grantor_position' => $grantorPosition,
                'grantor_email' => $grantorEmail,
                'issued_at' => $issuedAt,
                'city' => $city,
                'asset_snapshot' => $snapshot,
                'legal_text_snapshot' => $resolvedBody,
                'legal_template_version' => $template->version,
                'privacy_notice_version' => $privacyNotice->version,
            ]);

            $responsibility->forceFill([
                'document_number' => sprintf('RESP-%s-%05d', now()->year, $responsibility->id),
            ])->save();

            // El movimiento ya deja constancia de quién la generó (todo
            // registro de bitácora guarda el usuario autenticado); el
            // comentario agrega, cuando lo hay, el motivo de por qué se
            // generó -relevante sobre todo al volver a generar una
            // responsiva que ya existía para el mismo responsable.
            $comment = "Responsiva {$responsibility->document_number} generada para {$responsible->full_name}.";

            if (filled($reason)) {
                $comment .= " Motivo: {$reason}.";
            }

            $this->movements->log($asset, MovementType::Responsiva, comment: $comment);

            return $responsibility;
        });
    }

    /**
     * @param  array<int, array{name: string, value: string}>  $characteristics
     */
    public function updateCharacteristics(AssetResponsibility $responsibility, array $characteristics): AssetResponsibility
    {
        $this->assertDraft($responsibility);

        $snapshot = $responsibility->asset_snapshot ?? [];
        $snapshot['characteristics'] = array_values(array_map(
            fn (array $row) => ['name' => (string) $row['name'], 'value' => (string) $row['value']],
            $characteristics,
        ));

        $responsibility->update(['asset_snapshot' => $snapshot]);

        // El PDF sin firmar quedó cacheado con las características viejas:
        // se invalida para que se regenere en el próximo download/envío.
        if ($responsibility->unsigned_pdf_path) {
            Storage::disk('local')->delete($responsibility->unsigned_pdf_path);
            $responsibility->update(['unsigned_pdf_path' => null]);
        }

        return $responsibility;
    }

    public function assertDraft(AssetResponsibility $responsibility): void
    {
        if (! $responsibility->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Esta responsiva ya no está en borrador; su contenido ya no puede modificarse.',
            ]);
        }
    }

    public function cancel(AssetResponsibility $responsibility): void
    {
        if ($responsibility->status->isFinal()) {
            throw ValidationException::withMessages([
                'status' => 'Esta responsiva ya está en un estado final y no puede cancelarse.',
            ]);
        }

        $responsibility->update(['status' => AssetResponsibilityStatus::Cancelada]);
    }
}
