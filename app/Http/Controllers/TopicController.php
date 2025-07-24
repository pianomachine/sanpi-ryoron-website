<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Topic;
use App\Models\Vote;
use App\Models\AnonymousVote;
use App\Services\LinkPreviewService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    /**
     * 議題一覧表示
     */
    public function index(Request $request): Response
    {
        $tab = $request->get('tab', 'hot');
        
        $hotTopics = Topic::with(['user', 'comments'])
            ->where('status', 'active')
            ->withCount(['comments'])
            ->orderByDesc('views_count')
            ->orderByDesc('comments_count')
            ->limit(10)
            ->get();

        $latestTopics = Topic::with(['user', 'comments'])
            ->where('status', 'active')
            ->withCount(['comments'])
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('topics/index', [
            'hotTopics' => $hotTopics,
            'latestTopics' => $latestTopics,
            'currentTab' => $tab,
        ]);
    }

    /**
     * 議題詳細表示
     */
    public function show(Topic $topic): Response
    {
        $topic->incrementViewsCount();
        
        $topic->load([
            'user',
            'comments' => function ($query) {
                $query->with(['user', 'votes'])
                    ->orderByDesc('votes_count')
                    ->orderByDesc('created_at');
            }
        ]);

        $supportComments = $topic->comments->where('stance', 'support');
        $opposeComments = $topic->comments->where('stance', 'oppose');
        $neutralComments = $topic->comments->where('stance', 'neutral');

        // 現在のユーザーがこの議題を保存しているかチェック
        $isSaved = false;
        if (auth()->check()) {
            $isSaved = \App\Models\SavedTopic::where('user_id', auth()->id())
                ->where('topic_id', $topic->id)
                ->exists();
        }

        return Inertia::render('topics/show', [
            'topic' => $topic,
            'supportComments' => $supportComments->values(),
            'opposeComments' => $opposeComments->values(),
            'neutralComments' => $neutralComments->values(),
            'isSaved' => $isSaved,
        ]);
    }

    /**
     * 新規議題作成
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'community_id' => 'required|integer|exists:communities,id',
            'title' => 'required|string|min:10|max:200',
            'content' => 'required|string|min:20|max:5000',
            'stance' => 'required|in:support,oppose,neutral',
            'flair' => 'nullable|string|max:50',
        ]);

        // リンクプレビューを生成
        $linkPreviewService = new LinkPreviewService();
        $linkPreviews = $linkPreviewService->extractLinksFromContent($validated['content']);

        // 議題を作成
        $topic = Topic::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'description' => $validated['content'], // description フィールドも content で埋める
            'community_id' => $validated['community_id'],
            'user_id' => auth()->id(),
            'flair' => $validated['flair'],
            'status' => 'active',
            'upvotes' => 0,
            'downvotes' => 0,
            'score' => 0,
            'views_count' => 0,
            'is_nsfw' => false,
            'link_previews' => $linkPreviews,
            'is_spoiler' => false,
        ]);

        // 作成者の投票も記録
        \App\Models\TopicVote::create([
            'topic_id' => $topic->id,
            'user_id' => auth()->id(),
            'stance' => $validated['stance'],
        ]);

        // 成功時はホームページにリダイレクト
        return redirect()->route('home')->with('success', '議題を投稿しました！');
    }

    /**
     * コメント投稿
     */
    public function storeComment(Request $request, Topic $topic)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:500',
            'stance' => 'required|in:support,oppose,neutral',
        ]);

        $comment = $topic->comments()->create([
            'content' => $validated['content'],
            'stance' => $validated['stance'],
            'user_id' => auth()->id(),
        ]);

        $topic->updateCommentsCount();

        return back();
    }

    /**
     * コメントへの投票
     */
    public function vote(Request $request, Comment $comment)
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => '評価するにはログインが必要です'
            ], 401);
        }

        $validated = $request->validate([
            'vote_type' => 'required|in:like,dislike',
        ]);

        // 既存の投票を確認
        $existingVote = Vote::where('comment_id', $comment->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existingVote) {
            if ($existingVote->vote_type === $validated['vote_type']) {
                // 同じ投票タイプの場合は削除（取り消し）
                $existingVote->delete();
            } else {
                // 異なる投票タイプの場合は更新
                $existingVote->update(['vote_type' => $validated['vote_type']]);
            }
        } else {
            // 新規投票
            Vote::create([
                'comment_id' => $comment->id,
                'user_id' => auth()->id(),
                'vote_type' => $validated['vote_type'],
            ]);
        }

        $comment->updateVotesCount();
        
        // リフレッシュしてデータベースから最新の投票数を取得
        $comment->refresh();

        return response()->json([
            'success' => true,
            'message' => '評価しました',
            'upvotes' => $comment->upvotes,
            'downvotes' => $comment->downvotes,
            'score' => $comment->score,
            'votes_count' => $comment->votes_count
        ]);
    }

    /**
     * 議題への投票（匿名投票対応）
     */
    public function voteTopic(Request $request, Topic $topic)
    {
        $validated = $request->validate([
            'stance' => 'required|in:support,oppose,neutral',
        ]);

        $sessionId = $request->session()->getId();
        $ipAddress = $request->ip();
        $user = auth()->user();

        if ($user) {
            // 認証済みユーザーの投票処理
            $existingVote = \App\Models\TopicVote::where('topic_id', $topic->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingVote) {
                // 既存の投票を更新
                $existingVote->update(['stance' => $validated['stance']]);
            } else {
                // 新規投票
                \App\Models\TopicVote::create([
                    'topic_id' => $topic->id,
                    'user_id' => $user->id,
                    'stance' => $validated['stance'],
                ]);
            }
        } else {
            // 匿名ユーザーの投票処理
            $existingAnonymousVote = AnonymousVote::getVoteBySessionOrIp(
                $topic->id, 
                $sessionId, 
                $ipAddress
            );

            if ($existingAnonymousVote) {
                // 既存の匿名投票を更新
                $existingAnonymousVote->update(['stance' => $validated['stance']]);
            } else {
                // 新規匿名投票
                AnonymousVote::create([
                    'topic_id' => $topic->id,
                    'session_id' => $sessionId,
                    'stance' => $validated['stance'],
                    'ip_address' => $ipAddress,
                ]);
            }
        }

        // 投票結果の割合を計算（認証済み + 匿名投票の合計）
        $authenticatedVotes = \App\Models\TopicVote::where('topic_id', $topic->id);
        $anonymousVotes = AnonymousVote::where('topic_id', $topic->id);

        $totalAuthenticatedVotes = $authenticatedVotes->count();
        $totalAnonymousVotes = $anonymousVotes->count();
        $totalVotes = $totalAuthenticatedVotes + $totalAnonymousVotes;

        $authSupportVotes = (clone $authenticatedVotes)->where('stance', 'support')->count();
        $authOpposeVotes = (clone $authenticatedVotes)->where('stance', 'oppose')->count();
        $authNeutralVotes = (clone $authenticatedVotes)->where('stance', 'neutral')->count();

        $anonSupportVotes = (clone $anonymousVotes)->where('stance', 'support')->count();
        $anonOpposeVotes = (clone $anonymousVotes)->where('stance', 'oppose')->count();
        $anonNeutralVotes = (clone $anonymousVotes)->where('stance', 'neutral')->count();

        $supportVotes = $authSupportVotes + $anonSupportVotes;
        $opposeVotes = $authOpposeVotes + $anonOpposeVotes;
        $neutralVotes = $authNeutralVotes + $anonNeutralVotes;

        $supportPercentage = $totalVotes > 0 ? round(($supportVotes / $totalVotes) * 100) : 0;
        $opposePercentage = $totalVotes > 0 ? round(($opposeVotes / $totalVotes) * 100) : 0;
        $neutralPercentage = $totalVotes > 0 ? round(($neutralVotes / $totalVotes) * 100) : 0;

        return response()->json([
            'success' => true,
            'support_percentage' => $supportPercentage,
            'oppose_percentage' => $opposePercentage,
            'neutral_percentage' => $neutralPercentage,
            'total_votes' => $totalVotes,
            'is_anonymous' => !$user,
        ]);
    }

    /**
     * 議題を保存
     */
    public function saveTopic(Topic $topic)
    {
        $user = auth()->user();
        
        // 既に保存済みかチェック
        $existingSave = \App\Models\SavedTopic::where('user_id', $user->id)
            ->where('topic_id', $topic->id)
            ->first();

        if ($existingSave) {
            \Log::info("User {$user->id} tried to save already saved topic {$topic->id}");
            return response()->json([
                'success' => false,
                'message' => '既に保存済みです'
            ], 409);
        }

        $savedTopic = \App\Models\SavedTopic::create([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);

        \Log::info("User {$user->id} saved topic {$topic->id}");

        return response()->json([
            'success' => true,
            'message' => '議題を保存しました',
            'saved_topic_id' => $savedTopic->id
        ]);
    }

    /**
     * 議題の保存を解除
     */
    public function unsaveTopic(Topic $topic)
    {
        $user = auth()->user();
        
        $deleted = \App\Models\SavedTopic::where('user_id', $user->id)
            ->where('topic_id', $topic->id)
            ->delete();

        if ($deleted) {
            \Log::info("User {$user->id} unsaved topic {$topic->id}");
            return response()->json([
                'success' => true,
                'message' => '保存を解除しました'
            ]);
        }

        \Log::warning("User {$user->id} tried to unsave non-saved topic {$topic->id}");
        return response()->json([
            'success' => false,
            'message' => '保存されていない議題です'
        ], 404);
    }

    /**
     * ユーザーの投稿した議題一覧
     */
    public function getUserTopics()
    {
        $topics = Topic::with(['user', 'community'])
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->withCount(['comments'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($topics);
    }

    /**
     * ユーザーの保存した議題一覧
     */
    public function getSavedTopics()
    {
        $savedTopics = \App\Models\SavedTopic::with(['topic.user', 'topic.community'])
            ->where('user_id', auth()->id())
            ->whereHas('topic', function($query) {
                $query->where('status', 'active');
            })
            ->orderByDesc('created_at')
            ->paginate(10);

        // 議題のデータを抽出
        $topics = $savedTopics->getCollection()->map(function($savedTopic) {
            return $savedTopic->topic;
        });

        $savedTopics->setCollection($topics);

        return response()->json($savedTopics);
    }

    /**
     * 議題を削除
     */
    public function destroy(Topic $topic)
    {
        $user = auth()->user();
        
        // 作成者のみ削除可能
        if ($topic->user_id !== $user->id) {
            \Log::warning("User {$user->id} tried to delete topic {$topic->id} without permission");
            return response()->json([
                'success' => false,
                'message' => '削除権限がありません'
            ], 403);
        }

        $topic->update(['status' => 'deleted']);
        
        \Log::info("User {$user->id} deleted topic {$topic->id}");

        return response()->json([
            'success' => true,
            'message' => '議題を削除しました'
        ]);
    }

    /**
     * 既存議題のリンクプレビューを生成
     */
    public function generateLinkPreviews(Topic $topic)
    {
        try {
            $linkPreviewService = new LinkPreviewService();
            $linkPreviews = $linkPreviewService->extractLinksFromContent($topic->content);
            
            $topic->update(['link_previews' => $linkPreviews]);
            
            return response()->json([
                'success' => true,
                'message' => 'リンクプレビューを生成しました',
                'link_previews' => $linkPreviews
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'リンクプレビューの生成に失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }
}
