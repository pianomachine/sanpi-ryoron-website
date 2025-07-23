<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    protected $fillable = [
        'topic_id',
        'user_id',
        'parent_id',
        'content',
        'stance',
        'depth',
        'score',
        'upvotes',
        'downvotes',
        'votes_count',
        'is_collapsed',
    ];

    protected $casts = [
        'votes_count' => 'integer',
        'depth' => 'integer',
        'score' => 'integer',
        'upvotes' => 'integer',
        'downvotes' => 'integer',
        'is_collapsed' => 'boolean',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('score', 'desc');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Vote::class)->where('vote_type', 'like');
    }

    public function dislikes(): HasMany
    {
        return $this->hasMany(Vote::class)->where('vote_type', 'dislike');
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

    public function updateVotesCount(): void
    {
        $likesCount = $this->likes()->count();
        $dislikesCount = $this->dislikes()->count();
        
        $this->update([
            'upvotes' => $likesCount,
            'downvotes' => $dislikesCount,
            'votes_count' => $likesCount + $dislikesCount,
            'score' => $likesCount - $dislikesCount
        ]);
    }

    public function updateScore(): void
    {
        $this->update([
            'score' => $this->upvotes - $this->downvotes
        ]);
    }

    public function incrementUpvotes(): void
    {
        $this->increment('upvotes');
        $this->updateScore();
    }

    public function incrementDownvotes(): void
    {
        $this->increment('downvotes');
        $this->updateScore();
    }

    public function decrementUpvotes(): void
    {
        $this->decrement('upvotes');
        $this->updateScore();
    }

    public function decrementDownvotes(): void
    {
        $this->decrement('downvotes');
        $this->updateScore();
    }

    public function getStanceColorAttribute(): string
    {
        return $this->stance === 'support' ? 'blue' : 'red';
    }

    // 階層構造のスコープ
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithReplies($query)
    {
        return $query->with(['allChildren.user']);
    }
}
