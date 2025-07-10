import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { type User } from '@/types';
import CommonHeader from '@/components/common-header';
import { 
    ArrowUp, 
    ArrowDown, 
    MessageSquare, 
    Share, 
    Award, 
    Bookmark, 
    MoreHorizontal,
    User as UserIcon,
    ChevronDown,
    ChevronUp,
    Flag,
    Reply
} from 'lucide-react';

interface Comment {
    id: number;
    author: {
        username: string;
        karma: number;
        cake_day: string;
        stance?: 'support' | 'oppose' | null;
    };
    content: string;
    votes: {
        upvotes: number;
        downvotes: number;
        score: number;
    };
    created_at: string;
    awards: Array<{
        type: string;
        count: number;
    }>;
    gilded: number;
    replies: Comment[];
}

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
    gilded: number;
    saved: boolean;
    hidden: boolean;
}

interface Community {
    id: number;
    name: string;
    slug: string;
    icon: string;
    members: string;
    online: string;
    description: string;
    rules: string[];
    moderators: string[];
    created_at: string;
    is_member: boolean;
}

interface RelatedTopic {
    id: number;
    title: string;
    author: {
        username: string;
    };
    created_at: string;
    score: number;
}

interface PostShowProps {
    post: Post;
    comments: Comment[];
    community: Community;
    voting_results: {
        support_votes: number;
        oppose_votes: number;
        total_votes: number;
        support_percentage: number;
        oppose_percentage: number;
    };
    current_user_vote?: 'support' | 'oppose' | null;
    isSaved?: boolean;
    related_topics: RelatedTopic[];
    user?: User | null;
}

