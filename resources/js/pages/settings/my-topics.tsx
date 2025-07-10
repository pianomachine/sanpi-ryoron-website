import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import axios from 'axios';

import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Trash2, MessageSquare, Eye } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'My Topics',
        href: '/settings/my-topics',
    },
];

interface Topic {
    id: number;
    title: string;
    content: string;
    stance: 'support' | 'oppose' | 'neutral';
    created_at: string;
    views_count: number;
    comments_count: number;
    user: {
        id: number;
        name: string;
    };
    community?: {
        id: number;
        name: string;
        slug: string;
    };
}

interface PaginatedTopics {
    data: Topic[];
    current_page: number;
    last_page: number;
    total: number;
}

export default function MyTopics() {
    const [topics, setTopics] = useState<PaginatedTopics | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchTopics();
    }, []);

    const fetchTopics = async () => {
        try {
            const response = await axios.get('/profile/topics');
            setTopics(response.data);
        } catch (error) {
            console.error('Failed to fetch topics:', error);
        } finally {
            setLoading(false);
        }
    };

    const deleteTopic = async (topicId: number) => {
        if (!confirm('この議題を削除しますか？')) return;

        try {
            await axios.delete(`/topics/${topicId}`);
            fetchTopics(); // リロード
        } catch (error) {
            console.error('Failed to delete topic:', error);
            alert('削除に失敗しました');
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('ja-JP', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const getStanceBadge = (stance: string) => {
        const stanceMap = {
            support: { label: '賛成', className: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
            oppose: { label: '反対', className: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
            neutral: { label: '中立', className: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' },
        };
        const config = stanceMap[stance as keyof typeof stanceMap] || stanceMap.neutral;
        return <Badge className={config.className}>{config.label}</Badge>;
    };

    if (loading) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="My Topics" />
                <SettingsLayout>
                    <div className="space-y-6">
                        <HeadingSmall title="投稿した議題" description="あなたが投稿した議題の一覧です" />
                        <div className="text-center py-8">読み込み中...</div>
                    </div>
                </SettingsLayout>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Topics" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="投稿した議題" description="あなたが投稿した議題の一覧です" />

                    {topics && topics.data.length > 0 ? (
                        <div className="space-y-4">
                            {topics.data.map((topic) => (
                                <Card key={topic.id} className="hover:shadow-md transition-shadow">
                                    <CardHeader>
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <CardTitle className="text-lg">
                                                    <a 
                                                        href={`/post/${topic.id}`}
                                                        className="hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                                    >
                                                        {topic.title}
                                                    </a>
                                                </CardTitle>
                                                <div className="flex items-center gap-2 mt-2">
                                                    {getStanceBadge(topic.stance)}
                                                    {topic.community && (
                                                        <Badge variant="outline">
                                                            {topic.community.name}
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => deleteTopic(topic.id)}
                                                className="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </Button>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-sm text-muted-foreground mb-3 line-clamp-2">
                                            {topic.content}
                                        </p>
                                        <div className="flex items-center justify-between text-sm text-muted-foreground">
                                            <span>投稿日: {formatDate(topic.created_at)}</span>
                                            <div className="flex items-center gap-4">
                                                <div className="flex items-center gap-1">
                                                    <Eye className="w-4 h-4" />
                                                    <span>{topic.views_count}</span>
                                                </div>
                                                <div className="flex items-center gap-1">
                                                    <MessageSquare className="w-4 h-4" />
                                                    <span>{topic.comments_count}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    ) : (
                        <Card>
                            <CardContent className="text-center py-8">
                                <p className="text-muted-foreground">まだ議題を投稿していません</p>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
} 