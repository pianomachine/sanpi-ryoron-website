<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'user_id',
        'stance'
    ];

    // リレーションシップ
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // スコープ
    public function scopeSupport($query)
    {
        return $query->where('stance', 'support');
    }

    public function scopeOppose($query)
    {
        return $query->where('stance', 'oppose');
    }
}
