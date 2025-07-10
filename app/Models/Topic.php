<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    protected $fillable = [
        'title',
        'description',
        'user_id',
        'community_id',
        'content',
        'type',
        'url',
        'image_url',
        'flair',
        'score',
        'upvotes',
        'downvotes',
        'is_nsfw',
        'is_spoiler',
        'awards',
        'status',
        'views_count',
        'comments_count',
    ];

    protected $casts = [
        'views_count' => 'integer',
        'comments_count' => 'integer',
        'score' => 'integer',
        'upvotes' => 'integer',
        'downvotes' => 'integer',
        'is_nsfw' => 'boolean',
        'is_spoiler' => 'boolean',
        'awards' => 'array',
    ];

    // リレーションシップ
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function topLevelComments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(TopicVote::class);
    }

    public function supportComments(): HasMany
    {
        return $this->hasMany(Comment::class)->where('stance', 'support');
    }

    public function opposeComments(): HasMany
    {
        return $this->hasMany(Comment::class)->where('stance', 'oppose');
    }

    // アクセサー・メソッド
    public function getScoreFormatted()
    {
        if (abs($this->score) >= 1000) {
            return number_format($this->score / 1000, 1) . 'k';
        }
        return number_format($this->score);
    }

    public function getTimeAgo()
    {
        $diff = now()->diffInHours($this->created_at);
        
        if ($diff < 1) {
            $minutes = now()->diffInMinutes($this->created_at);
            return $minutes . '分前';
        } elseif ($diff < 24) {
            return $diff . '時間前';
        } else {
            $days = now()->diffInDays($this->created_at);
            return $days . '日前';
        }
    }

    public function incrementViewsCount(): void
    {
        $this->increment('views_count');
    }

    public function updateCommentsCount(): void
    {
        $this->update([
            'comments_count' => $this->comments()->count()
        ]);
    }

    public function updateVoteScore(): void
    {
        $this->update([
            'score' => $this->upvotes - $this->downvotes
        ]);
    }

    public function incrementUpvotes(): void
    {
        $this->increment('upvotes');
        $this->updateVoteScore();
    }

    public function incrementDownvotes(): void
    {
        $this->increment('downvotes');
        $this->updateVoteScore();
    }

    public function decrementUpvotes(): void
    {
        $this->decrement('upvotes');
        $this->updateVoteScore();
    }

    public function decrementDownvotes(): void
    {
        $this->decrement('downvotes');
        $this->updateVoteScore();
    }
}
