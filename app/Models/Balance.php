<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;

class Balance extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'balances';
    protected $keyType = 'uuid';
    public $incrementing = false;
    protected $primaryKey = 'id';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) { // Fix: Remove type hint or use `User`
            if (empty($model->id)) {
                $model->id = Uuid::uuid4()->toString();
            }
        });
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'pin',
        'owe',
    ];

    public function updateBalance($amount)
    {
        DB::statement('SET @allow_balance_update = 1');
        
        $this->balance += $amount;
        $this->save();
        
        DB::statement('SET @allow_balance_update = NULL');
        
        return $this;
    }

    public function incrementBalance($amount)
    {
        DB::statement('SET @allow_balance_update = 1');
        
        $this->increment('balance', $amount);
        
        DB::statement('SET @allow_balance_update = NULL');
        
        return $this;
    }

    public function decrementBalance($amount)
    {
        DB::statement('SET @allow_balance_update = 1');
        
        $this->decrement('balance', $amount);
        
        DB::statement('SET @allow_balance_update = NULL');
        
        return $this;
    }
}