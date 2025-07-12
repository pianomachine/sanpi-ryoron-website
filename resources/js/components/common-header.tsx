import React, { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { type User } from '@/types';
import { getLoginUrlWithRedirect, getLogoutData } from '@/lib/utils';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { 
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/hooks/use-appearance';
import TopicCreationModal from '@/components/topic-creation-modal';
import SearchBar from '@/components/search-bar';
import { 
    Home,
    TrendingUp,
    Search,
    Plus,
    User as UserIcon,
    Edit,
    Trophy,
    Crown,
    Moon,
    Sun,
    LogOut,
    Megaphone,
    Settings
} from 'lucide-react';

interface CommonHeaderProps {
    user?: User | null;
}

export default function CommonHeader({ user }: CommonHeaderProps) {
    const { appearance, updateAppearance } = useAppearance();
    const [isTopicModalOpen, setIsTopicModalOpen] = useState(false);
    const page = usePage();
    const currentUrl = page.url;

    const toggleDarkMode = () => {
        // ダークモードとライトモードを切り替え
        if (appearance === 'dark') {
            updateAppearance('light');
        } else {
            updateAppearance('dark');
        }
    };

    const handleTopicCreate = () => {
        if (!user) {
            // ログインしていない場合はログインページにリダイレクト（現在のページを保持）
            router.visit(getLoginUrlWithRedirect(currentUrl));
            return;
        }
        setIsTopicModalOpen(true);
    };

    const handleLogout = () => {
        // WorkOSのログアウト処理（現在のページにリダイレクト）
        router.post('/logout', getLogoutData(currentUrl), {
            preserveState: false,
            preserveScroll: false,
        });
    };

    const handleSearch = (query: string) => {
        // 検索ページにリダイレクト
        router.visit(`/search?q=${encodeURIComponent(query)}`);
    };

    const isDarkMode = appearance === 'dark';

    return (
        <>
            <header className="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
                <div className="max-w-7xl mx-auto px-4">
                    <div className="flex items-center justify-between h-12">
                    {/* Logo and Navigation */}
                    <div className="flex items-center space-x-4">
                        <Link href="/home" className="flex items-center space-x-2">
                            <div className="w-8 h-8 bg-gradient-to-r from-blue-500 to-red-500 rounded-full flex items-center justify-center">
                                <span className="text-white font-bold text-xs">賛否</span>
                            </div>
                            <span className="font-bold text-xl hidden sm:block text-gray-900 dark:text-white">賛否両論.com</span>
                        </Link>
                        
                        <nav className="hidden md:flex items-center space-x-1">
                            <Button variant="ghost" size="sm" className="flex items-center space-x-1 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800" asChild>
                                <Link href="/home">
                                    <Home className="w-4 h-4" />
                                    <span>ホーム</span>
                                </Link>
                            </Button>
                            <Button variant="ghost" size="sm" className="flex items-center space-x-1 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800">
                                <TrendingUp className="w-4 h-4" />
                                <span>人気</span>
                            </Button>
                        </nav>
                    </div>

                    {/* Search Bar */}
                    <div className="flex-1 max-w-2xl mx-4">
                        <SearchBar 
                            onSearch={handleSearch}
                            placeholder="議題を検索..."
                            className="w-full flex justify-center"
                        />
                    </div>

                    {/* User Menu */}
                    <div className="flex items-center space-x-2">
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            onClick={handleTopicCreate}
                            className="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800"
                            title="議題を投稿"
                        >
                            <Plus className="w-4 h-4" />
                        </Button>
                        {user ? (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" className="flex items-center space-x-2 p-1 h-auto hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full text-gray-700 dark:text-gray-300">
                                        <Avatar className="w-8 h-8">
                                            <AvatarImage src={user.avatar} alt={user.name} />
                                            <AvatarFallback className="text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{user.name?.charAt(0)}</AvatarFallback>
                                        </Avatar>
                                        <span className="hidden sm:inline text-sm">{user.name}</span>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent className="w-56" align="end">
                                    <DropdownMenuLabel className="font-normal">
                                        <div className="flex flex-col space-y-1">
                                            <p className="text-sm font-medium leading-none">{user.name}</p>
                                            <p className="text-xs leading-none text-muted-foreground">{user.email}</p>
                                        </div>
                                    </DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem asChild>
                                        <Link href="/settings/my-topics" className="flex items-center w-full">
                                            <UserIcon className="mr-2 h-4 w-4" />
                                            <span>プロフィールを見る</span>
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem>
                                        <Edit className="mr-2 h-4 w-4" />
                                        <span>アバターを編集</span>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem>
                                        <Trophy className="mr-2 h-4 w-4" />
                                        <span>実績</span>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem>
                                        <Crown className="mr-2 h-4 w-4" />
                                        <span>プレミアム</span>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem onClick={toggleDarkMode}>
                                        {isDarkMode ? (
                                            <Sun className="mr-2 h-4 w-4" />
                                        ) : (
                                            <Moon className="mr-2 h-4 w-4" />
                                        )}
                                        <span>{isDarkMode ? 'ライトモード' : 'ダークモード'}</span>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem onClick={handleLogout}>
                                        <LogOut className="mr-2 h-4 w-4" />
                                        <span>ログアウト</span>
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem>
                                        <Megaphone className="mr-2 h-4 w-4" />
                                        <span>広告を掲載する</span>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem>
                                        <Settings className="mr-2 h-4 w-4" />
                                        <span>設定</span>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        ) : (
                            <div className="flex space-x-2">
                                <Button variant="outline" size="sm" className="border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800" asChild>
                                    <Link href={getLoginUrlWithRedirect(currentUrl)}>ログイン</Link>
                                </Button>
                                <Button size="sm" className="bg-blue-600 hover:bg-blue-700 text-white" asChild>
                                    <Link href={getLoginUrlWithRedirect(currentUrl)}>アカウント作成</Link>
                                </Button>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </header>
        
        <TopicCreationModal 
            open={isTopicModalOpen} 
            onOpenChange={setIsTopicModalOpen} 
            user={user} 
        />
        </>
    );
} 