<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Community;

class CommunitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $communities = [
            // 既存のコミュニティ
            [
                'name' => '💰 デート代支払い',
                'slug' => 'dating-payment',
                'icon' => '💰',
                'description' => 'デート代の負担について議論するコミュニティです。男女問わず様々な視点から建設的な議論を行いましょう。',
                'banner_color' => 'from-pink-100 to-red-100',
                'rules' => [
                    '相手を尊重した発言を心がけましょう',
                    '個人攻撃や暴言は禁止です',
                    '具体的な体験談を歓迎します',
                    'ジェンダーに関係なく平等に議論しましょう'
                ],
                'moderators' => ['モデレーター1', 'モデレーター2'],
                'members_count' => 128500,
                'online_count' => 2300,
                'status' => 'active'
            ],
            [
                'name' => '🚃 女性専用車両',
                'slug' => 'women-only-cars',
                'icon' => '🚃',
                'description' => '女性専用車両の是非について議論するコミュニティです。痴漢対策と男性差別の境界線について建設的に話し合いましょう。',
                'banner_color' => 'from-blue-100 to-purple-100',
                'rules' => [
                    '男女双方の立場を理解しましょう',
                    '感情的な発言は控えめに',
                    'データや事例に基づく議論を',
                    '解決策の提案を歓迎します'
                ],
                'moderators' => ['鉄道ファン', '平等主義者'],
                'members_count' => 95200,
                'online_count' => 1800,
                'status' => 'active'
            ],
            [
                'name' => '🏥 子ども温泉',
                'slug' => 'children-spa',
                'icon' => '🏥',
                'description' => '子どもと温泉に関するマナーと議論のコミュニティです。シングル親の悩みや社会のルールについて話し合いましょう。',
                'banner_color' => 'from-green-100 to-blue-100',
                'rules' => [
                    '子育ての困難さを理解しましょう',
                    '施設ルールの確認を推奨',
                    '建設的な解決策を提案',
                    '個人の価値観を尊重'
                ],
                'moderators' => ['シングル親の会', '温泉マナー専門家'],
                'members_count' => 87800,
                'online_count' => 1200,
                'status' => 'active'
            ],
            [
                'name' => '🍝 レディースデー',
                'slug' => 'ladies-day',
                'icon' => '🍝',
                'description' => '女性割引制度について議論するコミュニティです。ジェンダー平等と企業戦略の関係について考えましょう。',
                'banner_color' => 'from-yellow-100 to-orange-100',
                'rules' => [
                    'ジェンダー平等の観点から議論',
                    '企業の立場も考慮しましょう',
                    '具体的な改善案を歓迎',
                    '差別的な発言は禁止'
                ],
                'moderators' => ['平等推進委員', 'ビジネス分析家'],
                'members_count' => 112900,
                'online_count' => 2100,
                'status' => 'active'
            ],
            [
                'name' => '📱 電車内マナー',
                'slug' => 'train-manner',
                'icon' => '📱',
                'description' => '電車内でのマナーについて議論するコミュニティです。通勤・通学での困った体験や改善案をシェアしましょう。',
                'banner_color' => 'from-gray-100 to-blue-100',
                'rules' => [
                    '個人攻撃ではなく行為を議論',
                    '代替案の提案を推奨',
                    '文化的背景の理解を',
                    '建設的な議論を心がけましょう'
                ],
                'moderators' => ['通勤マスター', 'マナー講師'],
                'members_count' => 156100,
                'online_count' => 2800,
                'status' => 'active'
            ],
            
            // ゲーム・サブカル系コミュニティ
            [
                'name' => '🎮 ゲーム議論',
                'slug' => 'gaming-debate',
                'icon' => '🎮',
                'description' => 'ゲームに関する議論や評価について語り合うコミュニティです。FGO、原神、その他あらゆるゲームについて議論しましょう。',
                'banner_color' => 'from-purple-100 to-pink-100',
                'rules' => [
                    'ネタバレには注意しましょう',
                    '他のゲームを貶すのは禁止',
                    '建設的な批評を心がけて',
                    '個人の趣味を尊重しましょう'
                ],
                'moderators' => ['ゲーマー代表', 'eスポーツ関係者'],
                'members_count' => 245000,
                'online_count' => 4500,
                'status' => 'active'
            ],
            [
                'name' => '📺 アニメ・漫画',
                'slug' => 'anime-manga',
                'icon' => '📺',
                'description' => 'アニメと漫画について語り合うコミュニティです。今期アニメから名作まで幅広く議論しましょう。',
                'banner_color' => 'from-blue-100 to-cyan-100',
                'rules' => [
                    'ネタバレは適切にタグ付け',
                    '作品や作者への敬意を',
                    '多様な意見を受け入れましょう',
                    '建設的な批評を推奨'
                ],
                'moderators' => ['アニメ評論家', '漫画ソムリエ'],
                'members_count' => 198000,
                'online_count' => 3200,
                'status' => 'active'
            ],
            [
                'name' => '🎬 映画・ドラマ',
                'slug' => 'movies-drama',
                'icon' => '🎬',
                'description' => '映画とドラマについて議論するコミュニティです。邦画から洋画、アニメ映画まで幅広くカバー。',
                'banner_color' => 'from-red-100 to-yellow-100',
                'rules' => [
                    'ネタバレには十分注意',
                    '多様なジャンルを尊重',
                    '建設的な議論を心がけて',
                    '個人の感想を大切に'
                ],
                'moderators' => ['映画批評家', 'ドラマウォッチャー'],
                'members_count' => 167000,
                'online_count' => 2900,
                'status' => 'active'
            ],
            [
                'name' => '🎵 音楽議論',
                'slug' => 'music-debate',
                'icon' => '🎵',
                'description' => '音楽に関する議論のコミュニティです。アイドル、ロック、クラシックなど全ジャンル対応。',
                'banner_color' => 'from-green-100 to-teal-100',
                'rules' => [
                    '音楽の多様性を尊重',
                    '個人の音楽的趣味を否定しない',
                    'アーティストへの敬意を',
                    '建設的な議論を推奨'
                ],
                'moderators' => ['音楽プロデューサー', 'DJ'],
                'members_count' => 134000,
                'online_count' => 2400,
                'status' => 'active'
            ],
            
            // 政治・経済・社会系コミュニティ
            [
                'name' => '🏛️ 政治・政策',
                'slug' => 'politics-policy',
                'icon' => '🏛️',
                'description' => '政治や政策について建設的に議論するコミュニティです。与野党問わず幅広い視点で話し合いましょう。',
                'banner_color' => 'from-indigo-100 to-blue-100',
                'rules' => [
                    '特定政党への中傷禁止',
                    'ファクトチェックを心がけて',
                    '建設的な政策議論を',
                    '感情的な発言は控えめに'
                ],
                'moderators' => ['政治学者', '元政治記者'],
                'members_count' => 89500,
                'online_count' => 1600,
                'status' => 'active'
            ],
            [
                'name' => '💹 経済・税制',
                'slug' => 'economy-tax',
                'icon' => '💹',
                'description' => '経済政策や税制について議論するコミュニティです。税金、社会保障、経済政策について語り合いましょう。',
                'banner_color' => 'from-emerald-100 to-green-100',
                'rules' => [
                    'データに基づく議論を',
                    '複雑な経済問題への理解を',
                    '多角的な視点を大切に',
                    '建設的な提案を歓迎'
                ],
                'moderators' => ['経済学者', 'ファイナンシャルプランナー'],
                'members_count' => 76300,
                'online_count' => 1300,
                'status' => 'active'
            ],
            [
                'name' => '🌍 社会問題',
                'slug' => 'social-issues',
                'icon' => '🌍',
                'description' => '様々な社会問題について議論するコミュニティです。環境、人権、格差など幅広いテーマを扱います。',
                'banner_color' => 'from-orange-100 to-red-100',
                'rules' => [
                    '差別的な発言は禁止',
                    '多様な立場を理解しましょう',
                    '解決策の提案を歓迎',
                    '建設的な議論を心がけて'
                ],
                'moderators' => ['社会活動家', 'NPO関係者'],
                'members_count' => 112000,
                'online_count' => 2000,
                'status' => 'active'
            ],
            [
                'name' => '🎓 教育・子育て',
                'slug' => 'education-parenting',
                'icon' => '🎓',
                'description' => '教育制度や子育てについて議論するコミュニティです。学校教育から家庭教育まで幅広くカバー。',
                'banner_color' => 'from-teal-100 to-blue-100',
                'rules' => [
                    '子どもの視点も考慮',
                    '教育者への敬意を',
                    '建設的な改善案を',
                    '多様な教育観を尊重'
                ],
                'moderators' => ['教育関係者', '保護者代表'],
                'members_count' => 145000,
                'online_count' => 2600,
                'status' => 'active'
            ],
            [
                'name' => '🏢 働き方・労働',
                'slug' => 'work-labor',
                'icon' => '🏢',
                'description' => '働き方や労働環境について議論するコミュニティです。残業、リモートワーク、労働条件などを話し合いましょう。',
                'banner_color' => 'from-slate-100 to-gray-100',
                'rules' => [
                    '企業への誹謗中傷は禁止',
                    '労働者の権利を尊重',
                    '建設的な改善案を',
                    '多様な働き方を認めましょう'
                ],
                'moderators' => ['労働組合関係者', 'HR専門家'],
                'members_count' => 203000,
                'online_count' => 3800,
                'status' => 'active'
            ],
            [
                'name' => '💻 テクノロジー',
                'slug' => 'technology',
                'icon' => '💻',
                'description' => 'テクノロジーや科学技術について議論するコミュニティです。AI、プログラミング、新技術について語り合いましょう。',
                'banner_color' => 'from-cyan-100 to-blue-100',
                'rules' => [
                    '技術的正確性を重視',
                    '初心者にも優しく',
                    'ソースの提示を推奨',
                    '建設的な議論を'
                ],
                'moderators' => ['エンジニア', 'IT専門家'],
                'members_count' => 178000,
                'online_count' => 3400,
                'status' => 'active'
            ],
            [
                'name' => '🏥 健康・医療',
                'slug' => 'health-medical',
                'icon' => '🏥',
                'description' => '健康や医療について議論するコミュニティです。※医学的アドバイスではなく議論の場です。',
                'banner_color' => 'from-rose-100 to-pink-100',
                'rules' => [
                    '医療的アドバイスは専門家に',
                    '科学的根拠を重視',
                    '個人の体験談は参考程度に',
                    '健康情報には慎重に'
                ],
                'moderators' => ['医療関係者', '保健師'],
                'members_count' => 156000,
                'online_count' => 2800,
                'status' => 'active'
            ],
            [
                'name' => '🍜 グルメ・食文化',
                'slug' => 'food-culture',
                'icon' => '🍜',
                'description' => '食べ物や食文化について議論するコミュニティです。グルメ情報から食生活まで幅広く。',
                'banner_color' => 'from-amber-100 to-orange-100',
                'rules' => [
                    '個人の味覚を尊重',
                    '文化的背景を理解',
                    '建設的なレビューを',
                    '食材への感謝を忘れずに'
                ],
                'moderators' => ['料理研究家', 'グルメライター'],
                'members_count' => 189000,
                'online_count' => 3500,
                'status' => 'active'
            ],
            [
                'name' => '🚗 交通・移動',
                'slug' => 'transportation',
                'icon' => '🚗',
                'description' => '交通手段や移動について議論するコミュニティです。車、電車、自転車、歩行者の問題を話し合いましょう。',
                'banner_color' => 'from-blue-100 to-indigo-100',
                'rules' => [
                    '交通安全を最優先に',
                    '多様な移動手段を尊重',
                    '建設的な改善案を',
                    'マナーの向上を目指して'
                ],
                'moderators' => ['交通政策専門家', '運転指導員'],
                'members_count' => 134000,
                'online_count' => 2500,
                'status' => 'active'
            ],
            [
                'name' => '🏠 住環境・地域',
                'slug' => 'housing-community',
                'icon' => '🏠',
                'description' => '住環境や地域社会について議論するコミュニティです。マンション問題、町内会、近隣トラブルなど。',
                'banner_color' => 'from-green-100 to-emerald-100',
                'rules' => [
                    '近隣への配慮を',
                    '建設的な解決策を',
                    '地域差を理解しましょう',
                    '多様な生活スタイルを尊重'
                ],
                'moderators' => ['不動産専門家', '町内会長'],
                'members_count' => 98000,
                'online_count' => 1800,
                'status' => 'active'
            ]
        ];

        foreach ($communities as $community) {
            Community::create($community);
        }
    }
}
