<?php

namespace App\Mail;

use App\Models\AssetResponsibility;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResponsibilitySignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly AssetResponsibility $responsibility,
        public readonly string $downloadUrl,
    ) {}

    public function build(): self
    {
        return $this->subject('Responsiva firmada correctamente')
            ->view('mail.responsibility-signed')
            ->with([
                'responsibility' => $this->responsibility,
                'downloadUrl' => $this->downloadUrl,
            ]);
    }
}
