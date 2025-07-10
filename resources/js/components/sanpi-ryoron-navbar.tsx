import { MessageSquare, Flame, Clock, TrendingUp, Users, Info } from "lucide-react";
import { Navbar1 } from "@/components/shadcnblocks-com-navbar1";
import { usePage } from '@inertiajs/react';
import { getLoginUrlWithRedirect } from '@/lib/utils';

interface SanpiRyoronNavbarProps {
    user?: {
        name: string;
        email: string;
    } | null;
}

export default function SanpiRyoronNavbar({ user }: SanpiRyoronNavbarProps) {
    const page = usePage();
    const currentUrl = page.url;
    const menuItems = [
        {
            title: "ホーム",
            url: "/",
        },
        {
            title: "議題を探す",
            url: "/",
            items: [
                {
                    title: "HOTな議題",
                    description: "いま最も熱い議論が繰り広げられている議題",
                    icon: <Flame className="size-5 shrink-0 text-orange-500" />,
                    url: "/?tab=hot",
                },
                {
                    title: "最新の議題",
                    description: "最近投稿された新しい議題",
                    icon: <Clock className="size-5 shrink-0 text-blue-500" />,
                    url: "/?tab=latest",
                },
                {
                    title: "人気の議題",
                    description: "多くの人が参加している注目の議題",
                    icon: <TrendingUp className="size-5 shrink-0 text-green-500" />,
                    url: "/?tab=popular",
                },
                {
                    title: "参加者が多い議題",
                    description: "活発に議論されている盛り上がっている議題",
                    icon: <Users className="size-5 shrink-0 text-purple-500" />,
                    url: "/?tab=active",
                },
            ],
        },
        {
            title: "議論に参加",
            url: user ? "/topics" : getLoginUrlWithRedirect(currentUrl),
        },
        {
            title: "サイトについて",
            url: "#",
            items: [
                {
                    title: "賛否両論.comとは",
                    description: "このサイトの目的と使い方について",
                    icon: <Info className="size-5 shrink-0 text-blue-500" />,
                    url: "#about",
                },
                {
                    title: "利用規約",
                    description: "サービス利用時のルールと規約",
                    icon: <MessageSquare className="size-5 shrink-0 text-gray-500" />,
                    url: "#terms",
                },
                {
                    title: "プライバシーポリシー",
                    description: "個人情報の取り扱いについて",
                    icon: <MessageSquare className="size-5 shrink-0 text-gray-500" />,
                    url: "#privacy",
                },
                {
                    title: "お問い合わせ",
                    description: "ご質問やご意見をお聞かせください",
                    icon: <MessageSquare className="size-5 shrink-0 text-green-500" />,
                    url: "#contact",
                },
            ],
        },
    ];

    const mobileExtraLinks = [
        { name: "利用規約", url: "#terms" },
        { name: "プライバシーポリシー", url: "#privacy" },
        { name: "お問い合わせ", url: "#contact" },
        { name: "サイトマップ", url: "#sitemap" },
    ];

    return (
        <div className="pl-4 lg:pl-6">
            <Navbar1
                logo={{
                    url: "/",
                    src: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 32 32'%3E%3Cg fill='none'%3E%3Ccircle cx='12' cy='16' r='7' fill='%233b82f6' opacity='0.8'/%3E%3Ccircle cx='20' cy='16' r='7' fill='%23ef4444' opacity='0.8'/%3E%3Ctext x='12' y='20' font-family='Arial,sans-serif' font-size='9' font-weight='bold' text-anchor='middle' fill='white'%3E賛%3C/text%3E%3Ctext x='20' y='20' font-family='Arial,sans-serif' font-size='9' font-weight='bold' text-anchor='middle' fill='white'%3E否%3C/text%3E%3C/g%3E%3C/svg%3E",
                    alt: "賛否両論.com",
                    title: "賛否両論.com",
                }}
                menu={menuItems}
                mobileExtraLinks={mobileExtraLinks}
                auth={
                    user
                        ? {
                              login: { text: user.name, url: "/dashboard" },
                              signup: { text: "ログアウト", url: "/logout" },
                          }
                        : {
                              login: { text: "ログイン", url: getLoginUrlWithRedirect(currentUrl) },
                              signup: { text: "新規議題を投稿", url: getLoginUrlWithRedirect(currentUrl) },
                          }
                }
            />
        </div>
    );
} 