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
     * @param string|null $reference Transaction reference/request_id
     * @param string|null $serviceType Service type for cashback calculation (e.g., 'AIRTIME', 'DATA')
     * @param float|null $charges Transaction fee/charges amount
     * @return \App\Models\Transaction
     * @throws \Exception
     */
    public function createTransaction($user, float $amount, string $type, ?string $beneficiary = null, ?string $description = null, ?string $reference = null, ?string $serviceType = null, ?float $charges = null)
    {
        if (!in_array(strtoupper($type), ['DEBIT', 'CREDIT'])) {
            throw new Exception("Invalid transaction type: $type");
        }
        
        return DB::transaction(function () use ($user, $amount, $type, $beneficiary, $description, $reference, $serviceType, $charges) {
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
            // Calculate cashback for eligible services
            // -----------------------
            $cashback = 0;
            if (strtoupper($type) === 'DEBIT' && in_array(strtoupper($serviceType), ['AIRTIME', 'DATA']) && class_exists(\App\Services\CashbackService::class)) {
                $cashback = app(\App\Services\CashbackService::class)->calculate($amount);
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
                'reference' => $reference,
                'cash_back' => $cashback,
                'charges' => $charges,
            ]);
            
            return $transaction;
        });
    }

    /**
     * Refund a failed transaction
     *
     * @param \App\Models\Transaction $transaction
     * @param \App\Models\Balance $balance
     * @param string $originalReference
     * @param string $beneficiary
     * @param string $description
     * @param string $refundReference
     * @return \App\Models\Transaction
     */ 
    public function refundTransaction($transaction, $balance, $originalReference, $beneficiary, $description, $refundReference)
    {
        return DB::transaction(function () use ($transaction, $balance, $originalReference, $beneficiary, $description, $refundReference) {
            
            $balance->lockForUpdate()->first();
            
            $balanceBefore = $balance->balance;
            $balance->incrementBalance($transaction->amount);
            $balanceAfter = $balanceBefore + $transaction->amount;
            
            $refundTransaction = Transaction::create([
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount,
                'beneficiary' => $beneficiary,
                'description' => $description,
                'type' => 'CREDIT',
                'status' => 'SUCCESS',
                'source' => 'WHATSAPP',
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference' => $refundReference,
            ]);
            
            $transaction->update([
                'status' => 'FAILED',
                'reference' => $originalReference
            ]);
            
            return $refundTransaction;
        });
    }

    /**
     * Mark transaction as successful
     *
     * @param \App\Models\Transaction $transaction
     * @param string $description
     * @param string $reference
     * @param string|null $beneficiary
     * @return void
     */
    public function markTransactionSuccess($transaction, $description, $reference, $beneficiary = null)
    {
        $transaction->update([
            'status' => 'SUCCESS',
            'description' => $description,
            'reference' => $reference,
            'beneficiary' => $beneficiary ?? $transaction->beneficiary,
        ]);
    }
}