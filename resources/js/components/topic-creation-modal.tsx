import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { type User } from '@/types';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { X, Loader2, Plus, Check, Search, ChevronDown } from 'lucide-react';

interface TopicCreationModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    user?: User | null;
}

interface Community {
    id: number;
    name: string;
    slug: string;
    icon: string;
    description: string;
    members_count: number;
}

const stanceOptions = [
    { value: 'support', label: '賛成', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' },
    { value: 'oppose', label: '反対', color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' },
    { value: 'neutral', label: '中立', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' },
];

const flairOptions = [
    '議論',
    '体験談',
    '質問',
    '提案',
    'ニュース',
    'アンケート',
];

export default function TopicCreationModal({ open, onOpenChange, user }: TopicCreationModalProps) {
    const [formData, setFormData] = useState({
        community_id: '',
        title: '',
        content: '',
        stance: '',
        flair: '',
    });
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    
    // Community関連の状態
    const [communities, setCommunities] = useState<Community[]>([]);
    const [isLoadingCommunities, setIsLoadingCommunities] = useState(false);
    const [showCommunityForm, setShowCommunityForm] = useState(false);
    const [newCommunityData, setNewCommunityData] = useState({
        name: '',
        icon: '',
        description: '',
    });
    const [isCreatingCommunity, setIsCreatingCommunity] = useState(false);
    const [communityErrors, setCommunityErrors] = useState<Record<string, string>>({});
    
    // 検索機能関連の状態
    const [searchQuery, setSearchQuery] = useState('');
    const [showCommunityDropdown, setShowCommunityDropdown] = useState(false);
    const [selectedCommunityIndex, setSelectedCommunityIndex] = useState(-1);
    const [debouncedSearchQuery, setDebouncedSearchQuery] = useState('');

    // デバウンス効果
    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedSearchQuery(searchQuery);
        }, 300);

        return () => clearTimeout(timer);
    }, [searchQuery]);

    // フィルタリングされたコミュニティリスト
    const filteredCommunities = communities.filter(community =>
        community.name.toLowerCase().includes(debouncedSearchQuery.toLowerCase()) ||
        community.description.toLowerCase().includes(debouncedSearchQuery.toLowerCase())
    );

    // 検索結果のハイライト機能
    const highlightSearchTerm = (text: string, searchTerm: string) => {
        if (!searchTerm) return text;
        
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        const parts = text.split(regex);
        
        return parts.map((part, index) => 
            regex.test(part) ? (
                <span key={index} className="bg-yellow-200 dark:bg-yellow-800 text-yellow-900 dark:text-yellow-200">
                    {part}
                </span>
            ) : part
        );
    };

    // コミュニティ一覧を取得
    const fetchCommunities = async () => {
        setIsLoadingCommunities(true);
        try {
            const response = await fetch('/api/communities');
            if (response.ok) {
                const data = await response.json();
                setCommunities(data);
            }
        } catch (error) {
            console.error('コミュニティ取得エラー:', error);
        } finally {
            setIsLoadingCommunities(false);
        }
    };

    // モーダルが開かれた時にコミュニティを取得
    useEffect(() => {
        if (open && communities.length === 0) {
            fetchCommunities();
        }
    }, [open, communities.length]);

    // コミュニティ選択時の処理
    const handleCommunitySelect = (communityId: string) => {
        setFormData(prev => ({ ...prev, community_id: communityId }));
        setShowCommunityDropdown(false);
        setSearchQuery('');
        setSelectedCommunityIndex(-1);
        if (errors.community_id) {
            setErrors(prev => ({ ...prev, community_id: '' }));
        }
    };

    // キーボードナビゲーション
    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (!showCommunityDropdown) return;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                setSelectedCommunityIndex(prev => 
                    prev < filteredCommunities.length - 1 ? prev + 1 : 0
                );
                break;
            case 'ArrowUp':
                e.preventDefault();
                setSelectedCommunityIndex(prev => 
                    prev > 0 ? prev - 1 : filteredCommunities.length - 1
                );
                break;
            case 'Enter':
                e.preventDefault();
                if (selectedCommunityIndex >= 0 && filteredCommunities[selectedCommunityIndex]) {
                    handleCommunitySelect(filteredCommunities[selectedCommunityIndex].id.toString());
                }
                break;
            case 'Escape':
                setShowCommunityDropdown(false);
                setSelectedCommunityIndex(-1);
                break;
        }
    };

    // 選択されたアイテムをビューにスクロール
    useEffect(() => {
        if (selectedCommunityIndex >= 0 && showCommunityDropdown) {
            const element = document.getElementById(`community-option-${selectedCommunityIndex}`);
            if (element) {
                element.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        }
    }, [selectedCommunityIndex, showCommunityDropdown]);

    const handleInputChange = (field: string, value: string) => {
        setFormData(prev => ({ ...prev, [field]: value }));
        if (errors[field]) {
            setErrors(prev => ({ ...prev, [field]: '' }));
        }
    };

    const handleCommunityInputChange = (field: string, value: string) => {
        setNewCommunityData(prev => ({ ...prev, [field]: value }));
        if (communityErrors[field]) {
            setCommunityErrors(prev => ({ ...prev, [field]: '' }));
        }
    };

    const validateForm = () => {
        const newErrors: Record<string, string> = {};
        
        if (!formData.community_id) newErrors.community_id = 'コミュニティを選択してください';
        if (!formData.title.trim()) newErrors.title = 'タイトルを入力してください';
        if (formData.title.trim().length < 10) newErrors.title = 'タイトルは10文字以上で入力してください';
        if (!formData.content.trim()) newErrors.content = '内容を入力してください';
        if (formData.content.trim().length < 20) newErrors.content = '内容は20文字以上で入力してください';
        if (!formData.stance) newErrors.stance = '立場を選択してください';

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const validateCommunityForm = () => {
        const newErrors: Record<string, string> = {};
        
        if (!newCommunityData.name.trim()) newErrors.name = 'コミュニティ名を入力してください';
        if (newCommunityData.name.trim().length < 3) newErrors.name = 'コミュニティ名は3文字以上で入力してください';
        if (!newCommunityData.icon.trim()) newErrors.icon = 'アイコンを入力してください';
        if (!newCommunityData.description.trim()) newErrors.description = '説明を入力してください';
        if (newCommunityData.description.trim().length < 10) newErrors.description = '説明は10文字以上で入力してください';

        setCommunityErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleCreateCommunity = async () => {
        if (!validateCommunityForm()) return;

        setIsCreatingCommunity(true);
        try {
            const response = await fetch('/api/communities', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(newCommunityData),
            });

            if (response.ok) {
                const newCommunity = await response.json();
                setCommunities(prev => [newCommunity, ...prev]);
                setFormData(prev => ({ ...prev, community_id: newCommunity.id.toString() }));
                setShowCommunityForm(false);
                setNewCommunityData({ name: '', icon: '', description: '' });
                setCommunityErrors({});
            } else {
                const errorData = await response.json();
                if (errorData.errors) {
                    setCommunityErrors(errorData.errors);
                }
            }
        } catch (error) {
            console.error('コミュニティ作成エラー:', error);
        } finally {
            setIsCreatingCommunity(false);
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!user) {
            alert('ログインが必要です');
            return;
        }

        if (!validateForm()) return;

        setIsSubmitting(true);

        try {
            router.post('/topics', formData, {
                onSuccess: () => {
                    // 投稿成功時
                    onOpenChange(false);
                    setFormData({
                        community_id: '',
                        title: '',
                        content: '',
                        stance: '',
                        flair: '',
                    });
                    setErrors({});
                },
                onError: (errors) => {
                    setErrors(errors);
                },
                onFinish: () => {
                    setIsSubmitting(false);
                }
            });
        } catch (error) {
            console.error('投稿エラー:', error);
            setIsSubmitting(false);
        }
    };

    const selectedCommunity = communities.find(c => c.id.toString() === formData.community_id);
    const selectedStance = stanceOptions.find(s => s.value === formData.stance);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[600px] max-h-[80vh] overflow-y-auto bg-white dark:bg-gray-800">
                <DialogHeader>
                    <DialogTitle className="text-gray-900 dark:text-white">新しい議題を投稿</DialogTitle>
                    <DialogDescription className="text-gray-600 dark:text-gray-300">
                        コミュニティで議論を始めましょう。あなたの意見や体験をシェアしてください。
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* コミュニティ選択・作成 */}
                    <div className="space-y-2">
                        <Label htmlFor="community" className="text-gray-900 dark:text-white">コミュニティ *</Label>
                        
                        {!showCommunityForm ? (
                            <div className="space-y-2">
                                {/* 検索可能なコミュニティセレクター */}
                                <div className="relative">
                                    <div className="flex items-center">
                                        <div 
                                            className="flex-1 relative cursor-pointer"
                                            onClick={() => setShowCommunityDropdown(!showCommunityDropdown)}
                                        >
                                            <Input
                                                value={searchQuery || (selectedCommunity ? selectedCommunity.name : '')}
                                                onChange={(e) => {
                                                    setSearchQuery(e.target.value);
                                                    setShowCommunityDropdown(true);
                                                    setSelectedCommunityIndex(-1);
                                                }}
                                                onFocus={() => setShowCommunityDropdown(true)}
                                                onKeyDown={handleKeyDown}
                                                placeholder={isLoadingCommunities ? "読み込み中..." : "コミュニティを検索してください"}
                                                className="pr-10 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                                                readOnly={!showCommunityDropdown && !!selectedCommunity}
                                            />
                                            <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                {showCommunityDropdown ? (
                                                    <Search className="w-4 h-4 text-gray-400" />
                                                ) : (
                                                    <ChevronDown className="w-4 h-4 text-gray-400" />
                                                )}
                                            </div>
                                        </div>
                                        {selectedCommunity && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => {
                                                    setFormData(prev => ({ ...prev, community_id: '' }));
                                                    setSearchQuery('');
                                                    setShowCommunityDropdown(true);
                                                }}
                                                className="ml-2 p-2 h-auto text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                            >
                                                <X className="w-4 h-4" />
                                            </Button>
                                        )}
                                    </div>

                                    {/* ドロップダウンメニュー */}
                                    {showCommunityDropdown && (
                                        <div className="absolute z-50 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                            {isLoadingCommunities ? (
                                                <div className="p-4 text-center text-gray-500 dark:text-gray-400">
                                                    <Loader2 className="w-4 h-4 animate-spin mx-auto mb-2" />
                                                    読み込み中...
                                                </div>
                                            ) : searchQuery !== debouncedSearchQuery ? (
                                                <div className="p-4 text-center text-gray-500 dark:text-gray-400">
                                                    <Search className="w-4 h-4 animate-pulse mx-auto mb-2" />
                                                    検索中...
                                                </div>
                                            ) : filteredCommunities.length > 0 ? (
                                                filteredCommunities.map((community, index) => (
                                                    <div
                                                        key={community.id}
                                                        id={`community-option-${index}`}
                                                        onClick={() => handleCommunitySelect(community.id.toString())}
                                                        className={`px-4 py-3 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 border-b border-gray-100 dark:border-gray-600 last:border-b-0 ${
                                                            selectedCommunityIndex === index ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300' : 'text-gray-900 dark:text-white'
                                                        }`}
                                                    >
                                                        <div className="flex items-center space-x-3">
                                                            <span className="text-xl">{community.icon}</span>
                                                            <div className="flex-1 min-w-0">
                                                                <div className="font-medium text-sm truncate">
                                                                    {highlightSearchTerm(community.name, debouncedSearchQuery)}
                                                                </div>
                                                                <div className="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                                    {highlightSearchTerm(community.description, debouncedSearchQuery)} • {community.members_count}人
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))
                                            ) : (
                                                <div className="p-4 text-center text-gray-500 dark:text-gray-400">
                                                    <div className="text-sm mb-2">
                                                        {searchQuery ? `"${searchQuery}"に一致するコミュニティが見つかりません` : 'コミュニティがありません'}
                                                    </div>
                                                    {searchQuery && searchQuery.length >= 3 && (
                                                        <div className="text-xs text-gray-400 dark:text-gray-500 mb-3">
                                                            新しいコミュニティを作成するか、検索キーワードを変更してください
                                                        </div>
                                                    )}
                                                    {searchQuery && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => {
                                                                setShowCommunityForm(true);
                                                                setShowCommunityDropdown(false);
                                                                setNewCommunityData(prev => ({ 
                                                                    ...prev, 
                                                                    name: searchQuery.startsWith('🔍') ? searchQuery.slice(2).trim() : searchQuery 
                                                                }));
                                                            }}
                                                            className="text-xs bg-blue-50 hover:bg-blue-100 border-blue-200 text-blue-700 dark:bg-blue-950 dark:hover:bg-blue-900 dark:border-blue-800 dark:text-blue-300"
                                                        >
                                                            <Plus className="w-3 h-3 mr-1" />
                                                            「{searchQuery}」でコミュニティを作成
                                                        </Button>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {/* クリック外の検出 */}
                                    {showCommunityDropdown && (
                                        <div 
                                            className="fixed inset-0 z-40" 
                                            onClick={() => {
                                                setShowCommunityDropdown(false);
                                                setSelectedCommunityIndex(-1);
                                                if (!selectedCommunity) {
                                                    setSearchQuery('');
                                                }
                                            }}
                                        />
                                    )}
                                </div>
                                
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setShowCommunityForm(true)}
                                    className="w-full border-dashed border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                                >
                                    <Plus className="w-4 h-4 mr-2" />
                                    新しいコミュニティを作成
                                </Button>
                            </div>
                        ) : (
                            <Card className="border-gray-300 dark:border-gray-600">
                                <CardHeader className="pb-3">
                                    <div className="flex items-center justify-between">
                                        <h4 className="text-sm font-medium text-gray-900 dark:text-white">新しいコミュニティを作成</h4>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => {
                                                setShowCommunityForm(false);
                                                setCommunityErrors({});
                                                setNewCommunityData({ name: '', icon: '', description: '' });
                                            }}
                                            className="h-auto p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                        >
                                            <X className="w-4 h-4" />
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div>
                                        <Label htmlFor="community-name" className="text-sm text-gray-700 dark:text-gray-300">コミュニティ名 *</Label>
                                        <Input
                                            id="community-name"
                                            value={newCommunityData.name}
                                            onChange={(e) => handleCommunityInputChange('name', e.target.value)}
                                            placeholder="例: 🍔 グルメ議論"
                                            className="mt-1"
                                            maxLength={50}
                                        />
                                        {communityErrors.name && <p className="text-red-500 text-xs mt-1">{communityErrors.name}</p>}
                                    </div>
                                    
                                    <div>
                                        <Label htmlFor="community-icon" className="text-sm text-gray-700 dark:text-gray-300">アイコン（絵文字） *</Label>
                                        <Input
                                            id="community-icon"
                                            value={newCommunityData.icon}
                                            onChange={(e) => handleCommunityInputChange('icon', e.target.value)}
                                            placeholder="🍔"
                                            className="mt-1"
                                            maxLength={2}
                                        />
                                        {communityErrors.icon && <p className="text-red-500 text-xs mt-1">{communityErrors.icon}</p>}
                                    </div>
                                    
                                    <div>
                                        <Label htmlFor="community-description" className="text-sm text-gray-700 dark:text-gray-300">説明 *</Label>
                                        <textarea
                                            id="community-description"
                                            value={newCommunityData.description}
                                            onChange={(e) => handleCommunityInputChange('description', e.target.value)}
                                            placeholder="このコミュニティで議論される内容について説明してください"
                                            className="mt-1 w-full h-20 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white resize-none"
                                            maxLength={200}
                                        />
                                        <div className="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            <span>{communityErrors.description || ' '}</span>
                                            <span>{newCommunityData.description.length}/200</span>
                                        </div>
                                    </div>
                                    
                                    <div className="flex space-x-2 pt-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => {
                                                setShowCommunityForm(false);
                                                setCommunityErrors({});
                                                setNewCommunityData({ name: '', icon: '', description: '' });
                                            }}
                                            disabled={isCreatingCommunity}
                                            className="flex-1"
                                        >
                                            キャンセル
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            onClick={handleCreateCommunity}
                                            disabled={isCreatingCommunity}
                                            className="flex-1 bg-blue-600 hover:bg-blue-700 text-white"
                                        >
                                            {isCreatingCommunity ? (
                                                <>
                                                    <Loader2 className="w-3 h-3 mr-1 animate-spin" />
                                                    作成中...
                                                </>
                                            ) : (
                                                <>
                                                    <Check className="w-3 h-3 mr-1" />
                                                    作成
                                                </>
                                            )}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                        
                        {errors.community_id && <p className="text-red-500 text-sm">{errors.community_id}</p>}
                    </div>

                    {/* タイトル */}
                    <div className="space-y-2">
                        <Label htmlFor="title" className="text-gray-900 dark:text-white">タイトル *</Label>
                        <Input
                            id="title"
                            value={formData.title}
                            onChange={(e) => handleInputChange('title', e.target.value)}
                            placeholder="議題のタイトルを入力してください（10文字以上）"
                            className="bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                            maxLength={200}
                        />
                        <div className="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>{errors.title || ' '}</span>
                            <span>{formData.title.length}/200</span>
                        </div>
                    </div>

                    {/* 内容 */}
                    <div className="space-y-2">
                        <Label htmlFor="content" className="text-gray-900 dark:text-white">内容 *</Label>
                        <textarea
                            id="content"
                            value={formData.content}
                            onChange={(e) => handleInputChange('content', e.target.value)}
                            placeholder="詳細な内容、あなたの意見、体験談などを入力してください（20文字以上）"
                            className="w-full h-32 px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                            maxLength={5000}
                        />
                        <div className="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>{errors.content || ' '}</span>
                            <span>{formData.content.length}/5000</span>
                        </div>
                    </div>

                    {/* 立場選択 */}
                    <div className="space-y-2">
                        <Label className="text-gray-900 dark:text-white">あなたの立場 *</Label>
                        <div className="flex space-x-3">
                            {stanceOptions.map((stance) => (
                                <button
                                    key={stance.value}
                                    type="button"
                                    onClick={() => handleInputChange('stance', stance.value)}
                                    className={`px-4 py-2 rounded-full text-sm font-medium transition-colors ${
                                        formData.stance === stance.value 
                                            ? stance.color + ' ring-2 ring-blue-500' 
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                    }`}
                                >
                                    {stance.label}
                                </button>
                            ))}
                        </div>
                        {errors.stance && <p className="text-red-500 text-sm">{errors.stance}</p>}
                    </div>

                    {/* フレア選択（任意） */}
                    <div className="space-y-2">
                        <Label className="text-gray-900 dark:text-white">フレア（任意）</Label>
                        <div className="flex flex-wrap gap-2">
                            {flairOptions.map((flair) => (
                                <button
                                    key={flair}
                                    type="button"
                                    onClick={() => handleInputChange('flair', formData.flair === flair ? '' : flair)}
                                    className={`px-3 py-1 rounded-full text-xs font-medium transition-colors ${
                                        formData.flair === flair
                                            ? 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300 ring-2 ring-blue-500'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                    }`}
                                >
                                    {flair}
                                </button>
                            ))}
                        </div>
                        {formData.flair && (
                            <div className="flex items-center space-x-2">
                                <span className="text-sm text-gray-600 dark:text-gray-400">選択中:</span>
                                <Badge variant="outline" className="border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                                    {formData.flair}
                                    <button
                                        type="button"
                                        onClick={() => handleInputChange('flair', '')}
                                        className="ml-1 hover:text-red-500"
                                    >
                                        <X className="w-3 h-3" />
                                    </button>
                                </Badge>
                            </div>
                        )}
                    </div>

                    {/* プレビュー */}
                    {selectedCommunity && formData.title && (
                        <div className="space-y-2">
                            <Label className="text-gray-900 dark:text-white">プレビュー</Label>
                            <div className="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                                <div className="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400 mb-2">
                                    <span>{selectedCommunity.icon} {selectedCommunity.name}</span>
                                    <span>•</span>
                                    <span>投稿者: {user?.name}</span>
                                    {selectedStance && (
                                        <>
                                            <span>•</span>
                                            <Badge className={selectedStance.color}>{selectedStance.label}</Badge>
                                        </>
                                    )}
                                    {formData.flair && (
                                        <>
                                            <span>•</span>
                                            <Badge variant="outline" className="border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">{formData.flair}</Badge>
                                        </>
                                    )}
                                </div>
                                <h3 className="font-medium text-gray-900 dark:text-white mb-2">{formData.title}</h3>
                                {formData.content && (
                                    <p className="text-gray-700 dark:text-gray-300 text-sm">{formData.content}</p>
                                )}
                            </div>
                        </div>
                    )}

                    <DialogFooter className="flex-col sm:flex-row space-y-2 sm:space-y-0">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                            className="border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            キャンセル
                        </Button>
                        <Button
                            type="submit"
                            disabled={isSubmitting || !user}
                            className="bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50"
                        >
                            {isSubmitting ? (
                                <>
                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                    投稿中...
                                </>
                            ) : (
                                '議題を投稿'
                            )}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
} 