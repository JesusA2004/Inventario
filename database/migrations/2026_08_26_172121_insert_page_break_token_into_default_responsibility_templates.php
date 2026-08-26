<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Inserta el token reservado {{ page_break }} en las plantillas "default"
 * sembradas por ResponsibilityTemplateSeeder antes de que ese token
 * existiera, justo donde el formato real de MR LANA parte a la página 2 (
 * después de "...autorización expresa y por escrito del patrón." y antes de
 * "Que el patrón cubrirá los servicios del equipo de cómputo."). Nunca toca
 * responsivas ya emitidas (viven de su propio snapshot); solo actualiza la
 * plantilla "default" mientras siga en draft y sin el token ya insertado,
 * para no duplicarlo si esta migración corre más de una vez o si alguien ya
 * lo agregó a mano desde el panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        $needle = 'a menos que haya autorización expresa y por escrito del patrón.';
        $replacement = $needle."\n\n{{ page_break }}";

        DB::table('responsibility_templates')
            ->where('name', 'default')
            ->where('legal_review_status', 'draft')
            ->where('body', 'like', '%'.$needle.'%')
            ->where('body', 'not like', '%{{ page_break }}%')
            ->orderBy('id')
            ->get(['id', 'body'])
            ->each(function (object $row) use ($needle, $replacement): void {
                DB::table('responsibility_templates')
                    ->where('id', $row->id)
                    ->update([
                        'body' => str_replace($needle, $replacement, $row->body),
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('responsibility_templates')
            ->where('body', 'like', '%{{ page_break }}%')
            ->get(['id', 'body'])
            ->each(function (object $row): void {
                DB::table('responsibility_templates')
                    ->where('id', $row->id)
                    ->update([
                        'body' => str_replace("\n\n{{ page_break }}", '', $row->body),
                        'updated_at' => now(),
                    ]);
            });
    }
};
