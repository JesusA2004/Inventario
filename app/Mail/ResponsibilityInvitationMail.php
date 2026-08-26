<?php

namespace App\Mail;

use App\Models\AssetResponsibility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Se despacha en cola (requiere un worker corriendo, ver .env.example /
 * documentación de despliegue) para no bloquear la petición HTTP del
 * administrador que envía la invitación.
 */
class ResponsibilityInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly AssetResponsibility $responsibility,
        public readonly string $signingUrl,
    ) {}

    public function build(): self
    {
        $code = $this->responsibility->asset_snapshot['asset']['internal_code'] ?? $this->responsibility->asset->internal_code;

        return $this->subject("Responsiva de equipo asignado — {$code}")
            ->view('mail.responsibility-invitation')
            ->with([
                'responsibility' => $this->responsibility,
                'signingUrl' => $this->signingUrl,
                'expiresAt' => $this->responsibility->signing_token_expires_at,
            ]);
    }
}
