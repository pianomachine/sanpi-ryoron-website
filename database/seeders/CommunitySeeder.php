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
            ]
        ];

        foreach ($communities as $community) {
            Community::create($community);
        }
    }
}
