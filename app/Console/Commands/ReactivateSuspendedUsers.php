<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ReactivateSuspendedUsers extends Command
{
    protected $signature = 'users:reactivate-suspended';

    protected $description = 'Reativa contas com uma suspensão temporária expirada';

    public function handle(): int
    {
        $count = User::query()
            ->where('account_status', 'suspended')
            ->whereNotNull('suspended_until')
            ->where('suspended_until', '<=', now())
            ->update([
                'account_status' => 'active',
                'suspended_until' => null,
                'suspension_reason' => null,
                'updated_at' => now(),
            ]);

        $this->info("Reactivated {$count} suspended account(s).");

        return self::SUCCESS;
    }
}
