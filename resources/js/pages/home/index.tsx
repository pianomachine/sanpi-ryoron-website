import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { type User } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useInfiniteScroll } from '@/hooks/use-infinite-scroll';
import { SkeletonLoader, LoadingSeparator } from '@/components/skeleton-loader';

import CommonHeader from '@/components/common-header';
import { 
    ArrowUp, 
    ArrowDown, 
    MessageSquare, 
    Share, 
    TrendingUp,
    Clock,
    Flame,
    Loader2
} from 'lucide-react';

interface Post {
    id: number;
    subreddit: string;
    subreddit_icon: string;
    title: string;
    content?: string;
    type: 'text' | 'link' | 'image' | 'video';
    author: {
        username: string;
        karma: number;
        cake_day: string;
    };
    votes: {
        upvotes: number;
        downvotes: number;
        score: number;
    };
    comments_count: number;
    awards: Array<{
        type: string;
        count: number;
    }>;
    created_at: string;
    url?: string;
    image_url?: string;
    is_nsfw: boolean;
    is_spoiler: boolean;
    flair?: string;
}

interface Community {
    name: string;
    members: string;
    icon: string;
    description: string;
}

interface PopularPost {
    id: number;
    title: string;
    subreddit: string;
    score: number;
}

interface HomePageProps {
    posts: Post[];
    all_topics: Post[];
    current_sort: string;
    trending_communities: Community[];
    popular_posts_today: PopularPost[];
    user?: User | null;
}

