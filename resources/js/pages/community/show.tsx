import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { type User } from '@/types';
import { useInfiniteScroll } from '@/hooks/use-infinite-scroll';
import { SkeletonLoader, LoadingSeparator } from '@/components/skeleton-loader';
import CommonHeader from '@/components/common-header';
import { 
    ArrowUp, 
    ArrowDown, 
    MessageSquare, 
    Share, 
    Award, 
    Bookmark,
    Users,
    Eye,
    Calendar,
    User as UserIcon,
    Settings,
    Bell,
    Plus,
    Star,
    Loader2
} from 'lucide-react';

interface Post {
    id: number;
    subreddit: string;
    subreddit_slug: string;
    title: string;
    content?: string;
    author: {
        username: string;
        karma: number;
    };
    votes: {
        score: number;
    };
    comments_count: number;
    created_at: string;
    flair?: string;
}

interface Community {
    id: number;
    name: string;
    icon: string;
    slug: string;
    members: string;
    online: string;
    description: string;
    created_at: string;
    rules: string[];
    moderators: string[];
    banner_color: string;
    is_member: boolean;
}

interface CommunityShowProps {
    community: Community;
    posts: Post[];
    current_sort: string;
    user?: User | null;
}

export default function CommunityShow({ community, posts: initialPosts, current_sort, user }: CommunityShowProps) {
    // 無限スクロールフック
    const { 
        data: infinitePosts, 
        loading, 
        hasMore, 
        error, 
        refresh,
        isItemNew,
        markItemAsOld
    } = useInfiniteScroll({
        url: `/api/community/${community.slug}/posts`,
        initialData: initialPosts,
        params: { sort: current_sort },
        enabled: true
    });
    
    const [votedPosts, setVotedPosts] = useState<Record<number, 'up' | 'down' | null>>({});
    const [isMember, setIsMember] = useState(community.is_member);
    const [isMembershipLoading, setIsMembershipLoading] = useState(false);

    // ソートが変更された時にリフレッシュ
    useEffect(() => {
        refresh();
    }, [current_sort]);

    // アニメーション完了時のハンドラー
    const handleAnimationEnd = (postId: number) => {
        markItemAsOld(postId);
    };

    const handleVote = (postId: number, voteType: 'up' | 'down') => {
        setVotedPosts(prev => ({
            ...prev,
            [postId]: prev[postId] === voteType ? null : voteType
        }));
    };

    const showNotification = (message: string, type: 'success' | 'error' = 'success') => {
        const notification = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
        notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 opacity-0 translate-y-[-10px]`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        requestAnimationFrame(() => {
            notification.classList.remove('opacity-0', 'translate-y-[-10px]');
            notification.classList.add('opacity-100', 'translate-y-0');
        });
        
        setTimeout(() => {
            notification.classList.add('opacity-0', 'translate-y-[-10px]');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    };

    const handleCommunityMembership = async () => {
        if (!user) {
            alert('参加するにはログインが必要です。');
            return;
        }

        if (isMembershipLoading) return;

        setIsMembershipLoading(true);
        try {
            const url = isMember 
                ? `/api/communities/${community.id}/leave`
                : `/api/communities/${community.id}/join`;
            
            const method = isMember ? 'DELETE' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                const data = await response.json();
                setIsMember(data.is_member);
                showNotification(data.message);
            } else {
                const errorData = await response.json();
                showNotification(errorData.message || 'エラーが発生しました', 'error');
            }
        } catch (error) {
            console.error('メンバーシップエラー:', error);
            showNotification('エラーが発生しました', 'error');
        } finally {
            setIsMembershipLoading(false);
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
        const hours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60));
        
        if (hours < 1) return `${Math.floor(hours * 60)}分前`;
        if (hours < 24) return `${hours}時間前`;
        return `${Math.floor(hours / 24)}日前`;
    };

    // バナー色にダークモード対応を追加する関数
    const getDarkModeBannerColor = (bannerColor: string) => {
        const darkModeMap: Record<string, string> = {
            'from-pink-100 to-red-100': 'from-pink-100 to-red-100 dark:from-pink-900 dark:to-red-900',
            'from-blue-100 to-purple-100': 'from-blue-100 to-purple-100 dark:from-blue-900 dark:to-purple-900',
            'from-green-100 to-blue-100': 'from-green-100 to-blue-100 dark:from-green-900 dark:to-blue-900',
            'from-yellow-100 to-orange-100': 'from-yellow-100 to-orange-100 dark:from-yellow-900 dark:to-orange-900',
            'from-gray-100 to-blue-100': 'from-gray-100 to-blue-100 dark:from-gray-700 dark:to-gray-800'
        };
        
        return darkModeMap[bannerColor] || `${bannerColor} dark:from-gray-700 dark:to-gray-800`;
    };

    return (
        <>
            <Head title={`${community.name} - 賛否両論.com`} />
            
            <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
                <CommonHeader user={user} />

                {/* Community Banner */}
                <div className={`bg-gradient-to-r ${getDarkModeBannerColor(community.banner_color)} border-b border-gray-200 dark:border-gray-700`}>
                    <div className="max-w-7xl mx-auto px-8 py-8">
                        <div className="flex items-start justify-between">
                            <div className="flex items-center space-x-4">
                                <div className="w-20 h-20 bg-white dark:bg-gray-800 rounded-full flex items-center justify-center text-4xl shadow-lg">
                                    {community.icon}
                                </div>
                                <div>
                                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{community.name}</h1>
                                    <p className="text-gray-600 dark:text-gray-300 mt-1">{community.description}</p>
                                    <div className="flex items-center space-x-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                        <div className="flex items-center space-x-1">
                                            <Users className="w-4 h-4" />
                                            <span>{community.members} メンバー</span>
                                        </div>
                                        <div className="flex items-center space-x-1">
                                            <Eye className="w-4 h-4" />
                                            <span>{community.online} オンライン</span>
                                        </div>
                                        <div className="flex items-center space-x-1">
                                            <Calendar className="w-4 h-4" />
                                            <span>設立: {community.created_at}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="flex items-center space-x-2">
                                <Button
                                    onClick={handleCommunityMembership}
                                    className={`px-6 text-white ${isMember ? 'bg-gray-500 hover:bg-gray-600' : 'bg-blue-600 hover:bg-blue-700'} ${isMembershipLoading ? 'opacity-75 cursor-not-allowed' : ''}`}
                                    disabled={isMembershipLoading}
                                >
                                    {isMembershipLoading 
                                        ? (isMember ? '脱退中...' : '参加中...') 
                                        : (isMember ? 'メンバー' : '参加する')
                                    }
                                </Button>
                                <Button variant="outline" size="sm" className="border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <Bell className="w-4 h-4" />
                                </Button>
                                <Button variant="outline" size="sm" className="border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <Settings className="w-4 h-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Breadcrumb */}
                <div className="max-w-7xl mx-auto px-8 py-2">
                    <div className="text-sm text-gray-500 dark:text-gray-400">
                        <Link href="/home" className="hover:text-blue-600 dark:hover:text-blue-400">ホーム</Link>
                        <span className="mx-2">›</span>
                        <span className="text-gray-900 dark:text-white">{community.name}</span>
                    </div>
                </div>

                {/* Main Content */}
                <div className="max-w-7xl mx-auto px-8 py-4">
                    <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        {/* Main Feed */}
                        <div className="lg:col-span-3">
                            {/* Create Post Button */}
                            <Card className="bg-white dark:bg-gray-800 mb-4">
                                <CardContent className="p-6 px-8">
                                    <div className="flex items-center space-x-3">
                                        <div className="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                            <UserIcon className="w-4 h-4 text-gray-600 dark:text-gray-300" />
                                        </div>
                                        <input 
                                            type="text" 
                                            placeholder={`${community.name}に投稿する`}
                                            className="flex-1 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                        />
                                        <Button size="sm" className="flex items-center space-x-1 bg-blue-600 hover:bg-blue-700 text-white">
                                            <Plus className="w-4 h-4" />
                                            <span>投稿</span>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Sort Options */}
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 mb-4 p-4">
                                <div className="flex items-center justify-evenly">
                                    <Link 
                                        href={`/community/${community.slug}?sort=hot`}
                                        className={`flex items-center space-x-1 px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm whitespace-nowrap ${
                                            current_sort === 'hot' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        }`}
                                    >
                                        <span>🔥人気</span>
                                    </Link>
                                    <Link 
                                        href={`/community/${community.slug}?sort=new`}
                                        className={`flex items-center space-x-1 px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm whitespace-nowrap ${
                                            current_sort === 'new' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        }`}
                                    >
                                        <span>🆕最新</span>
                                    </Link>
                                    <Link 
                                        href={`/community/${community.slug}?sort=top`}
                                        className={`flex items-center space-x-1 px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm whitespace-nowrap ${
                                            current_sort === 'top' ? 'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                                        }`}
                                    >
                                        <span>⭐殿堂</span>
                                    </Link>
                                </div>
                            </div>

                            {/* Posts */}
                            <div className="space-y-3">
                                {infinitePosts.map((post) => {
                                    const postIsNew = isItemNew(post.id);
                                    return (
                                        <Card 
                                            key={post.id} 
                                            className={`bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600 transition-colors ${
                                                postIsNew ? 'infinite-scroll-item-delayed' : ''
                                            }`}
                                            onAnimationEnd={() => postIsNew && handleAnimationEnd(post.id)}
                                        >
                                            <div className="flex">
                                                {/* Vote Section */}
                                                <div className="flex flex-col items-center p-2 bg-gray-50 dark:bg-gray-700 rounded-l-lg">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className={`p-1 h-auto ${
                                                            votedPosts[post.id] === 'up' ? 'text-orange-500' : 'text-gray-400 dark:text-gray-500 hover:text-orange-500'
                                                        }`}
                                                        onClick={() => handleVote(post.id, 'up')}
                                                    >
                                                        <ArrowUp className="w-5 h-5" />
                                                    </Button>
                                                    <span className={`text-xs font-medium ${
                                                        votedPosts[post.id] === 'up' ? 'text-orange-500' : 
                                                        votedPosts[post.id] === 'down' ? 'text-blue-500' : 'text-gray-700 dark:text-gray-300'
                                                    }`}>
                                                        {formatScore(post.votes.score)}
                                                    </span>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className={`p-1 h-auto ${
                                                            votedPosts[post.id] === 'down' ? 'text-blue-500' : 'text-gray-400 dark:text-gray-500 hover:text-blue-500'
                                                        }`}
                                                        onClick={() => handleVote(post.id, 'down')}
                                                    >
                                                        <ArrowDown className="w-5 h-5" />
                                                    </Button>
                                                </div>

                                                {/* Post Content */}
                                                <div className="flex-1 p-3 px-6">
                                                    {/* Post Header */}
                                                    <div className="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400 mb-2">
                                                        <span>投稿者: {post.author.username}</span>
                                                        <span>•</span>
                                                        <span>{formatTimeAgo(post.created_at)}</span>
                                                        {post.flair && (
                                                            <>
                                                                <span>•</span>
                                                                <Badge variant="outline" className="text-xs border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                                                                    {post.flair}
                                                                </Badge>
                                                            </>
                                                        )}
                                                    </div>

                                                    {/* Post Title */}
                                                    <Link href={`/post/${post.id}`}>
                                                        <h2 className="text-lg font-medium text-gray-900 dark:text-white mb-2 hover:text-blue-600 dark:hover:text-blue-400 cursor-pointer break-words overflow-wrap-anywhere">
                                                            {post.title}
                                                        </h2>
                                                    </Link>

                                                    {/* Post Content */}
                                                    {post.content && (
                                                        <p className="text-gray-700 dark:text-gray-300 text-sm mb-3 line-clamp-3 break-words overflow-wrap-anywhere">{post.content}</p>
                                                    )}

                                                    {/* Post Actions */}
                                                    <div className="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                                                        <Button variant="ghost" size="sm" className="flex items-center space-x-1 h-auto p-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                                                            <MessageSquare className="w-4 h-4" />
                                                            <span>{post.comments_count} コメント</span>
                                                        </Button>
                                                        <Button variant="ghost" size="sm" className="h-auto p-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" title="シェア">
                                                            <Share className="w-4 h-4" />
                                                        </Button>
                                                        <Button variant="ghost" size="sm" className="h-auto p-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" title="保存">
                                                            <Bookmark className="w-4 h-4" />
                                                        </Button>
                                                        <Button variant="ghost" size="sm" className="flex items-center space-x-1 h-auto p-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                                                            <Award className="w-4 h-4" />
                                                            <span>評価</span>
                                                        </Button>
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
                                {!hasMore && infinitePosts.length > 0 && !loading && (
                                    <div className="text-center py-8">
                                        <div className="text-gray-500 dark:text-gray-400 mb-2">
                                            🎉 すべての投稿を読み込みました
                                        </div>
                                        <p className="text-sm text-gray-400 dark:text-gray-500">
                                            合計 {infinitePosts.length} 件の投稿
                                        </p>
                                    </div>
                                )}
                                
                                {/* 初期ローディング中（投稿が0件の場合） */}
                                {infinitePosts.length === 0 && loading && (
                                    <SkeletonLoader count={5} />
                                )}
                            </div>

                            {infinitePosts.length === 0 && !loading && (
                                <Card className="bg-white dark:bg-gray-800">
                                    <CardContent className="p-8 px-12 text-center">
                                        <div className="text-gray-400 dark:text-gray-500 mb-4">
                                            <MessageSquare className="w-16 h-16 mx-auto" />
                                        </div>
                                        <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">まだ投稿がありません</h3>
                                        <p className="text-gray-500 dark:text-gray-400 mb-4">このコミュニティで最初の議論を始めませんか？</p>
                                        <Button className="bg-blue-600 hover:bg-blue-700 text-white">最初の投稿をする</Button>
                                    </CardContent>
                                </Card>
                            )}
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-4">
                            {/* Community Info */}
                            <Card className="bg-white dark:bg-gray-800">
                                <CardHeader className="pb-3">
                                    <div className="flex items-center space-x-2">
                                        <span className="text-xl">{community.icon}</span>
                                        <h3 className="font-semibold text-sm text-gray-900 dark:text-white">コミュニティについて</h3>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-3 px-6">
                                    <p className="text-xs text-gray-600 dark:text-gray-300">{community.description}</p>
                                    
                                    <div className="flex items-center justify-between text-xs">
                                        <div className="text-center">
                                            <div className="font-medium text-gray-900 dark:text-white">{community.members}</div>
                                            <div className="text-gray-500 dark:text-gray-400">メンバー</div>
                                        </div>
                                        <div className="text-center">
                                            <div className="font-medium text-gray-900 dark:text-white">{community.online}</div>
                                            <div className="text-gray-500 dark:text-gray-400">オンライン</div>
                                        </div>
                                    </div>

                                    <div className="text-xs text-gray-500 dark:text-gray-400">
                                        <Calendar className="w-3 h-3 inline mr-1" />
                                        設立: {community.created_at}
                                    </div>

                                    <Button 
                                        onClick={handleCommunityMembership}
                                        className={`w-full text-white ${isMember ? 'bg-gray-500 hover:bg-gray-600' : 'bg-blue-600 hover:bg-blue-700'} ${isMembershipLoading ? 'opacity-75 cursor-not-allowed' : ''}`}
                                        size="sm"
                                        disabled={isMembershipLoading}
                                    >
                                        {isMembershipLoading 
                                            ? (isMember ? '脱退中...' : '参加中...') 
                                            : (isMember ? 'メンバー' : '参加する')
                                        }
                                    </Button>
                                </CardContent>
                            </Card>

                            {/* Community Rules */}
                            <Card className="bg-white dark:bg-gray-800">
                                <CardHeader className="pb-3">
                                    <h3 className="font-semibold text-sm text-gray-900 dark:text-white">コミュニティルール</h3>
                                </CardHeader>
                                <CardContent className="space-y-2 px-6">
                                    {community.rules.map((rule, index) => (
                                        <div key={index} className="text-xs text-gray-600 dark:text-gray-300">
                                            <span className="font-medium">{index + 1}. </span>
                                            {rule}
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>

                            {/* Moderators */}
                            <Card className="bg-white dark:bg-gray-800">
                                <CardHeader className="pb-3">
                                    <h3 className="font-semibold text-sm text-gray-900 dark:text-white">モデレーター</h3>
                                </CardHeader>
                                <CardContent className="space-y-2 px-6">
                                    {community.moderators.map((mod, index) => (
                                        <div key={index} className="flex items-center space-x-2 text-xs">
                                            <UserIcon className="w-3 h-3 text-gray-400 dark:text-gray-500" />
                                            <span className="text-blue-600 dark:text-blue-400">{mod}</span>
                                            <Star className="w-3 h-3 text-yellow-500" />
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
} 