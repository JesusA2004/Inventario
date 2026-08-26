<?php

namespace App\Http\Resources;

use App\Models\AssetResponsibility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AssetResponsibility
 *
 * Nunca expone paths de archivo ni el hash del token de firma. El bloque
 * "evidence" (IP, user agent, hashes) solo se agrega si quien pide el
 * recurso tiene permiso ver-evidencia-responsivas.
 */
class AssetResponsibilityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canSeeEvidence = (bool) $request->user()?->can('ver-evidencia-responsivas');
        $snapshot = $this->asset_snapshot ?? [];

        return [
            'id' => $this->id,
            'public_id' => $this->public_id,
            'document_number' => $this->document_number,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],

            'receiver_name' => $this->receiver_name,
            'receiver_position' => $this->receiver_position,
            'receiver_email' => $this->receiver_email,
            'grantor_name' => $this->grantor_name,
            'grantor_position' => $this->grantor_position,
            'grantor_email' => $this->grantor_email,

            'issued_at' => $this->issued_at->toDateString(),
            'city' => $this->city,
            'document_title' => $snapshot['document_title'] ?? null,
            'asset_snapshot' => [
                'asset' => $snapshot['asset'] ?? null,
                'company' => $snapshot['company'] ?? null,
                'characteristics' => $snapshot['characteristics'] ?? [],
                'legal_review_status' => $snapshot['legal_review_status'] ?? null,
            ],

            'has_unsigned_pdf' => (bool) $this->unsigned_pdf_path,
            'has_signed_pdf' => $this->hasSignedPdf(),
            'has_physical_copy' => $this->hasPhysicalCopy(),

            'sent_at' => $this->sent_at?->toISOString(),
            'viewed_at' => $this->viewed_at?->toISOString(),
            'signed_at' => $this->signed_at?->toISOString(),
            'physical_received_at' => $this->physical_received_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),

            'asset' => $this->whenLoaded('asset', fn () => $this->asset ? [
                'public_id' => $this->asset->public_id,
                'internal_code' => $this->asset->internal_code,
                'name' => $this->asset->name,
            ] : null),
            'company' => $this->whenLoaded('company', fn () => $this->company ? ['id' => $this->company->id, 'name' => $this->company->name] : null),
            'responsiblePerson' => $this->whenLoaded('responsiblePerson', fn () => $this->responsiblePerson ? [
                'id' => $this->responsiblePerson->id,
                'full_name' => $this->responsiblePerson->full_name,
                'email' => $this->responsiblePerson->email,
            ] : null),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? ['id' => $this->creator->id, 'name' => $this->creator->name] : null),
            'physicalReceivedBy' => $this->whenLoaded('physicalReceivedBy', fn () => $this->physicalReceivedBy ? ['name' => $this->physicalReceivedBy->name] : null),

            // Solo en la ficha detallada (evita mandar el clausulado completo en cada renglón del listado).
            'legal_text_snapshot' => $this->when($request->routeIs('responsibilities.show'), $this->legal_text_snapshot),

            'evidence' => $this->when($canSeeEvidence, fn () => [
                'signer_ip' => $this->signer_ip,
                'signer_user_agent' => $this->signer_user_agent,
                'signature_sha256' => $this->signature_sha256,
                'document_sha256' => $this->document_sha256,
                'legal_template_version' => $this->legal_template_version,
                'privacy_notice_version' => $this->privacy_notice_version,
                'consent_accepted_at' => $this->consent_accepted_at?->toISOString(),
                'privacy_accepted_at' => $this->privacy_accepted_at?->toISOString(),
                'verification_code' => $this->verificationCode(),
            ]),
        ];
    }
}
