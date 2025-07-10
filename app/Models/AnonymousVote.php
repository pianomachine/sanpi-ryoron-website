<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnonymousVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'session_id',
        'fingerprint',
        'stance',
        'ip_address',
    ];

    protected $casts = [
        'ip_address' => 'string',
    ];

    /**
     * トピックとのリレーション
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * スコープ: 賛成投票
     */
    public function scopeSupport($query)
    {
        return $query->where('stance', 'support');
    }

    /**
     * スコープ: 反対投票
     */
    public function scopeOppose($query)
    {
        return $query->where('stance', 'oppose');
    }

    /**
     * スコープ: 中立投票
     */
    public function scopeNeutral($query)
    {
        return $query->where('stance', 'neutral');
    }

    /**
     * 特定のセッション・IPの投票を取得
     */
    public static function getVoteBySessionOrIp($topicId, $sessionId, $ipAddress = null)
    {
        $query = self::where('topic_id', $topicId);
        
        if ($sessionId) {
            $query->where('session_id', $sessionId);
        } elseif ($ipAddress) {
            $query->where('ip_address', $ipAddress);
        }
        
        return $query->first();
    }
}
