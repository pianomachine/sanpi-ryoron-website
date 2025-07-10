import SanpiRyoronNavbar from '@/components/sanpi-ryoron-navbar';
import { usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';
import { type User } from '@/types';

interface NavbarLayoutProps {
    children: ReactNode;
}

interface PageProps {
    auth: {
        user: User | null;
    };
    [key: string]: unknown;
}

export default function NavbarLayout({ children }: NavbarLayoutProps) {
    const { auth } = usePage<PageProps>().props;

    return (
        <div className="min-h-screen bg-background">
            <SanpiRyoronNavbar user={auth.user} />
            <main className="flex-1">
                {children}
            </main>
            
            {/* フッター */}
            <footer className="border-t bg-muted/10 py-12 mt-16">
                <div className="container mx-auto px-4">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
                        <div className="col-span-1 md:col-span-2">
                            <h3 className="font-bold text-lg mb-4 text-gradient">賛否両論.com</h3>
                            <p className="text-muted-foreground mb-4">
                                誰もが参加できる議論プラットフォーム。<br />
                                極端な意見を戦わせ、新たな視点を発見しよう。
                            </p>
                            <div className="flex gap-4 text-sm text-muted-foreground">
                                <span>© 2025 賛否両論.com</span>
                            </div>
                        </div>
                        
                        <div>
                            <h4 className="font-semibold mb-4">議題</h4>
                            <ul className="space-y-2 text-sm text-muted-foreground">
                                <li><a href="/?tab=hot" className="hover:text-foreground">HOTな議題</a></li>
                                <li><a href="/?tab=latest" className="hover:text-foreground">最新の議題</a></li>
                                <li><a href="/?tab=popular" className="hover:text-foreground">人気の議題</a></li>
                                <li><a href="/login" className="hover:text-foreground">議題を投稿</a></li>
                            </ul>
                        </div>
                        
                        <div>
                            <h4 className="font-semibold mb-4">サポート</h4>
                            <ul className="space-y-2 text-sm text-muted-foreground">
                                <li><a href="#about" className="hover:text-foreground">サイトについて</a></li>
                                <li><a href="#terms" className="hover:text-foreground">利用規約</a></li>
                                <li><a href="#privacy" className="hover:text-foreground">プライバシーポリシー</a></li>
                                <li><a href="#contact" className="hover:text-foreground">お問い合わせ</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
} 