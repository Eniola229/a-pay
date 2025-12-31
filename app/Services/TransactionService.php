<?php
namespace App\Services;

use App\Models\Transaction;
use App\Models\Balance;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionService
{
    /**
     * Create a transaction and update balance atomically.
     *
     * @param \App\Models\User $user
     * @param float $amount
     * @param string $type 'DEBIT' or 'CREDIT'
     * @param string|null $beneficiary
     * @param string|null $description
     * @return \App\Models\Transaction
     * @throws \Exception
     */
    public function createTransaction($user, float $amount, string $type, ?string $beneficiary = null, ?string $description = null)
    {
        if (!in_array(strtoupper($type), ['DEBIT', 'CREDIT'])) {
            throw new Exception("Invalid transaction type: $type");
        }

        return DB::transaction(function () use ($user, $amount, $type, $beneficiary, $description) {
            // Fetch user balance and lock for update
            $balance = Balance::where('user_id', $user->id)->lockForUpdate()->first();
            
            if (!$balance) {
                throw new Exception("Wallet not found for user.");
            }

            // Record balance before transaction
            $balanceBefore = $balance->balance;
            $balanceAfter = $balanceBefore;

            // -----------------------
            // DEBIT: deduct balance
            // -----------------------
            if (strtoupper($type) === 'DEBIT') {
                if ($balanceBefore < $amount) {
                    throw new Exception("Insufficient balance");
                }

                $balance->decrementBalance($amount);
                $balanceAfter = $balanceBefore - $amount;
            }

            // -----------------------
            // CREDIT: add balance
            // -----------------------
            if (strtoupper($type) === 'CREDIT') {
                $balance->incrementBalance($amount);
                $balanceAfter = $balanceBefore + $amount;
            }

            // -----------------------
            // Create transaction record with balance snapshots
            // -----------------------
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'beneficiary' => $beneficiary,
                'description' => $description,
                'type' => strtoupper($type),
                'status' => 'PENDING',
                'source' => 'WHATSAPP',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            return $transaction;
        });
    }
}