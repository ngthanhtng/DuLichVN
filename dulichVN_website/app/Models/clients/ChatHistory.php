<?php

namespace App\Models\clients;

use Illuminate\Database\Eloquent\Model;

class ChatHistory extends Model
{
    protected $table = 'chat_histories';

    protected $fillable = [
        'user_id',
        'message',
        'response',
    ];
}