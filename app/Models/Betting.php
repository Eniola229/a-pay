<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Ramsey\Uuid\Uuid;

class Betting extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'bettings';
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
        'service_id',
        'customer_id',
        'amount',
        'total_amount',
        'status'
    ];

    /**
     * Get the user that made the purchase.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}