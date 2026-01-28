<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class WalletService
{
    protected function supportsLockedCredits(): bool
    {
        static $supported = null;
        if ($supported === null) {
            $supported = Schema::hasColumn('users', 'locked_credits');
        }
        return $supported;
    }

    public function lockCredits(int $userId, int $credits): void
    {
        if ($credits <= 0) {
            throw new RuntimeException('Credits must be positive');
        }

        if (!$this->supportsLockedCredits()) {
            // Fallback for legacy schema: deduct immediately.
            $this->debitCredits($userId, $credits);
            return;
        }

        DB::transaction(function () use ($userId, $credits) {
            /** @var User $user */
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $available = (int) ($user->credits ?? 0) - (int) ($user->locked_credits ?? 0);
            if ($available < $credits) {
                throw new RuntimeException('Insufficient credits');
            }
            $user->locked_credits = (int) ($user->locked_credits ?? 0) + $credits;
            $user->save();
        });
    }

    public function unlockCredits(int $userId, int $credits): void
    {
        if ($credits <= 0) {
            throw new RuntimeException('Credits must be positive');
        }

        if (!$this->supportsLockedCredits()) {
            // Fallback for legacy schema: refund immediately.
            $this->creditCredits($userId, $credits);
            return;
        }

        DB::transaction(function () use ($userId, $credits) {
            /** @var User $user */
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $locked = (int) ($user->locked_credits ?? 0);
            if ($locked < $credits) {
                throw new RuntimeException('Locked credits insufficient');
            }
            $user->locked_credits = $locked - $credits;
            $user->save();
        });
    }

    public function consumeLockedCredits(int $userId, int $credits): void
    {
        if ($credits <= 0) {
            throw new RuntimeException('Credits must be positive');
        }

        if (!$this->supportsLockedCredits()) {
            // Legacy schema already deducted at request time.
            return;
        }

        DB::transaction(function () use ($userId, $credits) {
            /** @var User $user */
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $locked = (int) ($user->locked_credits ?? 0);
            if ($locked < $credits) {
                throw new RuntimeException('Locked credits insufficient');
            }
            $user->locked_credits = $locked - $credits;
            $user->credits = (int) ($user->credits ?? 0) - $credits;
            $user->save();
        });
    }

    public function debitCredits(int $userId, int $credits): void
    {
        if ($credits <= 0) {
            throw new RuntimeException('Credits must be positive');
        }

        DB::transaction(function () use ($userId, $credits) {
            /** @var User $user */
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $available = (int) ($user->credits ?? 0) - (int) ($user->locked_credits ?? 0);
            if ($available < $credits) {
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

