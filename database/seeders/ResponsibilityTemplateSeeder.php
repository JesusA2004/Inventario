<?php

namespace Database\Seeders;

use App\Enums\LegalReviewStatus;
use App\Models\Company;
use App\Models\ResponsibilityTemplate;
use Illuminate\Database\Seeder;

class ResponsibilityTemplateSeeder extends Seeder
{
    /**
     * Texto EXACTO tal como lo entregó el cliente (formato real de MR LANA,
     * marzo 2026): ningún párrafo aquí fue reescrito, resumido ni
     * "corregido" -incluida la cita al artículo 32, fracción III de la Ley
     * Federal del Trabajo, tal cual aparece en el documento original-. El
     * único cambio permitido es sustituir el nombre de la empresa por el
     * placeholder {{ company_name }} en la versión GLOBAL (no en la de MR
     * LANA, que usa el nombre literal) para que sirva de punto de partida a
     * cualquier empresa nueva. {{ characteristics_table }} y {{ page_break }}
     * son tokens reservados (tabla de características y salto de página);
     * el renderer los sustituye, no se resuelven como placeholders de datos.
     *
     * @var array<int, string>
     */
    private const array BODY_PARAGRAPHS = [
        'Yo, {{ receiver_name }}, en mi calidad de receptor, con base en el contrato individual de trabajo, ante usted manifiesto que tengo pleno conocimiento de lo siguiente:',
        'Que el patrón es propietario del equipo de cómputo que se describe a continuación, mismo que está destinado para cumplir con las actividades de su objeto social:',
        '{{ characteristics_table }}',
        'Que el patrón, de conformidad con lo establecido por el artículo 32, fracción III de la Ley Federal del Trabajo, proporciona al suscrito el equipo descrito, por lo que este acto se entrega en buen estado, con las condiciones descritas en la presente responsiva.',
        'Que el suscrito recibe el equipo de cómputo y se obliga en los términos del artículo 134, fracción VI de la Ley Federal del Trabajo, a conservarlo en buen estado y a devolverlo al patrón cuando ésta así lo requiera o cuando se suspenda o se dé por terminada la relación de trabajo.',
        'Que el equipo proporcionado al suscrito es para el uso exclusivo de mi trabajo y no podré prestarlo a terceros, ni utilizarlo para asuntos personales.',
        'Que, para el correcto uso y el mantenimiento del equipo de cómputo, el suscrito me comprometo a seguir las instrucciones del patrón.',
        'Que está estrictamente prohibido al suscrito modificar o alterar el estado y forma del equipo de cómputo, a menos que haya autorización expresa y por escrito del patrón.',
        // El formato real de MR LANA ocupa 2 páginas: la primera termina
        // justo después del párrafo anterior. Salto de página explícito en
        // vez de dejar que dompdf decida dónde partir el texto.
        '{{ page_break }}',
        'Que el patrón cubrirá los servicios del equipo de cómputo.',
        'Que el suscrito será responsable de los daños o averías del equipo de cómputo, de la perdida completa de este, de su robo; derivado del uso indebido, error o negligencia. Para ello, deberé pagar la reparación, la sustitución o cualquier otro pago que deba cubrir el patrón, derivado de mi conducta.',
        'Debido a ello, el suscrito desde este momento autorizo al patrón para que se realicen los descuentos de mi nómina, en los términos de la Ley Federal del Trabajo.',
        'Que cuando por la naturaleza del trabajo que realice el suscrito se vuelva innecesaria el uso del equipo de cómputo, o bien, el patrón establezca diferentes políticas de uso y designación, o a juicio del patrón deba retirarse la designación de manera definitiva, el suscrito deberá entregar el equipo de cómputo.',
        'Que en caso de que el suscrito dejare de prestar mis servicios al patrón, devolveré el equipo de cómputo y cubriré las cantidades que adeudare con motivo de las obligaciones contraídas por la presente responsiva, mismas que podrán ser descontadas del finiquito definitivo que me entregue el patrón, por lo que desde este momento otorgo mi consentimiento para tal efecto.',
    ];

    public function run(): void
    {
        $this->seedGlobalDefault();
        $this->seedMrLana();
    }

    /**
     * Plantilla global (company_id null): fallback para cualquier empresa
     * sin plantilla propia. Nace en "draft": sigue requiriendo que alguien
     * con criterio jurídico la revise y apruebe antes de poder usarse para
     * firma digital, para esa empresa en particular.
     */
    private function seedGlobalDefault(): void
    {
        if (ResponsibilityTemplate::query()->whereNull('company_id')->where('name', 'default')->exists()) {
            return;
        }

        ResponsibilityTemplate::create([
            'company_id' => null,
            'name' => 'default',
            'title' => 'Responsiva de equipo de cómputo — plantilla base',
            'document_title' => 'RESPONSIVA DE EQUIPO DE CÓMPUTO',
            'recipient_block' => "A QUIEN CORRESPONDA\n{{ company_name }}\nPRESENTE.",
            'version' => 1,
            'active' => true,
            'legal_review_status' => LegalReviewStatus::Draft,
            'created_by' => null,
            'body' => implode("\n\n", self::BODY_PARAGRAPHS),
        ]);
    }

    /**
     * Plantilla propia de MR LANA con el nombre real de la empresa en el
     * bloque de destinatario, tal cual el formato oficial. Solo se crea si
     * la empresa ya existe (CompaniesSeeder corre antes en DatabaseSeeder).
     */
    private function seedMrLana(): void
    {
        $mrLana = Company::query()->where('code', 'CML')->first();

        if (! $mrLana) {
            return;
        }

        if (ResponsibilityTemplate::query()->where('company_id', $mrLana->id)->where('name', 'default')->exists()) {
            return;
        }

        ResponsibilityTemplate::create([
            'company_id' => $mrLana->id,
            'name' => 'default',
            'title' => 'Responsiva de equipo de cómputo — MR LANA',
            'document_title' => 'RESPONSIVA DE EQUIPO DE CÓMPUTO',
            'recipient_block' => "A QUIEN CORRESPONDA\nPRODUCTOS Y SERVICIOS MR LANA SAPI DE C.V.\nPRESENTE.",
            'version' => 1,
            'active' => true,
            'legal_review_status' => LegalReviewStatus::Draft,
            'created_by' => null,
            'body' => implode("\n\n", self::BODY_PARAGRAPHS),
        ]);
    }
}
