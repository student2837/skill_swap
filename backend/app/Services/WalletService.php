<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WalletService
{
    public function debitCredits(int $userId, int $credits): void
    {
        if ($credits <= 0) {
            throw new RuntimeException('Credits must be positive');
        }

        DB::transaction(function () use ($userId, $credits) {
            /** @var User $user */
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            if (($user->credits ?? 0) < $credits) {
                throw new RuntimeException('Insufficient credits');
            }
            $user->credits = (int) $user->credits - $credits;
            $user->save();
        });
    }

    public function creditCredits(int $userId, int $credits): void
    {
        if ($credits <= 0) {
            throw new RuntimeException('Credits must be positive');
        }

        DB::transaction(function () use ($userId, $credits) {
            /** @var User $user */
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $user->credits = (int) ($user->credits ?? 0) + $credits;
            $user->save();
        });
    }
}