export default function RedditHome({ 
    posts: initialPosts,
    all_topics,
    current_sort, 
    trending_communities, 
    popular_posts_today,
    user 
}: HomePageProps) {
    // 無限スクロールフック
    const { 
        data: infinitePosts, 
        loading, 
        hasMore, 
        error, 
        refresh,
        totalCount,
        isItemNew,
        markItemAsOld,
        showingSkeleton
    } = useInfiniteScroll({
        url: '/api/posts',
        initialData: initialPosts,
        params: { sort: current_sort },
        enabled: true
    });
    
    const [topicVotes, setTopicVotes] = useState<{[key: number]: 'support' | 'oppose' | null}>({});
    const [votingResults, setVotingResults] = useState<{[key: number]: {support: number, oppose: number} | null}>({});
    const [currentIndices, setCurrentIndices] = useState<{[key: string]: number}>({});
    const [isTransitioning, setIsTransitioning] = useState<{[key: number]: boolean}>({});

    // ソートが変更された時にリフレッシュ
    useEffect(() => {
        refresh();
    }, [current_sort]);

    // プレミアムプロモーションを挿入した投稿リストを生成
    const generatePostsWithAds = (posts: Post[]) => {
        const result: Array<Post | { type: 'premium-ad', id: string }> = [];
        
        posts.forEach((post, index) => {
            // 7個ごとにプレミアムプロモーションを挿入
            if (index > 0 && index % 7 === 0) {
                result.push({
                    type: 'premium-ad',
                    id: `premium-ad-${Math.floor(index / 7)}`
                });
            }
            result.push(post);
        });
        
        return result;
    };

    const postsWithAds = generatePostsWithAds(infinitePosts);

    // アニメーション完了時のハンドラー
    const handleAnimationEnd = (postId: number) => {
        markItemAsOld(postId);
    };

    const handleTopicNavigation = (currentPost: Post, direction: 'up' | 'down') => {
        if (current_sort !== 'hot' || isTransitioning[currentPost.id]) return;
        
        // 全ての投稿リストから同じコミュニティの投稿を取得
        const sameSubredditPosts = all_topics.filter(p => p.subreddit === currentPost.subreddit);
        if (sameSubredditPosts.length <= 1) return;
        const currentIndex = currentIndices[currentPost.subreddit] !== undefined 
            ? currentIndices[currentPost.subreddit] 
            : sameSubredditPosts.findIndex(p => p.id === currentPost.id);
        
        let nextIndex;
        if (direction === 'up') {
            nextIndex = currentIndex > 0 ? currentIndex - 1 : sameSubredditPosts.length - 1;
        } else {
            nextIndex = currentIndex < sameSubredditPosts.length - 1 ? currentIndex + 1 : 0;
        }
        
        const nextPost = sameSubredditPosts[nextIndex];
        if (nextPost && nextPost.id !== currentPost.id) {
            setIsTransitioning(prev => ({ ...prev, [currentPost.id]: true }));
            setCurrentIndices(prev => ({ ...prev, [currentPost.subreddit]: nextIndex }));
            
            // アニメーション付きで投稿内容を切り替え
            setTimeout(() => {
                // 無限スクロールのデータを更新する必要がある場合は、ここで処理
                setIsTransitioning(prev => ({ ...prev, [currentPost.id]: false }));
            }, 150);
        }
    };

    const handleTopicVote = async (topicId: number, stance: 'support' | 'oppose') => {
        try {
            const response = await fetch(`/topics/${topicId}/vote`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ stance }),
            });

            if (response.ok) {
                const result = await response.json();
                setTopicVotes(prev => ({
                    ...prev,
                    [topicId]: stance
                }));
                setVotingResults(prev => ({
                    ...prev,
                    [topicId]: {
                        support: result.support_percentage,
                        oppose: result.oppose_percentage
                    }
                }));
                
                // 匿名投票の場合はメッセージ表示
                if (result.is_anonymous) {
                    // トーストまたは簡単な通知を表示（必要に応じて）
                    console.log('匿名投票が完了しました');
                }
            } else {
                alert('投票に失敗しました');
            }
        } catch (error) {
            console.error('投票エラー:', error);
            alert('投票に失敗しました');
        }
    };

    const formatScore = (score: number) => {
        if (score >= 1000) {
            return `${(score / 1000).toFixed(1)}k`;
        }
        return score.toString();
    };

    const formatTimeAgo = (dateString: string) => {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now.getTime() - date.getTime();
        const hours = Math.floor(diff / (1000 * 60 * 60));
        
        if (hours < 1) return 'just now';
        if (hours < 24) return `${hours}h ago`;
        const days = Math.floor(hours / 24);
        return `${days}d ago`;
    };

    return (
        <>
            <Head title="賛否両論.com - ホーム" />
            
            <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
                <CommonHeader user={user} />

                {/* Main Content */}
                <div className="max-w-7xl mx-auto px-4 py-4">
                    <div className="grid grid-cols-1 lg:grid-cols-4 gap-4">
                        {/* Left Sidebar */}
                        <div className="hidden lg:block">
                            <Card className="sticky top-20 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                <CardHeader className="pb-3">
                                    <h3 className="font-semibold text-sm text-gray-900 dark:text-white">人気の議論カテゴリ</h3>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {trending_communities.slice(0, 5).map((community, index) => {
                                        // コミュニティ名からスラッグを生成
                                        const communitySlugMap: Record<string, string> = {
                                            '💰 デート代支払い': 'dating-payment',
                                            '🚃 女性専用車両': 'women-only-cars',
                                            '🏥 子ども温泉': 'children-spa',
                                            '🍝 レディースデー': 'ladies-day',
                                            '📱 電車内マナー': 'train-manner'
                                        };
                                        const slug = communitySlugMap[community.name];
                                        
                                        return (
                                            <Link key={community.name} href={`/community/${slug}`} className="block">
                                                <div className="flex items-center space-x-3 hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded cursor-pointer">
                                                    <span className="text-xs text-gray-500 dark:text-gray-400 w-4">{index + 1}</span>
                                                    <span className="text-lg">{community.icon}</span>
                                                    <div className="flex-1 min-w-0">
                                                        <div className="text-sm font-medium text-gray-900 dark:text-white truncate hover:text-blue-600 dark:hover:text-blue-400">
                                                            {community.name}
                                                        </div>
                                                        <div className="text-xs text-gray-500 dark:text-gray-400">
                                                            {community.members} 参加者
                                                        </div>
                                                    </div>
                                                </div>
                                            </Link>
                                        );
                                    })}
                                </CardContent>
                            </Card>
                        </div>

                        {/* Main Feed */}
                        <div className="lg:col-span-2">
                            {/* Sort Options */}
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 mb-4 p-3">
                                <div className="flex items-center justify-evenly">
                                    <Link 
                                        href="/home?sort=hot" 
                                        className={`flex items-center space-x-1 px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm whitespace-nowrap ${
                                            current_sort === 'hot' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        }`}
                                    >
                                        <Flame className="w-3 h-3 sm:w-4 sm:h-4" />
                                        <span>🔥人気</span>
                                    </Link>
                                    <Link 
                                        href="/home?sort=new" 
                                        className={`flex items-center space-x-1 px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm whitespace-nowrap ${
                                            current_sort === 'new' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        }`}
                                    >
                                        <Clock className="w-3 h-3 sm:w-4 sm:h-4" />
                                        <span>🆕最新</span>
                                    </Link>
                                    <Link 
                                        href="/home?sort=top" 
                                        className={`flex items-center space-x-1 px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm whitespace-nowrap ${
                                            current_sort === 'top' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        }`}
                                    >
                                        <TrendingUp className="w-3 h-3 sm:w-4 sm:h-4" />
                                        <span>⭐殿堂</span>
                                    </Link>
                                    <Link 
                                        href="/home?sort=rising" 
                                        className={`flex items-center space-x-1 px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm whitespace-nowrap ${
                                            current_sort === 'rising' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        }`}
                                    >
                                        <TrendingUp className="w-3 h-3 sm:w-4 sm:h-4" />
                                        <span>📈上昇中</span>
                                    </Link>
                                </div>
                            </div>

                            {/* Posts */}
                            <div className="space-y-3">
                                {postsWithAds.map((item, index) => {
                                    if (item.type === 'premium-ad') {
                                        return (
                                            <Card key={item.id} className="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border-yellow-200 dark:border-yellow-700 shadow-sm infinite-scroll-item">
                                                <CardContent className="p-6 text-center">
                                                    <div className="mb-4">
                                                        <div className="text-2xl mb-2">⭐</div>
                                                        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">
                                                            賛否両論.com プレミアム
                                                        </h3>
                                                        <p className="text-sm text-gray-600 dark:text-gray-300 mb-4">
                                                            プレミアム会員になって広告なしで議論を楽しもう
                                                        </p>
                                                    </div>
                                                    <Button className="bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white font-semibold px-6 py-2 rounded-full shadow-md transition-all duration-200 transform hover:scale-105">
                                                        今すぐ参加
                                                    </Button>
                                                </CardContent>
                                            </Card>
                                        );
                                    }
                                    const post = item as Post;
                                    const postIsNew = isItemNew(post.id);
                                    return (
                                        <Card 
                                            key={`post-${post.id}`} 
                                            className={`bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors overflow-hidden p-0 ${
                                                postIsNew ? 'infinite-scroll-item-delayed' : ''
                                            }`}
                                            onAnimationEnd={() => postIsNew && handleAnimationEnd(post.id)}
                                        >
                                            <div className="flex">
                                                {/* Topic Navigation Section */}
                                                <div className={`flex flex-col items-center justify-center px-2 bg-gray-200 dark:bg-gray-700 min-w-[50px] flex-shrink-0 transition-opacity duration-150 ${isTransitioning[post.id] ? 'opacity-50' : 'opacity-100'}`}>
                                                    {current_sort === 'hot' ? (
                                                        // Hot表示の場合：議題切り替え機能付き
                                                        <>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className={`p-1 h-auto text-gray-500 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors ${
                                                                    all_topics.filter(p => p.subreddit === post.subreddit).length <= 1 ? 'cursor-not-allowed opacity-30' : ''
                                                                } ${isTransitioning[post.id] ? 'cursor-not-allowed' : ''}`}
                                                                onClick={() => handleTopicNavigation(post, 'up')}
                                                                disabled={all_topics.filter(p => p.subreddit === post.subreddit).length <= 1 || isTransitioning[post.id]}
                                                                title={all_topics.filter(p => p.subreddit === post.subreddit).length > 1 ? `前の議題に切り替え` : '他の議題がありません'}
                                                            >
                                                                <ArrowUp className="w-5 h-5" />
                                                            </Button>
                                                            <div className="text-center my-1">
                                                                <div className={`text-xs font-medium text-gray-600 dark:text-gray-300`}>
                                                                    {(() => {
                                                                        const sameSubredditPosts = all_topics.filter(p => p.subreddit === post.subreddit);
                                                                        const currentIndex = (currentIndices[post.subreddit] !== undefined) 
                                                                            ? currentIndices[post.subreddit] 
                                                                            : sameSubredditPosts.findIndex(p => p.id === post.id);
                                                                        return `${currentIndex + 1}/${sameSubredditPosts.length}`;
                                                                    })()}
                                                                </div>
                                                                <div className="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate w-8">
                                                                    {post.subreddit.slice(0, 3)}
                                                                </div>
                                                            </div>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className={`p-1 h-auto text-gray-500 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors ${
                                                                    all_topics.filter(p => p.subreddit === post.subreddit).length <= 1 ? 'cursor-not-allowed opacity-30' : ''
                                                                } ${isTransitioning[post.id] ? 'cursor-not-allowed' : ''}`}
                                                                onClick={() => handleTopicNavigation(post, 'down')}
                                                                disabled={all_topics.filter(p => p.subreddit === post.subreddit).length <= 1 || isTransitioning[post.id]}
                                                                title={all_topics.filter(p => p.subreddit === post.subreddit).length > 1 ? `次の議題に切り替え` : '他の議題がありません'}
                                                            >
                                                                <ArrowDown className="w-5 h-5" />
                                                            </Button>
                                                        </>
                                                    ) : (
                                                        // new, top, risingの場合：何も表示しない（空のスペース）
                                                        <div className="text-xs text-gray-500 dark:text-gray-400 text-center">
                                                            <div className="font-medium">{formatScore(post.votes.score)}</div>
                                                            <div className="text-xs">スコア</div>
                                                        </div>
                                                    )}
                                                </div>

                                                {/* Post Content */}
                                                <div className={`flex-1 p-3 ${current_sort === 'hot' ? `transition-opacity duration-150 ${isTransitioning[post.id] ? 'opacity-30' : 'opacity-100'}` : ''}`}>
                                                    {/* Post Header */}
                                                    <div className="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400 mb-2">
                                                        <span className="font-medium text-gray-900 dark:text-white">{post.subreddit}</span>
                                                        <span>•</span>
                                                        <span>投稿者: {post.author.username}</span>
                                                        <span>•</span>
                                                        <span>{formatTimeAgo(post.created_at)}</span>
                                                        {post.flair && (
                                                            <>
                                                                <span>•</span>
                                                                <Badge variant="outline" className="text-xs border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">
                                                                    {post.flair}
                                                                </Badge>
                                                            </>
                                                        )}
                                                    </div>

                                                    {/* Post Title */}
                                                    <Link href={`/post/${post.id}`}>
                                                        <h2 className="text-lg font-medium text-gray-900 dark:text-white mb-2 hover:text-blue-600 dark:hover:text-blue-400 cursor-pointer">
                                                            {post.title}
                                                        </h2>
                                                    </Link>

                                                    {/* Post Content */}
                                                    {post.content && (
                                                        <p className="text-gray-700 dark:text-gray-300 text-sm mb-3 line-clamp-3">{post.content}</p>
                                                    )}

                                                    {/* Post Image */}
                                                    {post.image_url && (
                                                        <div className="mb-3">
                                                            <img 
                                                                src={post.image_url} 
                                                                alt={post.title}
                                                                className="max-w-full h-auto rounded border"
                                                            />
                                                        </div>
                                                    )}

                                                    {/* Post Actions */}
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                                                            <Link href={`/post/${post.id}`}>
                                                                <Button variant="ghost" size="sm" className="flex items-center space-x-1 h-auto p-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                                    <MessageSquare className="w-4 h-4" />
                                                                    <span>{post.comments_count} コメント</span>
                                                                </Button>
                                                            </Link>
                                                            <Button variant="ghost" size="sm" className="h-auto p-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" title="シェア">
                                                                <Share className="w-4 h-4" />
                                                            </Button>
                                                        </div>

                                                        {/* Vote Buttons or Results */}
                                                        <div className="ml-auto">
                                                            {votingResults[post.id] ? (
                                                                <div className="flex items-center space-x-2 text-xs">
                                                                    <div className="flex items-center space-x-1">
                                                                        <span className="text-blue-600 dark:text-blue-400">賛成</span>
                                                                        <span className="font-medium text-blue-600 dark:text-blue-400">
                                                                            {votingResults[post.id]?.support}%
                                                                        </span>
                                                                    </div>
                                                                    <span className="text-gray-400 dark:text-gray-500">|</span>
                                                                    <div className="flex items-center space-x-1">
                                                                        <span className="text-red-600 dark:text-red-400">反対</span>
                                                                        <span className="font-medium text-red-600 dark:text-red-400">
                                                                            {votingResults[post.id]?.oppose}%
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            ) : (
                                                                <div className="flex items-center space-x-2">
                                                                    <Button
                                                                        variant={topicVotes[post.id] === 'support' ? 'default' : 'outline'}
                                                                        size="sm"
                                                                        className={`text-xs h-6 px-2 ${
                                                                            topicVotes[post.id] === 'support' 
                                                                                ? 'bg-blue-600 text-white' 
                                                                                : 'border-blue-600 dark:border-blue-400 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20'
                                                                        }`}
                                                                        onClick={() => handleTopicVote(post.id, 'support')}
                                                                    >
                                                                        賛成
                                                                    </Button>
                                                                    <Button
                                                                        variant={topicVotes[post.id] === 'oppose' ? 'default' : 'outline'}
                                                                        size="sm"
                                                                        className={`text-xs h-6 px-2 ${
                                                                            topicVotes[post.id] === 'oppose' 
                                                                                ? 'bg-red-600 text-white' 
                                                                                : 'border-red-600 dark:border-red-400 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20'
                                                                        }`}
                                                                        onClick={() => handleTopicVote(post.id, 'oppose')}
                                                                    >
                                                                        反対
                                                                    </Button>
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </Card>
                                    );
                                })}
                                
                                {/* ローディング中の表示改良 */}
                                {loading && hasMore && (
                                    <>
                                        <LoadingSeparator />
                                        <SkeletonLoader count={3} />
                                    </>
                                )}
                                
                                {/* エラー時の表示 */}
                                {error && (
                                    <div className="text-center py-8 text-red-500 dark:text-red-400">
                                        <p>エラーが発生しました: {error}</p>
                                        <Button onClick={refresh} className="mt-4">再読み込み</Button>
                                    </div>
                                )}
                                
                                {/* 完了時の表示 */}
                                {!hasMore && postsWithAds.length > 0 && !loading && (
                                    <div className="text-center py-8">
                                        <div className="text-gray-500 dark:text-gray-400 mb-2">
                                            🎉 すべての投稿を読み込みました
                                        </div>
                                        <p className="text-sm text-gray-400 dark:text-gray-500">
                                            合計 {postsWithAds.length} 件の投稿
                                        </p>
                                    </div>
                                )}
                                
                                {/* 初期ローディング中（投稿が0件の場合） */}
                                {postsWithAds.length === 0 && loading && (
                                    <SkeletonLoader count={5} />
                                )}
                            </div>
                        </div>

                        {/* Right Sidebar */}
                        <div className="space-y-4">
                            {/* Popular Posts Today */}
                            <Card className="bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                <CardHeader className="pb-3">
                                    <h3 className="font-semibold text-sm text-gray-900 dark:text-white">本日の人気議題</h3>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {popular_posts_today.map((post, index) => (
                                        <Link key={index} href={`/post/${post.id}`} className="block">
                                            <div className="hover:bg-gray-50 dark:hover:bg-gray-700 p-2 rounded cursor-pointer">
                                                <div className="flex items-start space-x-2">
                                                    <span className="text-xs text-gray-500 dark:text-gray-400 mt-1">{index + 1}</span>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm font-medium text-gray-900 dark:text-white line-clamp-2 mb-1 hover:text-blue-600 dark:hover:text-blue-400">
                                                            {post.title}
                                                        </p>
                                                        <div className="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
                                                            <span>{post.subreddit}</span>
                                                            <span>•</span>
                                                            <span>{formatScore(post.score)} 賛成票</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </Link>
                                    ))}
                                </CardContent>
                            </Card>

                            {/* Premium Ad */}
                            <Card className="bg-gradient-to-r from-blue-100 to-red-100 dark:from-blue-900/30 dark:to-red-900/30 border-gray-200 dark:border-gray-700">
                                <CardContent className="p-4">
                                    <div className="text-center">
                                        <h3 className="font-semibold text-sm mb-2 text-gray-900 dark:text-white">賛否両論.com プレミアム</h3>
                                        <p className="text-xs text-gray-600 dark:text-gray-300 mb-3">
                                            プレミアム会員になって広告なしで議論を楽しもう
                                        </p>
                                        <Button size="sm" className="w-full bg-blue-600 hover:bg-blue-700 text-white">
                                            今すぐ参加
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
} 