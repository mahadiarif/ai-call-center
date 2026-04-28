<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiTicketComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_ticket_id',
        'commenter_name',
        'commenter_mobile',
        'comment_text',
    ];

    // AiTicket এর সাথে রিলেশন
    public function aiTicket()
    {
        return $this->belongsTo(AiTicket::class);
    }
}
