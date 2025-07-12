<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

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

    public function updateCachedCounts(): void
    {
        $commentsCount = $this->comments()->count();
        $uniqueCommenters = $this->comments()->distinct('user_id')->count('user_id');
        
        $authVotes = \App\Models\TopicVote::where('topic_id', $this->id)
            ->groupBy('stance')
            ->selectRaw('stance, COUNT(*) as count')
            ->pluck('count', 'stance');
        
        $anonVotes = \App\Models\AnonymousVote::where('topic_id', $this->id)
            ->groupBy('stance')
            ->selectRaw('stance, COUNT(*) as count')
            ->pluck('count', 'stance');
        
        $this->update([
            'cached_comments_count' => $commentsCount,
            'cached_unique_commenters' => $uniqueCommenters,
            'cached_support_votes' => $authVotes->get('support', 0),
            'cached_oppose_votes' => $authVotes->get('oppose', 0),
            'cached_anonymous_support_votes' => $anonVotes->get('support', 0),
            'cached_anonymous_oppose_votes' => $anonVotes->get('oppose', 0),
            'counts_updated_at' => now()
        ]);
        
        // キャッシュをクリア
        Cache::forget("topic_stats_{$this->id}");
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
