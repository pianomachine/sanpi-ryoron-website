<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'community_id',
        'role',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    /**
     * ユーザーとのリレーション
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * コミュニティとのリレーション
     */
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    /**
     * メンバーかどうか
     */
    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    /**
     * モデレーターかどうか
     */
    public function isModerator(): bool
    {
        return in_array($this->role, ['moderator', 'admin']);
    }

    /**
     * 管理者かどうか
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