export default function PostShow({ post, comments, community, voting_results, current_user_vote, isSaved: initialIsSaved = false, related_topics, user }: PostShowProps) {
    const [votedComments, setVotedComments] = useState<Record<number, 'up' | 'down' | null>>({});
    const [collapsedComments, setCollapsedComments] = useState<Record<number, boolean>>({});
    const [expandedReplies, setExpandedReplies] = useState<Record<number, boolean>>({});
    const [commentSort, setCommentSort] = useState('best');
    const [newComment, setNewComment] = useState('');
    const [replyingTo, setReplyingTo] = useState<number | null>(null);
    const [replyText, setReplyText] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [allComments, setAllComments] = useState<Comment[]>(comments);
    const [topicVote, setTopicVote] = useState<'support' | 'oppose' | null>(current_user_vote || null);
    const [showVotePrompt, setShowVotePrompt] = useState(!current_user_vote);
    const [isSaved, setIsSaved] = useState(initialIsSaved);
    const [savingState, setSavingState] = useState<'idle' | 'saving' | 'unsaving'>('idle');
    const [currentTopicIndex, setCurrentTopicIndex] = useState(0);
    const [isTransitioning, setIsTransitioning] = useState(false);
    const [isMember, setIsMember] = useState(community.is_member);
    const [isMembershipLoading, setIsMembershipLoading] = useState(false);

    const handleTopicNavigation = (direction: 'up' | 'down') => {
        if (isTransitioning || related_topics.length === 0) return;
        
        let nextIndex;
        if (direction === 'up') {
            nextIndex = currentTopicIndex > 0 ? currentTopicIndex - 1 : related_topics.length - 1;
        } else {
            nextIndex = currentTopicIndex < related_topics.length - 1 ? currentTopicIndex + 1 : 0;
        }
        
        const nextTopic = related_topics[nextIndex];
        if (nextTopic) {
            setIsTransitioning(true);
            setCurrentTopicIndex(nextIndex);
            
            // アニメーション付きでページ遷移
            setTimeout(() => {
                window.location.href = `/post/${nextTopic.id}`;
            }, 150);
        }
    };

    const handleVote = (itemId: number, voteType: 'up' | 'down', isComment = false) => {
        if (isComment) {
            setVotedComments(prev => ({
                ...prev,
                [itemId]: prev[itemId] === voteType ? null : voteType
            }));
        }
        // Post voting functionality can be implemented here if needed
    };

    const toggleComment = (commentId: number) => {
        setCollapsedComments(prev => ({
            ...prev,
            [commentId]: !prev[commentId]
        }));
    };

    const toggleReplies = (commentId: number) => {
        setExpandedReplies(prev => ({
            ...prev,
            [commentId]: !prev[commentId]
        }));
    };

    const showNotification = (message: string, type: 'success' | 'error' = 'success') => {
        const notification = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
        notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 opacity-0 translate-y-[-10px]`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        // アニメーション付きで表示
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

    const handleSaveToggle = async () => {
        console.log('Save button clicked!', { 
            userId: user?.id, 
            postId: post.id, 
            isSaved, 
            savingState 
        });

        if (!user) {
            console.log('User not authenticated');
            alert('保存するにはログインが必要です。');
            return;
        }

        if (savingState !== 'idle') {
            console.log('Already processing, ignoring click');
            return;
        }

        try {
            if (isSaved) {
                console.log('Unsaving post...');
                setSavingState('unsaving');
                const response = await fetch(`/topics/${post.id}/save`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                });
                
                if (response.ok) {
                    console.log('Unsave successful');
                    setIsSaved(false);
                    showNotification('保存を解除しました');
                } else {
                    console.error('Unsave failed:', response.status);
                    showNotification('保存解除に失敗しました', 'error');
                }
            } else {
                console.log('Saving post...');
                setSavingState('saving');
                const response = await fetch(`/topics/${post.id}/save`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                });
                
                if (response.ok) {
                    console.log('Save successful');
                    setIsSaved(true);
                    showNotification('議題を保存しました');
                } else {
                    console.error('Save failed:', response.status);
                    showNotification('保存に失敗しました', 'error');
                }
            }
        } catch (error: unknown) {
            console.error('Save toggle failed:', error);
            showNotification(
                isSaved ? '保存解除に失敗しました' : '保存に失敗しました',
                'error'
            );
        } finally {
            setSavingState('idle');
        }
    };

    const handleTopicVote = async (stance: 'support' | 'oppose') => {
        try {
            const response = await fetch(`/topics/${post.id}/vote`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ stance }),
            });

            if (response.ok) {
                setTopicVote(stance);
                setShowVotePrompt(false);
                
                const result = await response.json();
                // 匿名投票の場合はメッセージ表示
                if (result.is_anonymous) {
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

    const submitComment = async () => {
        if (!newComment.trim() || isSubmitting) return;
        
        setIsSubmitting(true);
        try {
            const response = await fetch(`/posts/${post.id}/comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    content: newComment,
                }),
            });

            if (response.ok) {
                const newCommentData = await response.json();
                setAllComments(prev => [...prev, newCommentData]);
                setNewComment('');
            } else {
                alert('コメントの投稿に失敗しました。');
            }
        } catch (error) {
            console.error('コメント投稿エラー:', error);
            alert('コメントの投稿に失敗しました。');
        } finally {
            setIsSubmitting(false);
        }
    };

    const submitReply = async (parentId: number) => {
        if (!user) {
            alert('返信するにはログインが必要です。');
            return;
        }
        
        if (!replyText.trim() || isSubmitting) return;
        
        setIsSubmitting(true);
        try {
            const response = await fetch(`/posts/${post.id}/comments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    content: replyText,
                    parent_id: parentId,
                }),
            });

            if (response.ok) {
                const newReplyData = await response.json();
                setAllComments(prev => prev.map(comment => 
                    comment.id === parentId 
                        ? { ...comment, replies: [...comment.replies, newReplyData] }
                        : comment
                ));
                setReplyText('');
                setReplyingTo(null);
                setExpandedReplies(prev => ({ ...prev, [parentId]: true }));
            } else {
                alert('返信の投稿に失敗しました。');
            }
        } catch (error) {
            console.error('返信投稿エラー:', error);
            alert('返信の投稿に失敗しました。');
        } finally {
            setIsSubmitting(false);
        }
    };

    const startReply = (commentId: number) => {
        setReplyingTo(commentId);
        setReplyText('');
    };

    const cancelReply = () => {
        setReplyingTo(null);
        setReplyText('');
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
        const minutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));
        
        if (minutes < 60) return `${minutes}分前`;
        
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}時間前`;
        
        const days = Math.floor(hours / 24);
        return `${days}日前`;
    };

    const renderComment = (comment: Comment, depth: number = 0) => {
        const isCollapsed = collapsedComments[comment.id];
        const marginLeft = Math.min(depth * 20, 100); // 最大100pxまでに制限

        return (
            <div key={comment.id} className="border-l border-gray-200 dark:border-gray-700" style={{ marginLeft: `${marginLeft}px` }}>
                <div className="pl-4 py-2 relative">
                    {/* Comment Header */}
                    <div className="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400 mb-2">
                        <Button
                            variant="ghost"
                            size="sm"
                            className="p-0 h-auto text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                            onClick={() => toggleComment(comment.id)}
                        >
                            {isCollapsed ? <ChevronDown className="w-3 h-3" /> : <ChevronUp className="w-3 h-3" />}
                        </Button>
                        <div className="flex items-center space-x-1">
                            {comment.author.stance && (
                                <span className={`text-xs px-1.5 py-0.5 rounded-md font-medium ${
                                    comment.author.stance === 'support' 
                                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' 
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                                }`}>
                                    {comment.author.stance === 'support' ? '賛成' : '反対'}
                                </span>
                            )}
                            <span className="font-medium text-gray-900 dark:text-gray-100">{comment.author.username}</span>
                        </div>
                        <span className="text-gray-400">•</span>
                        <span>{formatTimeAgo(comment.created_at)}</span>
                        {comment.gilded > 0 && (
                            <>
                                <span className="text-gray-400">•</span>
                                <span className="text-yellow-600">🏆 {comment.gilded}</span>
                            </>
                        )}
                    </div>

                    {!isCollapsed && (
                        <>
                            {/* Comment Content */}
                            <div className="mb-3" style={{ marginLeft: '40px' }}>
                                <p className="text-gray-800 dark:text-gray-200 text-sm whitespace-pre-line leading-relaxed">{comment.content}</p>
                            </div>

                            {/* Comment Actions */}
                            <div className="flex items-center space-x-2 mb-3" style={{ marginLeft: '40px' }}>
                                <div className="flex items-center space-x-1">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className={`p-1 h-auto ${
                                            votedComments[comment.id] === 'up' ? 'text-orange-500' : 'text-gray-400 hover:text-orange-500'
                                        } ${!user ? 'cursor-not-allowed opacity-50' : ''}`}
                                        onClick={() => user && handleVote(comment.id, 'up', true)}
                                        disabled={!user}
                                        title="いいね"
                                    >
                                        <ArrowUp className="w-4 h-4" />
                                    </Button>
                                    <span className={`text-xs font-medium min-w-[20px] text-center ${
                                        votedComments[comment.id] === 'up' ? 'text-orange-500' : 
                                        votedComments[comment.id] === 'down' ? 'text-blue-500' : 'text-gray-700 dark:text-gray-300'
                                    }`}>
                                        {formatScore(comment.votes.score)}
                                    </span>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className={`p-1 h-auto ${
                                            votedComments[comment.id] === 'down' ? 'text-blue-500' : 'text-gray-400 hover:text-blue-500'
                                        } ${!user ? 'cursor-not-allowed opacity-50' : ''}`}
                                        onClick={() => user && handleVote(comment.id, 'down', true)}
                                        disabled={!user}
                                        title="よくない"
                                    >
                                        <ArrowDown className="w-4 h-4" />
                                    </Button>
                                </div>
                                <Button 
                                    variant="ghost" 
                                    size="sm" 
                                    className="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 h-auto p-1"
                                    onClick={() => startReply(comment.id)}
                                    title="返信"
                                >
                                    <Reply className="w-4 h-4" />
                                </Button>
                                <Button 
                                    variant="ghost" 
                                    size="sm" 
                                    className="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 h-auto p-1"
                                    title="シェア"
                                >
                                    <Share className="w-4 h-4" />
                                </Button>
                                <Button 
                                    variant="ghost" 
                                    size="sm" 
                                    className="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 h-auto p-1"
                                    title="報告"
                                >
                                    <Flag className="w-4 h-4" />
                                </Button>
                            </div>

                            {/* Awards */}
                            {comment.awards.length > 0 && (
                                <div className="flex items-center space-x-2 mb-3" style={{ marginLeft: '40px' }}>
                                    {comment.awards.map((award, index) => (
                                        <Badge key={index} variant="outline" className="text-xs">
                                            {award.type} {award.count}
                                        </Badge>
                                    ))}
                                </div>
                            )}

                            {/* Reply Form */}
                            {replyingTo === comment.id && (
                                <div className="mt-3" style={{ 
                                    marginLeft: '40px', 
                                    marginRight: `${Math.max(20, marginLeft + 40)}px` 
                                }}>
                                    <div className="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm">
                                        <div className="text-xs text-gray-600 dark:text-gray-400 mb-2 font-medium">
                                            {comment.author.username}さんに返信
                                        </div>
                                        {!user && (
                                            <div className="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-3 mb-3">
                                                <p className="text-xs text-yellow-700 dark:text-yellow-300">
                                                    返信を投稿するにはログインが必要です。
                                                </p>
                                            </div>
                                        )}
                                        <textarea
                                            className="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 min-h-[80px]"
                                            placeholder={user ? "返信を入力してください..." : "返信を入力してください（投稿にはログインが必要です）"}
                                            rows={3}
                                            value={replyText}
                                            onChange={(e) => setReplyText(e.target.value)}
                                        />
                                        <div className="flex justify-between items-center mt-3">
                                            <div className="text-xs text-gray-500 dark:text-gray-400">
                                                建設的な議論を心がけましょう
                                            </div>
                                            <div className="flex space-x-2">
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm" 
                                                    onClick={cancelReply}
                                                    disabled={isSubmitting}
                                                    className="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                                                >
                                                    キャンセル
                                                </Button>
                                                <Button 
                                                    size="sm" 
                                                    onClick={() => submitReply(comment.id)}
                                                    disabled={!replyText.trim() || isSubmitting}
                                                    className="bg-blue-600 hover:bg-blue-700 text-white"
                                                >
                                                    {isSubmitting ? '投稿中...' : '返信'}
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Replies */}
                            {comment.replies.length > 0 && (
                                <div className="mt-2" style={{ marginLeft: '40px' }}>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 h-auto p-1 mb-2"
                                        onClick={() => toggleReplies(comment.id)}
                                    >
                                        {expandedReplies[comment.id] 
                                            ? `返信を隠す (${comment.replies.length}件)` 
                                            : `返信を表示 (${comment.replies.length}件)`
                                        }
                                    </Button>
                                    {expandedReplies[comment.id] && (
                                        <div className="space-y-1">
                                            {comment.replies.map(reply => renderComment(reply, depth + 1))}
                                        </div>
                                    )}
                                </div>
                            )}
                        </>
                    )}

                    {isCollapsed && (
                        <div className="text-xs text-gray-500 dark:text-gray-400 italic" style={{ marginLeft: '40px' }}>
                            コメントが折りたたまれています ({comment.replies.length > 0 ? `${comment.replies.length}件の返信` : '返信なし'})
                        </div>
                    )}
                </div>
            </div>
        );
    };

    return (
        <>
            <Head title={`${post.title} - 賛否両論.com`} />
            
            <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
                <CommonHeader user={user} />

                {/* Main Content */}
                <div className="max-w-7xl mx-auto px-4 py-4">
                    {/* Breadcrumb */}
                    <div className="mb-4">
                        <Link href="/home">
                            <Button 
                                variant="outline" 
                                size="sm" 
                                className="text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
                            >
                                <ArrowUp className="w-4 h-4 rotate-[-90deg] mr-2" />
                                ホームに戻る
                            </Button>
                        </Link>
                    </div>
                    
                    <div className="grid grid-cols-1 lg:grid-cols-4 gap-4 lg:gap-6">
                        {/* Main Post */}
                        <div className="lg:col-span-3 order-1">
                            <Card className="bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 mb-4 overflow-hidden p-0">
                                {/* Mobile navigation - Show on small screens */}
                                <div className="sm:hidden bg-gray-100 dark:bg-gray-700 p-3 border-b border-gray-200 dark:border-gray-600">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center space-x-3">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className={`p-1.5 h-auto text-gray-500 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors ${
                                                    related_topics.length === 0 ? 'cursor-not-allowed opacity-30' : ''
                                                } ${isTransitioning ? 'cursor-not-allowed' : ''}`}
                                                onClick={() => handleTopicNavigation('up')}
                                                disabled={related_topics.length === 0 || isTransitioning}
                                                title={related_topics.length > 0 ? `前の議題: ${related_topics[currentTopicIndex > 0 ? currentTopicIndex - 1 : related_topics.length - 1]?.title.slice(0, 30)}...` : '他の議題がありません'}
                                            >
                                                <ArrowUp className="w-5 h-5" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className={`p-1.5 h-auto text-gray-500 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors ${
                                                    related_topics.length === 0 ? 'cursor-not-allowed opacity-30' : ''
                                                } ${isTransitioning ? 'cursor-not-allowed' : ''}`}
                                                onClick={() => handleTopicNavigation('down')}
                                                disabled={related_topics.length === 0 || isTransitioning}
                                                title={related_topics.length > 0 ? `次の議題: ${related_topics[currentTopicIndex < related_topics.length - 1 ? currentTopicIndex + 1 : 0]?.title.slice(0, 30)}...` : '他の議題がありません'}
                                            >
                                                <ArrowDown className="w-5 h-5" />
                                            </Button>
                                        </div>
                                        <div className="text-right">
                                            <div className="text-xs font-medium text-gray-600 dark:text-gray-300">
                                                {related_topics.length > 0 ? `${currentTopicIndex + 1}/${related_topics.length + 1}` : '1/1'}
                                            </div>
                                            <div className="text-xs text-gray-500 dark:text-gray-400">
                                                {community.name}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="flex">
                                    {/* Desktop Topic Navigation Section - Hidden on mobile */}
                                    <div className={`hidden sm:flex flex-col items-center justify-center px-3 bg-gray-200 dark:bg-gray-700 min-w-[60px] flex-shrink-0 transition-opacity duration-150 ${isTransitioning ? 'opacity-50' : 'opacity-100'}`}>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className={`p-1 h-auto text-gray-500 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors ${
                                                related_topics.length === 0 ? 'cursor-not-allowed opacity-30' : ''
                                            } ${isTransitioning ? 'cursor-not-allowed' : ''}`}
                                            onClick={() => handleTopicNavigation('up')}
                                            disabled={related_topics.length === 0 || isTransitioning}
                                            title={related_topics.length > 0 ? `前の議題: ${related_topics[currentTopicIndex > 0 ? currentTopicIndex - 1 : related_topics.length - 1]?.title.slice(0, 30)}...` : '他の議題がありません'}
                                        >
                                            <ArrowUp className="w-6 h-6" />
                                        </Button>
                                        <div className="text-center my-1">
                                            <div className={`text-xs font-medium text-gray-600 dark:text-gray-300`}>
                                                {related_topics.length > 0 ? `${currentTopicIndex + 1}/${related_topics.length + 1}` : '1/1'}
                                            </div>
                                            <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {community.name}
                                            </div>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className={`p-1 h-auto text-gray-500 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors ${
                                                related_topics.length === 0 ? 'cursor-not-allowed opacity-30' : ''
                                            } ${isTransitioning ? 'cursor-not-allowed' : ''}`}
                                            onClick={() => handleTopicNavigation('down')}
                                            disabled={related_topics.length === 0 || isTransitioning}
                                            title={related_topics.length > 0 ? `次の議題: ${related_topics[currentTopicIndex < related_topics.length - 1 ? currentTopicIndex + 1 : 0]?.title.slice(0, 30)}...` : '他の議題がありません'}
                                        >
                                            <ArrowDown className="w-6 h-6" />
                                        </Button>
                                    </div>

                                    {/* Post Content */}
                                    <div className="flex-1 p-3 sm:p-4">
                                        {/* Post Header */}
                                        <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-500 dark:text-gray-400 mb-3">
                                            <span className="font-medium text-gray-900 dark:text-white">{post.subreddit}</span>
                                            <span className="hidden sm:inline">•</span>
                                            <span className="text-xs sm:text-sm">投稿者: {post.author.username}</span>
                                            <span className="hidden sm:inline">•</span>
                                            <span className="text-xs sm:text-sm">{formatTimeAgo(post.created_at)}</span>
                                            {post.flair && (
                                                <>
                                                    <span className="hidden sm:inline">•</span>
                                                    <Badge variant="outline" className="text-xs border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">
                                                        {post.flair}
                                                    </Badge>
                                                </>
                                            )}
                                            {post.gilded > 0 && (
                                                <>
                                                    <span className="hidden sm:inline">•</span>
                                                    <span className="text-yellow-600 dark:text-yellow-400 text-xs sm:text-sm">🏆 {post.gilded}</span>
                                                </>
                                            )}
                                        </div>

                                        {/* Post Title */}
                                        <h1 className="text-xl font-medium text-gray-900 dark:text-white mb-4">
                                            {post.title}
                                        </h1>

                                        {/* Post Content */}
                                        {post.content && (
                                            <div className="prose max-w-none mb-4">
                                                <p className="text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed">
                                                    {post.content}
                                                </p>
                                            </div>
                                        )}

                                        {/* Post Image */}
                                        {post.image_url && (
                                            <div className="mb-4">
                                                <img 
                                                    src={post.image_url} 
                                                    alt={post.title}
                                                    className="max-w-full h-auto rounded border"
                                                />
                                            </div>
                                        )}

                                        {/* Awards */}
                                        {post.awards.length > 0 && (
                                            <div className="flex items-center space-x-2 mb-4">
                                                {post.awards.map((award, index) => (
                                                    <Badge key={index} variant="outline" className="text-xs">
                                                        {award.type} {award.count}
                                                    </Badge>
                                                ))}
                                            </div>
                                        )}

                                        {/* Voting Section */}
                                        {showVotePrompt && (
                                            <div className="bg-gradient-to-r from-blue-50 to-red-50 dark:from-blue-900/20 dark:to-red-900/20 p-3 sm:p-4 rounded-lg mb-4 border border-gray-200 dark:border-gray-700">
                                                <h3 className="text-xs sm:text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 leading-tight">
                                                    この議題についてあなたの意見を聞かせてください
                                                </h3>
                                                <div className="flex flex-col sm:flex-row gap-2 sm:gap-3">
                                                    <Button 
                                                        onClick={() => handleTopicVote('support')}
                                                        className="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm py-2"
                                                        size="sm"
                                                    >
                                                        👍 賛成
                                                    </Button>
                                                    <Button 
                                                        onClick={() => handleTopicVote('oppose')}
                                                        className="flex-1 bg-red-600 hover:bg-red-700 text-white text-xs sm:text-sm py-2"
                                                        size="sm"
                                                    >
                                                        👎 反対
                                                    </Button>
                                                </div>
                                            </div>
                                        )}

                                        {/* Vote Result Display */}
                                        {topicVote && (
                                            <div className="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg mb-4 border border-green-200 dark:border-green-800">
                                                <div className="flex items-center justify-between mb-3">
                                                    <span className="text-green-600 dark:text-green-400 text-sm font-medium">
                                                        ✓ あなたの意見: {topicVote === 'support' ? '賛成' : '反対'}
                                                    </span>
                                                    <span className="text-xs text-gray-500 dark:text-gray-400">
                                                        総投票数: {voting_results.total_votes}票
                                                    </span>
                                                </div>
                                                
                                                {/* 投票割合の棒グラフ */}
                                                <div className="space-y-2">
                                                    <div className="flex items-center justify-between text-sm">
                                                        <span className="text-blue-600 dark:text-blue-400 font-medium">
                                                            👍 賛成 {voting_results.support_percentage}%
                                                        </span>
                                                        <span className="text-gray-500">{voting_results.support_votes}票</span>
                                                    </div>
                                                    <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                        <div 
                                                            className="bg-blue-600 h-2 rounded-full transition-all duration-500"
                                                            style={{ width: `${voting_results.support_percentage}%` }}
                                                        ></div>
                                                    </div>
                                                    
                                                    <div className="flex items-center justify-between text-sm">
                                                        <span className="text-red-600 dark:text-red-400 font-medium">
                                                            👎 反対 {voting_results.oppose_percentage}%
                                                        </span>
                                                        <span className="text-gray-500">{voting_results.oppose_votes}票</span>
                                                    </div>
                                                    <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                        <div 
                                                            className="bg-red-600 h-2 rounded-full transition-all duration-500"
                                                            style={{ width: `${voting_results.oppose_percentage}%` }}
                                                        ></div>
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {/* Post Actions */}
                                        <div className="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                            <Button variant="ghost" size="sm" className="flex items-center space-x-1 h-auto p-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                <MessageSquare className="w-4 h-4" />
                                                <span className="text-xs sm:text-sm">{post.comments_count} コメント</span>
                                            </Button>
                                            <Button variant="ghost" size="sm" className="h-auto p-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" title="シェア">
                                                <Share className="w-4 h-4" />
                                            </Button>
                                            {user && (
                                                <Button 
                                                    variant="ghost" 
                                                    size="sm" 
                                                    onClick={handleSaveToggle}
                                                    disabled={savingState !== 'idle'}
                                                    className={`flex items-center space-x-1 h-auto p-1 transition-all duration-200 disabled:pointer-events-none disabled:opacity-50 ${
                                                        isSaved 
                                                            ? 'text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/30' 
                                                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                                                    } ${savingState !== 'idle' ? 'animate-pulse' : ''}`}
                                                >
                                                    <Bookmark className={`w-4 h-4 transition-all duration-200 ${isSaved ? 'fill-current scale-110' : ''} ${savingState !== 'idle' ? 'animate-bounce' : ''}`} />
                                                    <span className="font-medium text-xs sm:text-sm">
                                                        {savingState === 'saving' ? '保存中...' : 
                                                         savingState === 'unsaving' ? '解除中...' : 
                                                         isSaved ? '保存済み' : '保存'}
                                                    </span>
                                                </Button>
                                            )}
                                            <Button variant="ghost" size="sm" className="flex items-center space-x-1 h-auto p-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                <Award className="w-4 h-4" />
                                                <span className="hidden sm:inline text-xs sm:text-sm">評価</span>
                                            </Button>
                                            <Button variant="ghost" size="sm" className="flex items-center space-x-1 h-auto p-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                <MoreHorizontal className="w-4 h-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </Card>

                            {/* Comment Form - Only show if user has voted */}
                            {topicVote && (
                                <Card className="bg-white dark:bg-gray-800 mb-4">
                                    <CardContent className="p-4">
                                        {user ? (
                                            <>
                                                <div className="mb-3">
                                                    <span className="text-sm text-gray-600 dark:text-gray-400">
                                                        {user.name} としてコメント
                                                    </span>
                                                </div>
                                                <textarea
                                                    className="w-full p-3 border border-gray-300 dark:border-gray-600 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"
                                                    placeholder="この議題についてどう思いますか？建設的な議論を心がけましょう。"
                                                    rows={4}
                                                    value={newComment}
                                                    onChange={(e) => setNewComment(e.target.value)}
                                                />
                                                <div className="flex justify-end mt-3">
                                                    <Button 
                                                        size="sm" 
                                                        onClick={submitComment}
                                                        disabled={!newComment.trim() || isSubmitting}
                                                    >
                                                        {isSubmitting ? '投稿中...' : 'コメント投稿'}
                                                    </Button>
                                                </div>
                                            </>
                                        ) : (
                                            <div className="text-center py-6">
                                                <p className="text-gray-600 dark:text-gray-400 mb-4">
                                                    コメントを投稿するにはログインが必要です
                                                </p>
                                                <div className="flex justify-center space-x-2">
                                                    <Button variant="outline" size="sm">
                                                        ログイン
                                                    </Button>
                                                    <Button size="sm">
                                                        アカウント作成
                                                    </Button>
                                                </div>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            )}

                            {/* Comment Sort - Only show if user has voted */}
                            {topicVote && (
                                <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 mb-4 p-3">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="text-xs sm:text-sm text-gray-600 dark:text-gray-400">並び替え:</span>
                                        <Button 
                                            variant={commentSort === 'best' ? 'default' : 'ghost'} 
                                            size="sm"
                                            onClick={() => setCommentSort('best')}
                                            className="text-xs sm:text-sm"
                                        >
                                            ベスト
                                        </Button>
                                        <Button 
                                            variant={commentSort === 'top' ? 'default' : 'ghost'} 
                                            size="sm"
                                            onClick={() => setCommentSort('top')}
                                            className="text-xs sm:text-sm"
                                        >
                                            人気順
                                        </Button>
                                        <Button 
                                            variant={commentSort === 'new' ? 'default' : 'ghost'} 
                                            size="sm"
                                            onClick={() => setCommentSort('new')}
                                            className="text-xs sm:text-sm"
                                        >
                                            新着順
                                        </Button>
                                        <Button 
                                            variant={commentSort === 'controversial' ? 'default' : 'ghost'} 
                                            size="sm"
                                            onClick={() => setCommentSort('controversial')}
                                            className="text-xs sm:text-sm hidden sm:inline-flex"
                                        >
                                            議論の的
                                        </Button>
                                    </div>
                                </div>
                            )}

                            {/* Comments */}
                            <div className="relative">
                                <Card className="bg-white dark:bg-gray-800">
                                    <CardContent className={`${allComments.length === 0 ? 'p-12' : 'p-0'}`}>
                                        {allComments.length > 0 ? (
                                            allComments.map(comment => renderComment(comment))
                                        ) : (
                                            <div className="text-center py-8">
                                                <div className="text-4xl mb-4">💬</div>
                                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                                    まだコメントがありません
                                                </h3>
                                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                                    あなたが最初のコメントを投稿しませんか？
                                                </p>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>

                                                {/* Blur overlay when not voted */}
                {!topicVote && (
                    <div className="absolute inset-0 backdrop-blur-md bg-white/30 dark:bg-gray-900/30 rounded-lg flex items-center justify-center min-h-[200px]">
                        <div className="text-center p-6 bg-white/95 dark:bg-gray-800/95 rounded-lg border border-gray-200 dark:border-gray-600 backdrop-blur-sm shadow-lg max-w-sm mx-4 w-full">
                            <div className="text-3xl mb-3">🗳️</div>
                            <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3 leading-tight">
                                意見を表明してコメントを見る
                            </h3>
                            <p className="text-xs text-gray-600 dark:text-gray-400 leading-relaxed px-2">
                                この議題について賛成か反対かを選んでから、他の人のコメントを見ることができます
                            </p>
                        </div>
                    </div>
                )}
                            </div>
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-4 order-2">
                            {/* Community Info */}
                            <Card className="bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                <CardHeader className="pb-3">
                                    <div className="flex items-center space-x-2">
                                        <span className="text-2xl">{community.icon}</span>
                                        <h3 className="font-semibold text-sm text-gray-900 dark:text-white">{community.name}</h3>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-3">
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

                                    <Button 
                                        className={`w-full ${isMember 
                                            ? 'bg-gray-600 hover:bg-gray-700 text-white' 
                                            : 'bg-blue-600 hover:bg-blue-700 text-white'
                                        } ${isMembershipLoading ? 'opacity-75 cursor-not-allowed' : ''}`}
                                        size="sm"
                                        onClick={isMember ? () => window.location.href = `/community/${community.slug}` : handleCommunityMembership}
                                        disabled={isMembershipLoading}
                                    >
                                        {isMembershipLoading 
                                            ? (isMember ? '脱退中...' : '参加中...') 
                                            : (isMember ? 'このコミュニティを見る' : 'コミュニティに参加')
                                        }
                                    </Button>
                                </CardContent>
                            </Card>

                            {/* Community Rules */}
                            <Card className="bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                <CardHeader className="pb-3">
                                    <h3 className="font-semibold text-sm text-gray-900 dark:text-white">コミュニティルール</h3>
                                </CardHeader>
                                <CardContent className="space-y-2">
                                    {community.rules.map((rule, index) => (
                                        <div key={index} className="text-xs text-gray-600 dark:text-gray-300">
                                            <span className="font-medium">{index + 1}. </span>
                                            {rule}
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>

                            {/* Moderators */}
                            <Card className="bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700">
                                <CardHeader className="pb-3">
                                    <h3 className="font-semibold text-sm text-gray-900 dark:text-white">モデレーター</h3>
                                </CardHeader>
                                <CardContent className="space-y-2">
                                    {community.moderators.map((mod, index) => (
                                        <div key={index} className="flex items-center space-x-2 text-xs">
                                            <UserIcon className="w-3 h-3 text-gray-400 dark:text-gray-500" />
                                            <span className="text-blue-600 dark:text-blue-400">{mod}</span>
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
