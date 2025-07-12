import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { type User } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { SkeletonLoader } from '@/components/skeleton-loader';
import SortSelector from '@/components/sort-selector';
import SearchBar from '@/components/search-bar';
import CommonHeader from '@/components/common-header';
import { 
    MessageSquare, 
    Share,
    Search as SearchIcon,
    AlertCircle,
    Filter,
    X
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

interface CategoryStat {
    name: string;
    slug: string;
    count: number;
}

interface SearchPageProps {
    query: string;
    posts: Post[];
    total_results: number;
    current_sort: string;
    category_stats?: CategoryStat[];
    user?: User | null;
}

export default function SearchPage({ 
    query: initialQuery,
    posts: initialPosts, 
    total_results,
    current_sort,
    category_stats = [],
    user 
}: SearchPageProps) {
    const [loading, setLoading] = useState(false);
    const [posts, setPosts] = useState<Post[]>(initialPosts);
    const [query, setQuery] = useState(initialQuery);
    const [error, setError] = useState<string | null>(null);
    const [topicVotes, setTopicVotes] = useState<{[key: number]: 'support' | 'oppose' | null}>({});
    const [votingResults, setVotingResults] = useState<{[key: number]: {support: number, oppose: number} | null}>({});
    const [selectedCategory, setSelectedCategory] = useState<string>('');
    const [showFilters, setShowFilters] = useState(false);
    const [categoryStats, setCategoryStats] = useState<CategoryStat[]>(category_stats);

    const performSearch = async (searchQuery: string, sort: string = 'hot', category: string = '') => {
        if (!searchQuery.trim()) return;
        
        setLoading(true);
        setError(null);
        
        try {
            let url = `/api/search?q=${encodeURIComponent(searchQuery)}&sort=${sort}`;
            if (category) {
                url += `&category=${encodeURIComponent(category)}`;
            }
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (response.ok) {
                setPosts(data.posts);
                setQuery(searchQuery);
                setCategoryStats(data.category_stats || []);
                // URLも更新
                const urlParams = new URLSearchParams();
                urlParams.set('q', searchQuery);
                urlParams.set('sort', sort);
                if (category) urlParams.set('category', category);
                window.history.pushState({}, '', `/search?${urlParams.toString()}`);
            } else {
                setError(data.error || '検索中にエラーが発生しました');
            }
        } catch (err) {
            setError('検索中にエラーが発生しました');
        } finally {
            setLoading(false);
        }
    };

    const handleSearch = (newQuery: string) => {
        setSelectedCategory(''); // 新しい検索時はカテゴリフィルターをリセット
        performSearch(newQuery, current_sort);
    };

    const handleSortChange = (newSort: string) => {
        if (query) {
            performSearch(query, newSort, selectedCategory);
        }
    };

    const handleCategoryFilter = (categorySlug: string) => {
        setSelectedCategory(categorySlug);
        if (query) {
            performSearch(query, current_sort, categorySlug);
        }
    };

    const clearCategoryFilter = () => {
        setSelectedCategory('');
        if (query) {
            performSearch(query, current_sort, '');
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
            <Head title={`"${query}" の検索結果 - 賛否両論.com`} />
            
            <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
                <CommonHeader user={user} />
                
                {/* Main Content */}
                <div className="max-w-7xl mx-auto px-4 py-4">
                    <div className="max-w-4xl mx-auto">
                        {/* Search Header */}
                        <div className="mb-6">
                            <div className="mb-4">
                                <SearchBar 
                                    onSearch={handleSearch}
                                    placeholder="議題を検索..."
                                    className="w-full"
                                />
                            </div>
                            
                            {query && (
                                <div className="flex items-center justify-between flex-wrap gap-2">
                                    <div className="flex items-center space-x-2">
                                        <h1 className="text-lg font-medium text-gray-900 dark:text-white">
                                            "{query}" の検索結果
                                        </h1>
                                        <span className="text-sm text-gray-500 dark:text-gray-400">
                                            ({total_results} 件)
                                        </span>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Sort Options & Filters */}
                        {query && posts.length > 0 && (
                            <>
                                <Card className="bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 mb-4">
                                    <CardContent className="p-0">
                                        <SortSelector
                                            currentSort={current_sort}
                                            onSortChange={handleSortChange}
                                        />
                                    </CardContent>
                                </Card>

                                {/* Category Filters */}
                                {categoryStats.length > 0 && (
                                    <Card className="bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 mb-4">
                                        <CardContent className="p-4">
                                            <div className="flex items-center justify-between mb-3">
                                                <h3 className="text-sm font-medium text-gray-900 dark:text-white flex items-center space-x-2">
                                                    <Filter className="w-4 h-4" />
                                                    <span>カテゴリで絞り込み</span>
                                                </h3>
                                                {selectedCategory && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={clearCategoryFilter}
                                                        className="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                                                    >
                                                        クリア
                                                    </Button>
                                                )}
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                {categoryStats.map((category) => (
                                                    <Button
                                                        key={category.slug}
                                                        variant={selectedCategory === category.slug ? "default" : "outline"}
                                                        size="sm"
                                                        onClick={() => handleCategoryFilter(category.slug)}
                                                        className={`text-xs ${
                                                            selectedCategory === category.slug
                                                                ? 'bg-blue-600 text-white'
                                                                : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'
                                                        }`}
                                                    >
                                                        {category.name} ({category.count})
                                                    </Button>
                                                ))}
                                            </div>
                                            {selectedCategory && (
                                                <div className="mt-2 flex items-center space-x-2">
                                                    <Badge variant="secondary" className="text-xs">
                                                        フィルター中: {categoryStats.find(c => c.slug === selectedCategory)?.name}
                                                    </Badge>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={clearCategoryFilter}
                                                        className="h-auto p-1 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300"
                                                    >
                                                        <X className="w-3 h-3" />
                                                    </Button>
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                )}
                            </>
                        )}

                        {/* Results */}
                        {loading ? (
                            <SkeletonLoader count={5} />
                        ) : error ? (
                            <Card className="bg-white dark:bg-gray-800">
                                <CardContent className="p-8 text-center">
                                    <AlertCircle className="w-12 h-12 text-red-500 mx-auto mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        エラーが発生しました
                                    </h3>
                                    <p className="text-gray-500 dark:text-gray-400 mb-4">{error}</p>
                                    <Button onClick={() => handleSearch(query)}>
                                        再試行
                                    </Button>
                                </CardContent>
                            </Card>
                        ) : !query ? (
                            <Card className="bg-white dark:bg-gray-800">
                                <CardContent className="p-8 text-center">
                                    <SearchIcon className="w-16 h-16 text-gray-400 dark:text-gray-500 mx-auto mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        議題を検索
                                    </h3>
                                    <p className="text-gray-500 dark:text-gray-400">
                                        上の検索バーからキーワードを入力して議題を検索してください
                                    </p>
                                </CardContent>
                            </Card>
                        ) : posts.length === 0 ? (
                            <Card className="bg-white dark:bg-gray-800">
                                <CardContent className="p-8 text-center">
                                    <SearchIcon className="w-16 h-16 text-gray-400 dark:text-gray-500 mx-auto mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        検索結果が見つかりません
                                    </h3>
                                    <p className="text-gray-500 dark:text-gray-400 mb-4">
                                        "{query}" に一致する議題は見つかりませんでした
                                    </p>
                                    <div className="text-sm text-gray-400 dark:text-gray-500">
                                        <p>検索のヒント:</p>
                                        <ul className="list-disc list-inside mt-2 text-left max-w-md mx-auto">
                                            <li>別のキーワードを試してください</li>
                                            <li>より一般的な用語を使ってください</li>
                                            <li>スペルを確認してください</li>
                                        </ul>
                                    </div>
                                </CardContent>
                            </Card>
                        ) : (
                            <div className="space-y-3">
                                {posts.map((post) => (
                                    <Card 
                                        key={post.id} 
                                        className="bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors overflow-hidden p-0"
                                    >
                                        <div className="flex">
                                            {/* Score Section */}
                                            <div className="flex flex-col items-center justify-center px-2 bg-gray-200 dark:bg-gray-700 min-w-[50px] flex-shrink-0">
                                                <div className="text-center">
                                                    <div className="text-xs font-medium text-gray-600 dark:text-gray-300">
                                                        {formatScore(post.votes.score)}
                                                    </div>
                                                    <div className="text-xs text-gray-500 dark:text-gray-400">
                                                        スコア
                                                    </div>
                                                </div>
                                            </div>

                                            {/* Post Content */}
                                            <div className="flex-1 p-3 px-6 min-w-0">
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
                                                    <h2 className="text-lg font-medium text-gray-900 dark:text-white mb-2 hover:text-blue-600 dark:hover:text-blue-400 cursor-pointer break-words overflow-wrap-anywhere hyphens-auto max-w-full">
                                                        {post.title}
                                                    </h2>
                                                </Link>

                                                {/* Post Content */}
                                                {post.content && (
                                                    <p className="text-gray-700 dark:text-gray-300 text-sm mb-3 line-clamp-3 break-words overflow-wrap-anywhere hyphens-auto max-w-full">{post.content}</p>
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
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}