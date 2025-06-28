<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Borrow extends Model
{
    use HasFactory;

    protected $table = 'borrows';

    // Use UUIDs
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'amount',
        'repayment_status',
        'for',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid(); // ensures UUID format like yours
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
