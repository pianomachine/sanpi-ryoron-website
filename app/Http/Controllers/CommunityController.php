<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\CommunityMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    /**
     * 全コミュニティ一覧を取得
     */
    public function index()
    {
        $communities = Community::where('status', 'active')
            ->orderBy('members_count', 'desc')
            ->get()
            ->map(function ($community) {
                return [
                    'id' => $community->id,
                    'name' => $community->name,
                    'slug' => $community->slug,
                    'icon' => $community->icon,
                    'description' => $community->description,
                    'members_count' => $community->members_count,
                ];
            });

        return response()->json($communities);
    }

    /**
     * 新しいコミュニティを作成
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3|max:50|unique:communities,name',
            'icon' => 'required|string|max:2',
            'description' => 'required|string|min:10|max:200',
        ], [
            'name.required' => 'コミュニティ名は必須です',
            'name.min' => 'コミュニティ名は3文字以上で入力してください',
            'name.max' => 'コミュニティ名は50文字以内で入力してください',
            'name.unique' => 'このコミュニティ名は既に使用されています',
            'icon.required' => 'アイコンは必須です',
            'icon.max' => 'アイコンは絵文字1文字で入力してください',
            'description.required' => '説明は必須です',
            'description.min' => '説明は10文字以上で入力してください',
            'description.max' => '説明は200文字以内で入力してください',
        ]);

        // スラッグを生成（名前から絵文字を除去してケバブケースに変換）
        $nameForSlug = preg_replace('/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]/u', '', $request->name);
        $baseSlug = Str::slug(trim($nameForSlug));
        
        // スラッグの重複をチェックして、必要に応じて数字を付ける
        $slug = $baseSlug;
        $counter = 1;
        while (Community::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $community = Community::create([
            'name' => $request->name,
            'slug' => $slug,
            'icon' => $request->icon,
            'description' => $request->description,
            'banner_color' => 'from-gray-100 to-blue-100',
            'rules' => [
                '相手を尊重した発言を心がけましょう',
                '個人攻撃や暴言は禁止です',
                '建設的な議論を行いましょう',
                'ルールに違反した投稿は削除される場合があります'
            ],
            'moderators' => [$request->user()->name],
            'members_count' => 1,
            'online_count' => 1,
            'status' => 'active'
        ]);

        return response()->json([
            'id' => $community->id,
            'name' => $community->name,
            'slug' => $community->slug,
            'icon' => $community->icon,
            'description' => $community->description,
            'members_count' => $community->members_count,
        ], 201);
    }

    /**
     * コミュニティ名の重複をチェック
     */
    public function checkName(Request $request)
    {
        $name = $request->query('name');
        $exists = Community::where('name', $name)->exists();
        
        return response()->json(['exists' => $exists]);
    }

    /**
     * コミュニティに参加
     */
    public function join(Request $request, Community $community)
    {
        $user = $request->user();
        
        // 既に参加しているかチェック
        $existingMembership = CommunityMembership::where('user_id', $user->id)
            ->where('community_id', $community->id)
            ->exists();
            
        if ($existingMembership) {
            return response()->json([
                'message' => '既にこのコミュニティに参加しています'
            ], 409);
        }

        // メンバーシップを作成
        CommunityMembership::create([
            'user_id' => $user->id,
            'community_id' => $community->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        // コミュニティのメンバー数を更新
        $community->increment('members_count');

        return response()->json([
            'message' => 'コミュニティに参加しました',
            'is_member' => true,
        ]);
    }

    /**
     * コミュニティから脱退
     */
    public function leave(Request $request, Community $community)
    {
        $user = $request->user();
        
        $membership = CommunityMembership::where('user_id', $user->id)
            ->where('community_id', $community->id)
            ->first();
            
        if (!$membership) {
            return response()->json([
                'message' => 'このコミュニティに参加していません'
            ], 404);
        }

        // メンバーシップを削除
        $membership->delete();

        // コミュニティのメンバー数を更新
        $community->decrement('members_count');

        return response()->json([
            'message' => 'コミュニティから脱退しました',
            'is_member' => false,
        ]);
    }

    /**
     * ユーザーのメンバーシップ状況を取得
     */
    public function checkMembership(Request $request, Community $community)
    {
        if (!$request->user()) {
            return response()->json(['is_member' => false]);
        }

        $isMember = CommunityMembership::where('user_id', $request->user()->id)
            ->where('community_id', $community->id)
            ->exists();

        return response()->json(['is_member' => $isMember]);
    }
} 