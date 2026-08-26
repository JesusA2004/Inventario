<?php

namespace App\Services\Responsibilities;

/**
 * Sustituye placeholders {{ variable }} dentro del texto maestro de una
 * plantilla (recipient_block / body) por datos reales de la responsiva que
 * se está generando. Solo reemplaza las llaves que reconoce en $data: todo
 * lo demás del texto -incluida cualquier llave que no reconozca, como el
 * token reservado {{ characteristics_table }}- se deja exactamente como
 * está, sin reescribir ni una palabra del resto del documento.
 */
class TemplatePlaceholderResolver
{
    /**
     * @param  array<string, string>  $data
     */
    public function resolve(?string $text, array $data): string
    {
        if (blank($text)) {
            return '';
        }

        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            fn (array $match) => array_key_exists($match[1], $data) ? $data[$match[1]] : $match[0],
            $text,
        ) ?? $text;
    }
}
