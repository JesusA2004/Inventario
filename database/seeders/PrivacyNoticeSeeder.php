<?php

namespace Database\Seeders;

use App\Models\PrivacyNoticeVersion;
use Illuminate\Database\Seeder;

class PrivacyNoticeSeeder extends Seeder
{
    /**
     * Aviso de privacidad inicial (versión 1), conforme a la Ley Federal de
     * Protección de Datos Personales en Posesión de los Particulares:
     * identidad del responsable, datos tratados, finalidades limitadas al
     * control de entrega de activos (sin fines mercadotécnicos), mecanismos
     * ARCO y medio de contacto. Editable desde Configuración → Documentos /
     * Responsivas sin necesidad de tocar código.
     */
    public function run(): void
    {
        if (PrivacyNoticeVersion::query()->where('version', 1)->exists()) {
            return;
        }

        PrivacyNoticeVersion::create([
            'version' => 1,
            'active' => true,
            'created_by' => null,
            'body' => <<<'TEXT'
            AVISO DE PRIVACIDAD

            En cumplimiento de la Ley Federal de Protección de Datos Personales en Posesión de los Particulares, se hace de su conocimiento el presente aviso de privacidad.

            Responsable del tratamiento de sus datos personales: MR LANA, a través de su área de Sistemas, con domicilio configurable por el administrador del sistema en esta misma sección.

            Datos personales tratados: nombre completo, puesto, correo electrónico, la asignación del activo entregado, la firma electrónica capturada al aceptar este documento, así como la fecha, hora y metadatos técnicos asociados a dicha firma (dirección IP y navegador utilizado).

            Finalidades del tratamiento: (i) dejar constancia y control de la entrega y devolución de activos de la organización; (ii) administración del inventario de equipo; (iii) cumplimiento de obligaciones laborales y administrativas relacionadas con dicha entrega; (iv) auditoría y seguridad de la información; y (v) contacto relacionado con la entrega, resguardo o devolución del equipo. Sus datos no se utilizan con fines mercadotécnicos, publicitarios o de prospección comercial.

            Sus datos personales no serán transferidos a terceros salvo que exista una obligación legal para ello.

            Derechos ARCO: usted tiene derecho a acceder, rectificar y cancelar sus datos personales, así como a oponerse al tratamiento de los mismos o a revocar el consentimiento otorgado. Para ejercer estos derechos, puede dirigirse al correo de contacto configurado por el administrador del sistema en Configuración → Documentos / Responsivas.

            Cambios al aviso de privacidad: cualquier modificación a este aviso será publicada en esta misma página, indicando el número de versión vigente. La versión aceptada por usted al momento de firmar una responsiva queda registrada de forma permanente en dicho documento.
            TEXT,
        ]);
    }
}
