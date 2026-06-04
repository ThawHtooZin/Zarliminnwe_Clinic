<?php

namespace App\Domain\BackupRestore\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class BackupRestoreLogger
{
    public function log(string $action, array $context = []): void
    {
        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => 'backup_restore',
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => $context,
        ]);
    }
}
