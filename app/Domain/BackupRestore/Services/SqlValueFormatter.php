<?php

namespace App\Domain\BackupRestore\Services;

class SqlValueFormatter
{
    public function quote(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'".str_replace("'", "''", (string) $value)."'";
    }
}
