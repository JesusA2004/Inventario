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
                            <p style="font-size:16px;color:#166534;margin:0 0 16px;font-weight:bold;">✓ Responsiva firmada correctamente</p>
                            <p style="font-size:14px;color:#1e293b;margin:0 0 16px;">Hola {{ $responsibility->receiver_name }}, gracias por confirmar la recepción de tu equipo.</p>
                            <table role="presentation" width="100%" style="background:#f8fafc;border-radius:8px;margin-bottom:20px;">
                                <tr><td style="padding:14px 16px;font-size:13px;color:#334155;">
                                    <strong>{{ $responsibility->asset_snapshot['asset']['name'] ?? '' }}</strong><br>
                                    Clave: {{ $responsibility->asset_snapshot['asset']['internal_code'] ?? '' }}<br>
                                    Documento: {{ $responsibility->document_number }}<br>
                                    Firmado: {{ $responsibility->signed_at?->format('d/m/Y H:i') }}
                                </td></tr>
                            </table>
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr><td style="border-radius:8px;background:#0f2a4a;">
                                    <a href="{{ $downloadUrl }}" style="display:inline-block;padding:12px 22px;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;">Descargar mi copia</a>
                                </td></tr>
                            </table>
                            <p style="font-size:12px;color:#64748b;margin:20px 0 0;">Este enlace de descarga es temporal por seguridad.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
