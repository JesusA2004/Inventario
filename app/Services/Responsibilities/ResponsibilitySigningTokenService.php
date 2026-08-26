<?php

namespace App\Services\Responsibilities;

use App\Models\AssetResponsibility;
use Illuminate\Support\Str;

/**
 * El token en claro SOLO existe en el correo enviado al firmante; en base de
 * datos únicamente se guarda su hash SHA-256 (signing_token_hash). Nunca se
 * loguea ni se regresa el token en claro desde ningún endpoint autenticado.
 */
class ResponsibilitySigningTokenService
{
    public function issue(AssetResponsibility $responsibility, int $validDays): string
    {
        $token = Str::random(64);

        $responsibility->update([
            'signing_token_hash' => hash('sha256', $token),
            'signing_token_expires_at' => now()->addDays(max(1, $validDays)),
        ]);

        return $token;
    }

    public function invalidate(AssetResponsibility $responsibility): void
    {
        $responsibility->update([
            'signing_token_hash' => null,
            'signing_token_expires_at' => null,
        ]);
    }

    public function verify(AssetResponsibility $responsibility, ?string $token): bool
    {
        if (! $token || ! $responsibility->signing_token_hash) {
            return false;
        }

        if ($responsibility->signing_token_expires_at !== null && $responsibility->signing_token_expires_at->isPast()) {
            return false;
        }

        return hash_equals($responsibility->signing_token_hash, hash('sha256', $token));
    }

    public function isExpired(AssetResponsibility $responsibility): bool
    {
        return $responsibility->signing_token_expires_at !== null && $responsibility->signing_token_expires_at->isPast();
    }
}
