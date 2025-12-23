<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Ramsey\Uuid\Uuid;

class Transaction extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'transactions';
    protected $keyType = 'uuid';
    public $incrementing = false;
    protected $primaryKey = 'id';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) { 
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
        'amount',
        'cash_back',
        'charges',
        'beneficiary',
        'description',
        'type',
        'status',
        'reference',
        'balance_before',
        'balance_after'
    ];

        public function user()
    {
        return $this->belongsTo(User::class);
    }
}