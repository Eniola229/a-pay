<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Ramsey\Uuid\Uuid;

class KycProfile extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'kyc_profiles';
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
        'bvn',
        'nin',
        'passport_photo',
        'proof_of_address',
        'bvn_phone_last_5',
        'status',
        'rejection_reason'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isApproved()
    {
        return $this->status === 'APPROVED';
    }

    public function isPending()
    {
        return $this->status === 'PENDING';
    }

    public function isRejected()
    {
        return $this->status === 'REJECTED';
    }
}