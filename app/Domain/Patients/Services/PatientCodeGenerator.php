<?php

namespace App\Domain\Patients\Services;

use App\Models\Patient;
use Illuminate\Support\Carbon;

class PatientCodeGenerator
{
    public function generate(?Carbon $date = null): string
    {
        $date = $date ?? now();
        $prefix = 'PAT-'.$date->format('Ymd').'-';

        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $sequence = str_pad((string) ($this->nextSequenceForDate($date) + $attempt), 4, '0', STR_PAD_LEFT);
            $code = $prefix.$sequence;

            if (! Patient::query()->where('patient_code', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.strtoupper(bin2hex(random_bytes(2)));
    }

    private function nextSequenceForDate(Carbon $date): int
    {
        $latestCode = Patient::query()
            ->where('patient_code', 'like', 'PAT-'.$date->format('Ymd').'-%')
            ->orderByDesc('patient_code')
            ->value('patient_code');

        if ($latestCode === null) {
            return 1;
        }

        $sequence = (int) substr($latestCode, -4);

        return $sequence + 1;
    }
}
