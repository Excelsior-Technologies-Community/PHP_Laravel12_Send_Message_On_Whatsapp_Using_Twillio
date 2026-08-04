<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'from',
        'message',
        'message_type',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];
}