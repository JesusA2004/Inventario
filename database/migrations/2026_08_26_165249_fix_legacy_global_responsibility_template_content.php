<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrige en sitio la plantilla global sembrada ANTES de que existiera este
 * módulo de formato exacto por empresa: aquella versión reescribía/resumía
 * el clausulado real (incluida una cita errónea al "artículo 132" de la Ley
 * Federal del Trabajo, cuando el documento real de la empresa cita el
 * artículo 32, fracción III) y usaba encabezados inventados ("RESPONSIVA DE
 * EQUIPO DE TI", "Destinatario / Empresa"). Nunca se toca una responsiva ya
 * emitida (vive de su propio snapshot congelado, ajeno a esta tabla); solo
 * se corrige el texto maestro de la plantilla todavía sin document_title
 * (marca inequívoca de que es la fila sembrada por la versión vieja del
 * seeder, antes de que ese campo existiera) y que sigue en "draft" -si ya
 * fue aprobada o personalizada a mano, se deja intacta-.
 */
return new class extends Migration
{
    public function up(): void
    {
        $body = implode("\n\n", [
            'Yo, {{ receiver_name }}, en mi calidad de receptor, con base en el contrato individual de trabajo, ante usted manifiesto que tengo pleno conocimiento de lo siguiente:',
            'Que el patrón es propietario del equipo de cómputo que se describe a continuación, mismo que está destinado para cumplir con las actividades de su objeto social:',
            '{{ characteristics_table }}',
            'Que el patrón, de conformidad con lo establecido por el artículo 32, fracción III de la Ley Federal del Trabajo, proporciona al suscrito el equipo descrito, por lo que este acto se entrega en buen estado, con las condiciones descritas en la presente responsiva.',
            'Que el suscrito recibe el equipo de cómputo y se obliga en los términos del artículo 134, fracción VI de la Ley Federal del Trabajo, a conservarlo en buen estado y a devolverlo al patrón cuando ésta así lo requiera o cuando se suspenda o se dé por terminada la relación de trabajo.',
            'Que el equipo proporcionado al suscrito es para el uso exclusivo de mi trabajo y no podré prestarlo a terceros, ni utilizarlo para asuntos personales.',
            'Que, para el correcto uso y el mantenimiento del equipo de cómputo, el suscrito me comprometo a seguir las instrucciones del patrón.',
            'Que está estrictamente prohibido al suscrito modificar o alterar el estado y forma del equipo de cómputo, a menos que haya autorización expresa y por escrito del patrón.',
            'Que el patrón cubrirá los servicios del equipo de cómputo.',
            'Que el suscrito será responsable de los daños o averías del equipo de cómputo, de la perdida completa de este, de su robo; derivado del uso indebido, error o negligencia. Para ello, deberé pagar la reparación, la sustitución o cualquier otro pago que deba cubrir el patrón, derivado de mi conducta.',
            'Debido a ello, el suscrito desde este momento autorizo al patrón para que se realicen los descuentos de mi nómina, en los términos de la Ley Federal del Trabajo.',
            'Que cuando por la naturaleza del trabajo que realice el suscrito se vuelva innecesaria el uso del equipo de cómputo, o bien, el patrón establezca diferentes políticas de uso y designación, o a juicio del patrón deba retirarse la designación de manera definitiva, el suscrito deberá entregar el equipo de cómputo.',
            'Que en caso de que el suscrito dejare de prestar mis servicios al patrón, devolveré el equipo de cómputo y cubriré las cantidades que adeudare con motivo de las obligaciones contraídas por la presente responsiva, mismas que podrán ser descontadas del finiquito definitivo que me entregue el patrón, por lo que desde este momento otorgo mi consentimiento para tal efecto.',
        ]);

        DB::table('responsibility_templates')
            ->whereNull('company_id')
            ->where('name', 'default')
            ->whereNull('document_title')
            ->where('legal_review_status', 'draft')
            ->update([
                'title' => 'Responsiva de equipo de cómputo — plantilla base',
                'document_title' => 'RESPONSIVA DE EQUIPO DE CÓMPUTO',
                'recipient_block' => "A QUIEN CORRESPONDA\n{{ company_name }}\nPRESENTE.",
                'body' => $body,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Corrección de contenido, no de esquema: no hay un "antes" al que
        // valga la pena volver.
    }
};
