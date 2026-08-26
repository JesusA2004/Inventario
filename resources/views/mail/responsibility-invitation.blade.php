<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:520px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#0f2a4a;padding:20px 28px;">
                            <span style="color:#ffffff;font-size:15px;font-weight:bold;letter-spacing:0.3px;">MR LANA · Inventario</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="font-size:14px;color:#1e293b;margin:0 0 16px;">Hola {{ $responsibility->receiver_name }},</p>
                            <p style="font-size:14px;color:#1e293b;margin:0 0 16px;">Se te ha asignado el siguiente equipo:</p>
                            <table role="presentation" width="100%" style="background:#f8fafc;border-radius:8px;margin-bottom:20px;">
                                <tr><td style="padding:14px 16px;font-size:13px;color:#334155;">
                                    <strong>{{ $responsibility->asset_snapshot['asset']['name'] ?? '' }}</strong><br>
                                    Clave: {{ $responsibility->asset_snapshot['asset']['internal_code'] ?? '' }}<br>
                                    @if(!empty($responsibility->asset_snapshot['asset']['brand']) || !empty($responsibility->asset_snapshot['asset']['model']))
                                        Marca/Modelo: {{ trim(($responsibility->asset_snapshot['asset']['brand'] ?? '').' '.($responsibility->asset_snapshot['asset']['model'] ?? '')) }}
                                    @endif
                                </td></tr>
                            </table>
                            <p style="font-size:14px;color:#1e293b;margin:0 0 20px;">Para concluir la entrega, revisa y firma tu responsiva de equipo.</p>
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr><td style="border-radius:8px;background:#0f2a4a;">
                                    <a href="{{ $signingUrl }}" style="display:inline-block;padding:12px 22px;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;">Revisar y firmar responsiva</a>
                                </td></tr>
                            </table>
                            <p style="font-size:12px;color:#64748b;margin:20px 0 0;">Este enlace vence el {{ $expiresAt?->format('d/m/Y') }}.</p>
                            <p style="font-size:12px;color:#64748b;margin:8px 0 0;">Si no reconoces esta asignación, comunícate con el área de Sistemas.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
