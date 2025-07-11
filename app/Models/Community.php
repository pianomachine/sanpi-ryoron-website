<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Community extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'banner_color',
        'rules',
        'moderators',
        'members_count',
        'online_count',
        'status'
    ];

    protected $casts = [
        'rules' => 'array',
        'moderators' => 'array'
    ];

    // リレーションシップ
    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function memberships()
    {
        return $this->hasMany(CommunityMembership::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'community_memberships')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function comments()
    {
        return $this->hasManyThrough(
            \App\Models\Comment::class,
            \App\Models\Topic::class,
            'community_id',
            'topic_id'
        );
    }

    // アクセサー
    public function getMembersFormatted()
    {
        if ($this->members_count >= 1000) {
            return number_format($this->members_count / 1000, 1) . 'K';
        }
        return number_format($this->members_count);
    }

    public function getOnlineFormatted()
    {
        if ($this->online_count >= 1000) {
            return number_format($this->online_count / 1000, 1) . 'K';
        }
        return number_format($this->online_count);
    }
}
