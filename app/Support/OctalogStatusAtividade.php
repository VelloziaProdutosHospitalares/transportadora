<?php

namespace App\Support;

final class OctalogStatusAtividade
{
    /**
     * @param  array<string, mixed>  $row  Primeiro objeto retornado pela API (ex.: /pedido/salvar).
     */
    public static function labelFromResponseRow(array $row): ?string
    {
        foreach (['IDStatusAtividade', 'IDStatus', 'IdStatusAtividade'] as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $raw = $row[$key];
            if (! is_numeric($raw)) {
                continue;
            }
            $id = (int) $raw;

            return self::labelForId($id) ?? 'ID '.$id;
        }

        foreach (['Nome', 'Status', 'status'] as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $val = $row[$key];
            if (is_string($val) && trim($val) !== '') {
                return trim($val);
            }
        }

        return null;
    }

    public static function labelForId(int $id): ?string
    {
        $map = config('octalog.status_atividades', []);

        return $map[$id] ?? null;
    }
}
