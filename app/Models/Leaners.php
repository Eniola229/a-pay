<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Ramsey\Uuid\Uuid;

class Leaners extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'leaners';
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

    protected $fillable = [
        'first_name', 'last_name', 'age', 'sex', 'country', 'state', 
        'course_of_study', 'is_student', 'amount_paid', 'payment_status',
        'email', 'whatsapp'
    ];
}