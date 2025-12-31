<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Ramsey\Uuid\Uuid;

class WhatsappMessage extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'whatsapp_messages';
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
        'phone_number',
        'direction',
        'message_body',
        'message_sid',
        'status',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Get all messages for a specific phone number
    public static function getConversation($phoneNumber)
    {
        return self::where('phone_number', $phoneNumber)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    // Get recent messages
    public static function getRecent($limit = 50)
    {
        return self::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}