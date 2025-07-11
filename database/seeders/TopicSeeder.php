<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Topic;
use App\Models\Community;
use App\Models\User;
use Carbon\Carbon;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // コミュニティとユーザーを取得
        $communities = Community::all()->keyBy('slug');
        $user = User::first() ?? User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);

        // 各カテゴリ別のトピックテンプレートを定義
        $topicTemplates = $this->getTopicTemplates();
        
        $totalTopics = 0;
        $batchSize = 500; // バッチ処理で効率化
        
        // 各コミュニティに対してトピックを生成
        foreach ($communities as $slug => $community) {
            if (!isset($topicTemplates[$slug])) continue;
            
            $templates = $topicTemplates[$slug];
            $topicsPerCommunity = rand(400, 800); // コミュニティごとに400-800件
            
            for ($batch = 0; $batch < ceil($topicsPerCommunity / $batchSize); $batch++) {
                $batchTopics = [];
                $currentBatchSize = min($batchSize, $topicsPerCommunity - ($batch * $batchSize));
                
                for ($i = 0; $i < $currentBatchSize; $i++) {
                    $template = $templates[array_rand($templates)];
                    
                    $topic = [
                        'community_id' => $community->id,
                        'user_id' => $user->id,
                        'title' => $this->generateTitle($template),
                        'content' => $this->generateContent($template),
                        'type' => 'text',
                        'flair' => $this->getRandomFlair(),
                        'score' => 0,
                        'upvotes' => 0,
                        'downvotes' => 0,
                        'comments_count' => 0,
                        'views_count' => rand(50, 10000),
                        'status' => 'active',
                        'created_at' => Carbon::now()->subHours(rand(1, 720)) // 過去30日間
                    ];
                    
                    $batchTopics[] = $topic;
                    $totalTopics++;
                }
                
                // バッチ挿入
                Topic::insert($batchTopics);
                
                if ($totalTopics >= 10000) break 2; // 10,000件に達したら終了
            }
        }
        
        // ログ出力
        $this->command->info("Generated {$totalTopics} topics");
    }
    
    private function getTopicTemplates(): array
    {
        return [
            // ゲーム関係
            'gaming-debate' => [
                [
                    'title_templates' => [
                        'FGOの{character}って過小評価されてると思いませんか？',
                        '{game}の{element}がめちゃくちゃ良いと思いませんか？',
                        '最近の{genre}ゲームの{aspect}は昔より{comparison}だと思いませんか？',
                        '{platform}独占の{title}は他機種でも出すべきだと思いませんか？',
                        'ゲーム実況者の{behavior}って{opinion}だと思いませんか？'
                    ],
                    'content_templates' => [
                        '最近{game}をプレイしていて気になったのですが、{detail}。みなさんはどう思いますか？',
                        '{experience}という体験をして、{opinion}と感じました。同じような経験をした方はいらっしゃいますか？',
                        'ネット上では{topic}について議論が分かれていますが、実際のところどうなのでしょうか？'
                    ],
                    'variables' => [
                        'character' => ['ギルガメッシュ', 'セイバー', 'アーチャー', '沖田総司', 'ジャンヌダルク', 'マーリン'],
                        'game' => ['原神', 'ウマ娘', 'パズドラ', 'モンスト', 'デレステ', 'ポケモン', 'スプラトゥーン', 'フォートナイト'],
                        'element' => ['戦闘システム', 'ストーリー', 'グラフィック', 'BGM', 'キャラデザイン', 'ゲームバランス'],
                        'genre' => ['RPG', 'FPS', 'パズル', 'レーシング', 'シミュレーション', 'アクション'],
                        'aspect' => ['課金要素', '難易度', 'オンライン要素', 'ストーリー性', 'グラフィック'],
                        'comparison' => ['良くなっている', '悪くなっている', '変わっていない'],
                        'platform' => ['PlayStation', 'Nintendo Switch', 'Xbox', 'PC'],
                        'title' => ['独占タイトル', '人気シリーズ', '話題作'],
                        'behavior' => ['実況スタイル', '編集技術', 'コメント対応'],
                        'opinion' => ['素晴らしい', 'もう少し改善が必要', '好みが分かれる'],
                        'detail' => ['バランス調整が絶妙だった', 'ストーリーに感動した', 'システムが革新的だった'],
                        'experience' => ['オンライン対戦で', 'ソロプレイで', 'フレンドと協力プレイで'],
                        'topic' => ['このゲームの評価', '最近のアップデート', 'コミュニティの雰囲気']
                    ]
                ]
            ],
            
            // アニメ・漫画
            'anime-manga' => [
                [
                    'title_templates' => [
                        '今期の{season}アニメって{opinion}だと思いませんか？',
                        '{title}の{character}の{action}は{judgment}だと思いませんか？',
                        '最近の{genre}漫画の{trend}について{feeling}と思いませんか？',
                        '{studio}のアニメ制作って{quality}だと思いませんか？',
                        'アニメの{aspect}は漫画版より{comparison}だと思いませんか？'
                    ],
                    'variables' => [
                        'season' => ['春', '夏', '秋', '冬'],
                        'opinion' => ['豊作', '不作', '普通', '期待以上', '期待以下'],
                        'title' => ['鬼滅の刃', '呪術廻戦', '進撃の巨人', 'ワンピース', '東京リベンジャーズ', 'チェンソーマン'],
                        'character' => ['主人公', 'ヒロイン', 'ライバル', '敵キャラ', 'サブキャラ'],
                        'action' => ['決断', '行動', '発言', '成長', '関係性'],
                        'judgment' => ['正しい', '間違っている', '理解できる', '理解しがたい'],
                        'genre' => ['異世界', 'ラブコメ', 'バトル', '日常系', 'ホラー', 'SF'],
                        'trend' => ['設定', 'キャラデザ', 'ストーリー展開', 'テーマ性'],
                        'feeling' => ['面白い', 'つまらない', '新鮮', 'マンネリ'],
                        'studio' => ['マッドハウス', 'ufotable', 'WIT STUDIO', '京都アニメーション', 'ボンズ'],
                        'quality' => ['高品質', '安定している', '波がある', '期待を裏切らない'],
                        'aspect' => ['演出', '作画', '声優', '音楽', 'テンポ'],
                        'comparison' => ['良い', '劣る', '違った魅力がある']
                    ]
                ]
            ],
            
            // 政治・政策
            'politics-policy' => [
                [
                    'title_templates' => [
                        'この{tax}の高さに意味があると思いませんか？',
                        '{policy}は{effect}だと思いませんか？',
                        '日本の{system}は{country}を見習うべきだと思いませんか？',
                        '{politician}の{stance}について{opinion}と思いませんか？',
                        '{issue}の解決には{solution}が必要だと思いませんか？'
                    ],
                    'variables' => [
                        'tax' => ['消費税', '所得税', '住民税', '相続税', '法人税'],
                        'policy' => ['少子高齢化対策', '環境政策', '外交政策', '教育改革', '医療制度改革'],
                        'effect' => ['効果的', '非効率', '時代遅れ', '革新的'],
                        'system' => ['選挙制度', '社会保障制度', '教育制度', '司法制度'],
                        'country' => ['北欧諸国', 'ドイツ', 'シンガポール', 'カナダ', 'オーストラリア'],
                        'politician' => ['政治家', '首相', '大臣', '地方議員'],
                        'stance' => ['政策提案', '発言', '行動', '判断'],
                        'opinion' => ['適切', '不適切', '時期尚早', '遅すぎる'],
                        'issue' => ['少子化問題', '環境問題', '経済格差', '地方創生'],
                        'solution' => ['法改正', '予算増額', '制度改革', '国際協力']
                    ]
                ]
            ],
            
            // 経済・税制
            'economy-tax' => [
                [
                    'title_templates' => [
                        '{tax}を{direction}すべきだと思いませんか？',
                        '日本経済の{aspect}は{status}だと思いませんか？',
                        '{policy}の経済効果は{evaluation}だと思いませんか？',
                        '最低賃金{amount}円は{opinion}だと思いませんか？',
                        '{system}の導入は{necessity}だと思いませんか？'
                    ],
                    'variables' => [
                        'tax' => ['消費税', '法人税', '所得税', '相続税'],
                        'direction' => ['引き上げ', '引き下げ', '廃止', '簡素化'],
                        'aspect' => ['成長率', '競争力', '格差問題', '将来性'],
                        'status' => ['健全', '危険', '停滞している', '回復傾向'],
                        'policy' => ['金融緩和', 'ベーシックインカム', 'デジタル化促進', 'グリーン投資'],
                        'evaluation' => ['十分', '不十分', '逆効果', '未知数'],
                        'amount' => ['1000', '1200', '1500', '2000'],
                        'opinion' => ['適正', '低すぎる', '高すぎる', '地域差を考慮すべき'],
                        'system' => ['電子マネー', 'インボイス制度', 'リモートワーク税制', 'カーボンプライシング'],
                        'necessity' => ['必要', '不要', '時期尚早', '検討が必要']
                    ]
                ]
            ],
            
            // 既存のコミュニティテンプレートも追加
            'dating-payment' => [
                [
                    'title_templates' => [
                        'デート代は{payer}が払うべきだと思いませんか？',
                        '{situation}での{payment}は{opinion}だと思いませんか？',
                        '{age}代の{gender}から見たデート代の{aspect}について{feeling}と思いませんか？'
                    ],
                    'variables' => [
                        'payer' => ['男性', '女性', '誘った方', '年上', '収入が多い方'],
                        'situation' => ['初デート', '付き合い始め', '長期交際', '結婚前提'],
                        'payment' => ['全額負担', '割り勘', '多めに払う', '交互に払う'],
                        'opinion' => ['当然', '古い考え', '現実的', '理想的'],
                        'age' => ['20', '30', '40', '50'],
                        'gender' => ['男性', '女性'],
                        'aspect' => ['考え方', '負担感', '価値観', '経済的影響'],
                        'feeling' => ['共感できる', '理解できない', '時代錯誤', '現実的']
                    ]
                ]
            ],
            
            'women-only-cars' => [
                [
                    'title_templates' => [
                        '女性専用車両は{opinion}だと思いませんか？',
                        '{time}の女性専用車両は{necessity}だと思いませんか？',
                        '海外の{approach}を日本も{action}べきだと思いませんか？'
                    ],
                    'variables' => [
                        'opinion' => ['必要', '不要', '男性差別', '安全対策', '時代遅れ'],
                        'time' => ['ラッシュ時', '夜間', '全時間帯', '終電'],
                        'necessity' => ['絶対必要', '場合による', '見直すべき', '廃止すべき'],
                        'approach' => ['混合車両システム', '防犯強化', '監視カメラ', '教育重視'],
                        'action' => ['導入す', '参考にす', '検討す']
                    ]
                ]
            ],
            
            'train-manner' => [
                [
                    'title_templates' => [
                        '電車内での{behavior}は{judgment}だと思いませんか？',
                        '{situation}時の{action}について{opinion}と思いませんか？',
                        'マナー{aspect}は{method}べきだと思いませんか？'
                    ],
                    'variables' => [
                        'behavior' => ['化粧', '飲食', '通話', '音楽', '読書', '睡眠'],
                        'judgment' => ['マナー違反', '個人の自由', '場合による', '時代の変化'],
                        'situation' => ['ラッシュ', '深夜', '長距離', '短距離'],
                        'action' => ['座席の譲り合い', 'リュックの扱い', 'ドアの乗降', '優先席の利用'],
                        'opinion' => ['改善が必要', '理解できる', '仕方ない', '教育すべき'],
                        'aspect' => ['向上', '教育', '罰則', '啓発'],
                        'method' => ['義務化す', '推奨す', '自主性に任せる']
                    ]
                ]
            ],
            
            // 映画・ドラマ
            'movies-drama' => [
                [
                    'title_templates' => [
                        '{movie}は{rating}だと思いませんか？',
                        '最近の{genre}映画の{aspect}は{opinion}だと思いませんか？',
                        'ドラマの{element}って{judgment}だと思いませんか？',
                        '{country}映画と邦画の{comparison}について{feeling}と思いませんか？',
                        'リメイク作品は{original}だと思いませんか？'
                    ],
                    'variables' => [
                        'movie' => ['鬼滅の刃', 'シン・ゴジラ', '君の名は', '千と千尋', 'トップガン', 'アベンジャーズ'],
                        'rating' => ['傑作', '駄作', '過大評価', '過小評価', '普通', '話題性だけ'],
                        'genre' => ['アクション', 'ホラー', 'ラブコメ', 'SF', 'ファンタジー', 'ドキュメンタリー'],
                        'aspect' => ['CG技術', 'ストーリー', '演技力', '音楽', '撮影技術'],
                        'opinion' => ['進歩している', '劣化している', '変わらない', '独自性がない'],
                        'element' => ['恋愛要素', '展開の速さ', 'キャラクター設定', '伏線回収'],
                        'judgment' => ['重要', '不要', '適度', '過剰'],
                        'country' => ['ハリウッド', '韓国', 'インド', 'フランス', '中国'],
                        'comparison' => ['技術力の差', '文化的違い', '予算規模', 'ストーリー性'],
                        'feeling' => ['興味深い', '残念', '当然', '複雑'],
                        'original' => ['オリジナルを超えるべき', '忠実であるべき', '独自色を出すべき', '作らない方が良い']
                    ]
                ]
            ],
            
            // 音楽
            'music-debate' => [
                [
                    'title_templates' => [
                        '{artist}の{song}は{evaluation}だと思いませんか？',
                        '{genre}音楽の{aspect}について{opinion}と思いませんか？',
                        '音楽の{format}は{judgment}だと思いませんか？',
                        '{era}の音楽と現在の音楽の{difference}は{feeling}だと思いませんか？',
                        'ライブと{media}では{comparison}だと思いませんか？'
                    ],
                    'variables' => [
                        'artist' => ['米津玄師', 'あいみょん', 'YOASOBI', 'BTS', 'King Gnu', 'Official髭男dism'],
                        'song' => ['代表曲', '最新曲', 'デビュー曲', 'コラボ曲', 'カバー曲'],
                        'evaluation' => ['名曲', '普通', '過大評価', '隠れた名曲', '時代を表している'],
                        'genre' => ['J-POP', 'K-POP', 'ロック', 'ジャズ', 'クラシック', 'EDM'],
                        'aspect' => ['歌詞の深さ', 'メロディー', 'アレンジ', '世界観', 'パフォーマンス'],
                        'opinion' => ['素晴らしい', '物足りない', '変化が必要', '伝統を守るべき'],
                        'format' => ['ダウンロード', 'ストリーミング', 'CD', 'レコード', 'ライブ'],
                        'judgment' => ['時代に合っている', '味気ない', '便利', 'アーティストに不利'],
                        'era' => ['90年代', '2000年代', '昭和', '平成初期'],
                        'difference' => ['楽曲の質', '多様性', '技術力', '創造性'],
                        'feeling' => ['懐かしい', '進歩的', '複雑', '心配'],
                        'media' => ['録音', 'MV', 'ラジオ', 'TV'],
                        'comparison' => ['迫力が違う', '別物', '同じくらい良い', 'それぞれの良さがある']
                    ]
                ]
            ],
            
            // 社会問題
            'social-issues' => [
                [
                    'title_templates' => [
                        '{issue}は{priority}だと思いませんか？',
                        '{problem}を解決するには{solution}が{necessity}だと思いませんか？',
                        '日本の{aspect}は{status}だと思いませんか？',
                        '{group}の{situation}について{opinion}と思いませんか？',
                        '{trend}は社会に{impact}だと思いませんか？'
                    ],
                    'variables' => [
                        'issue' => ['格差問題', '環境破壊', '少子高齢化', '孤立死', '過労死', 'いじめ問題'],
                        'priority' => ['最優先課題', '後回しでよい', '個人の問題', '緊急性がある'],
                        'problem' => ['貧困', '差別', '環境汚染', 'ジェンダー格差', '教育格差'],
                        'solution' => ['政府の介入', '企業努力', '個人の意識改革', '法整備', 'NGOの活動'],
                        'necessity' => ['絶対必要', '検討が必要', '効果的でない', '時期尚早'],
                        'aspect' => ['人権意識', '環境対策', '福祉制度', '国際協力', '技術革新'],
                        'status' => ['世界水準', '遅れている', '進んでいる', '改善が必要'],
                        'group' => ['高齢者', '若者', '障害者', '外国人', 'LGBT+', 'シングルマザー'],
                        'situation' => ['現状', '待遇', '権利', '支援体制', '社会参加'],
                        'opinion' => ['改善すべき', '十分', '複雑', '理解が足りない'],
                        'trend' => ['リモートワーク', 'SNS', 'AI技術', 'グローバル化', '個人主義'],
                        'impact' => ['良い影響を与えている', '悪い影響を与えている', '両面がある', '未知数']
                    ]
                ]
            ],
            
            // 教育・子育て
            'education-parenting' => [
                [
                    'title_templates' => [
                        '学校の{system}は{opinion}だと思いませんか？',
                        '{age}の子どもに{activity}は{judgment}だと思いませんか？',
                        '教育に{aspect}を取り入れるのは{evaluation}だと思いませんか？',
                        '親の{behavior}は子どもに{impact}だと思いませんか？',
                        '{method}教育と{traditional}教育の{comparison}について{feeling}と思いませんか？'
                    ],
                    'variables' => [
                        'system' => ['制服', '部活動', '宿題', '定期テスト', '進路指導', '校則'],
                        'opinion' => ['必要', '不要', '改革が必要', '時代遅れ', '効果的'],
                        'age' => ['幼児', '小学生', '中学生', '高校生'],
                        'activity' => ['スマホ', 'ゲーム', '習い事', 'アルバイト', 'SNS', '恋愛'],
                        'judgment' => ['早すぎる', '適切', '必要', '有害', '個人差がある'],
                        'aspect' => ['デジタル技術', '国際理解', 'プログラミング', '金融教育', '性教育'],
                        'evaluation' => ['重要', '不要', '慎重に検討すべき', '時期尚早'],
                        'behavior' => ['過干渉', '放任主義', '教育熱心', '友達親子', 'SNS投稿'],
                        'impact' => ['良い影響', '悪い影響', '複雑な影響', '個人差がある影響'],
                        'method' => ['オンライン', '体験型', '海外式', 'STEM'],
                        'traditional' => ['従来の', '詰め込み式', '日本式', '講義型'],
                        'comparison' => ['効果の違い', '特徴', '適用範囲', '将来性'],
                        'feeling' => ['興味深い', '心配', '期待している', '慎重に見ている']
                    ]
                ]
            ],
            
            // 働き方・労働
            'work-labor' => [
                [
                    'title_templates' => [
                        '{workstyle}は{evaluation}だと思いませんか？',
                        '職場の{issue}について{opinion}と思いませんか？',
                        '{benefit}は労働者にとって{necessity}だと思いませんか？',
                        '転職{frequency}は{judgment}だと思いませんか？',
                        '仕事と{balance}の{aspect}は{importance}だと思いませんか？'
                    ],
                    'variables' => [
                        'workstyle' => ['リモートワーク', 'フレックス制', '週4日制', '副業', 'ジョブ型雇用'],
                        'evaluation' => ['効率的', '非効率', '理想的', '現実的でない', '一長一短'],
                        'issue' => ['残業', 'パワハラ', '飲み会', '服装規定', '年功序列', 'ノルマ'],
                        'opinion' => ['改善すべき', '必要悪', '文化として残すべき', '個人の判断'],
                        'benefit' => ['有給取得', '育休', '時短勤務', '福利厚生', '研修制度'],
                        'necessity' => ['必須', 'あった方が良い', '企業次第', '優先度は低い'],
                        'frequency' => ['3年以内', '5年以上', '一度も無し', '複数回'],
                        'judgment' => ['普通', '早すぎる', '慎重すぎる', '戦略的', '計画性がない'],
                        'balance' => ['プライベート', '家族', '趣味', '健康', '自己啓発'],
                        'aspect' => ['両立', '優先順位', '時間配分', '価値観'],
                        'importance' => ['重要', '二の次', 'バランス次第', '個人差がある']
                    ]
                ]
            ],
            
            // テクノロジー
            'technology' => [
                [
                    'title_templates' => [
                        '{technology}の{aspect}は{evaluation}だと思いませんか？',
                        'AI{application}は{opinion}だと思いませんか？',
                        '{device}の{feature}について{judgment}と思いませんか？',
                        'プログラミング{element}は{necessity}だと思いませんか？',
                        '{trend}が{field}に与える{impact}は{significance}だと思いませんか？'
                    ],
                    'variables' => [
                        'technology' => ['AI', '5G', 'VR', 'AR', 'ブロックチェーン', 'IoT', '量子コンピュータ'],
                        'aspect' => ['発展速度', '実用性', '安全性', 'プライバシー', '経済効果'],
                        'evaluation' => ['期待以上', '期待以下', '慎重に見るべき', '革命的'],
                        'application' => ['医療応用', '教育利用', '自動運転', '翻訳', '創作活動', '業務効率化'],
                        'opinion' => ['有益', '危険', '時期尚早', '可能性は高い', '課題が多い'],
                        'device' => ['iPhone', 'Android', 'ゲーム機', 'PC', 'タブレット', 'スマートウォッチ'],
                        'feature' => ['性能向上', '価格設定', 'デザイン', 'バッテリー', 'カメラ機能'],
                        'judgment' => ['満足', '不満', '期待以上', 'コスパが悪い', '改善が必要'],
                        'element' => ['教育', '資格', 'スキル習得', '言語選択', '実務経験'],
                        'necessity' => ['必須', '有利', 'あるに越したことはない', '専門職以外は不要'],
                        'trend' => ['クラウド化', 'デジタル化', 'リモート化', '自動化', 'サブスク化'],
                        'field' => ['教育', '医療', '金融', '製造業', 'エンタメ', '小売'],
                        'impact' => ['影響', '変革', '効果', '課題'],
                        'significance' => ['大きい', '小さい', '未知数', '長期的には重要']
                    ]
                ]
            ],
            
            // 健康・医療
            'health-medical' => [
                [
                    'title_templates' => [
                        '{health}に{method}は{effectiveness}だと思いませんか？',
                        '医療の{aspect}について{opinion}と思いませんか？',
                        '{lifestyle}は健康に{impact}だと思いませんか？',
                        '{age}代から{care}を始めるのは{timing}だと思いませんか？',
                        '健康{trend}の{evaluation}はいかがでしょうか？'
                    ],
                    'variables' => [
                        'health' => ['ダイエット', '筋トレ', '睡眠改善', 'ストレス解消', '禁煙', '生活習慣病予防'],
                        'method' => ['サプリメント', '運動', '食事制限', 'マインドフルネス', '定期検診'],
                        'effectiveness' => ['効果的', '効果なし', '個人差がある', '継続が必要', '科学的根拠が薄い'],
                        'aspect' => ['技術進歩', '費用負担', 'アクセス', '予防重視', '治療選択肢'],
                        'opinion' => ['進歩している', '課題が多い', '格差がある', '改善が必要'],
                        'lifestyle' => ['夜更かし', 'テレワーク', 'ファストフード', 'SNS依存', '運動不足'],
                        'impact' => ['悪影響', '影響なし', '適度なら問題なし', '個人差がある影響'],
                        'age' => ['20', '30', '40', '50', '60'],
                        'care' => ['健康管理', '美容ケア', '病気予防', '体力維持', 'メンタルケア'],
                        'timing' => ['適切', '早すぎる', '遅すぎる', '個人の判断次第'],
                        'trend' => ['情報', 'ブーム', '商品', 'サービス', '理論'],
                        'evaluation' => ['信頼できる', '疑問がある', '効果は限定的', '注意が必要']
                    ]
                ]
            ],
            
            // グルメ・食文化
            'food-culture' => [
                [
                    'title_templates' => [
                        '{food}の{aspect}は{evaluation}だと思いませんか？',
                        '{cuisine}と{comparison}の{difference}について{opinion}と思いませんか？',
                        '最近の{trend}は{judgment}だと思いませんか？',
                        '{situation}での{choice}は{appropriateness}だと思いませんか？',
                        '食の{value}は{importance}だと思いませんか？'
                    ],
                    'variables' => [
                        'food' => ['ラーメン', '寿司', 'カレー', 'ハンバーガー', 'ピザ', 'パスタ', '焼肉'],
                        'aspect' => ['味', '価格', '栄養価', '見た目', 'ボリューム', '話題性'],
                        'evaluation' => ['素晴らしい', '普通', '過大評価', 'コスパが良い', '改善が必要'],
                        'cuisine' => ['日本料理', '中華料理', 'イタリアン', 'フレンチ', '韓国料理'],
                        'comparison' => ['洋食', '家庭料理', 'ファストフード', '高級料理', '創作料理'],
                        'difference' => ['技術的差', '文化的背景', '健康への影響', 'コスト'],
                        'opinion' => ['興味深い', '当然', '驚くべき', '改善余地がある'],
                        'trend' => ['グルメブーム', '健康志向', 'インスタ映え', 'デリバリー', 'テイクアウト'],
                        'judgment' => ['良い傾向', '一時的', '問題がある', '多様性がある'],
                        'situation' => ['デート', '接待', '家族食事', '一人飯', '友人との食事'],
                        'choice' => ['高級店', 'ファミレス', '立ち食い', '手作り', 'コンビニ弁当'],
                        'appropriateness' => ['適切', '不適切', '場合による', '個人の自由'],
                        'value' => ['安全性', '地産地消', '伝統継承', '国際化', '効率性'],
                        'importance' => ['重要', '二の次', 'バランス次第', '時代で変わる']
                    ]
                ]
            ],
            
            // 交通・移動
            'transportation' => [
                [
                    'title_templates' => [
                        '{transport}の{issue}は{severity}だと思いませんか？',
                        '{situation}での{choice}は{appropriateness}だと思いませんか？',
                        '交通{rule}について{opinion}と思いませんか？',
                        '{technology}の導入は{necessity}だと思いませんか？',
                        '{group}の{behavior}は{judgment}だと思いませんか？'
                    ],
                    'variables' => [
                        'transport' => ['電車', 'バス', '自転車', '自動車', 'タクシー', '飛行機'],
                        'issue' => ['遅延', '混雑', '料金', '安全性', '環境負荷', 'マナー'],
                        'severity' => ['深刻', '普通', '改善されている', '仕方ない', '対策が必要'],
                        'situation' => ['通勤', '旅行', '緊急時', '悪天候', '深夜', '短距離移動'],
                        'choice' => ['公共交通', 'マイカー', '徒歩', 'シェアリング', 'レンタル'],
                        'appropriateness' => ['最適', '非効率', '環境に優しい', 'コスパが良い'],
                        'rule' => ['速度制限', '駐車規制', '自転車通行', '歩行者優先', '信号システム'],
                        'opinion' => ['適切', '厳しすぎる', '緩すぎる', '時代に合わない', '改善が必要'],
                        'technology' => ['自動運転', 'EV', 'MaaS', 'ライドシェア', 'ドローン配送'],
                        'necessity' => ['急務', '検討すべき', '時期尚早', '不要', '慎重に進めるべき'],
                        'group' => ['高齢ドライバー', '自転車利用者', '歩行者', '配達員', '観光客'],
                        'behavior' => ['運転マナー', '駐車方法', '歩行態度', '乗車態度', '待機方法'],
                        'judgment' => ['問題がある', '理解できる', '改善が必要', '教育すべき', '取り締まるべき']
                    ]
                ]
            ],
            
            // 住環境・地域
            'housing-community' => [
                [
                    'title_templates' => [
                        '{housing}の{issue}は{severity}だと思いませんか？',
                        '{community}活動への{participation}は{necessity}だと思いませんか？',
                        '近隣の{problem}について{opinion}と思いませんか？',
                        '{area}と{comparison}の{difference}は{significance}だと思いませんか？',
                        '住まいの{aspect}は{priority}だと思いませんか？'
                    ],
                    'variables' => [
                        'housing' => ['マンション', '一戸建て', '賃貸', '社宅', 'シェアハウス', '実家'],
                        'issue' => ['騒音', '駐車場', 'ペット問題', '管理費', '修繕', '防犯'],
                        'severity' => ['深刻', '普通', '些細', '我慢できる', '対策が必要'],
                        'community' => ['町内会', '自治会', 'PTA', '防災', '清掃', '祭り'],
                        'participation' => ['積極参加', '最低限参加', '不参加', '選択参加'],
                        'necessity' => ['義務', '任意', '時代遅れ', '重要', '負担が大きい'],
                        'problem' => ['ゴミ出し', '駐輪', '子どもの声', 'ペットの鳴き声', '工事音'],
                        'opinion' => ['我慢すべき', '対策が必要', '個人の問題', '話し合いで解決'],
                        'area' => ['都市部', '郊外', '地方', '新興住宅地', '古い住宅街'],
                        'comparison' => ['他の地域', '以前の住環境', '理想的な環境', '実家の環境'],
                        'difference' => ['生活の質', '利便性', 'コミュニティ', '費用', '将来性'],
                        'significance' => ['大きい', '小さい', '個人の価値観次第', '慣れの問題'],
                        'aspect' => ['立地', '広さ', '設備', '近隣関係', '将来性', '資産価値'],
                        'priority' => ['最重要', '重要', '二の次', '妥協できる', '個人差がある']
                    ]
                ]
            ],
            
            // その他のコミュニティも同様に追加
            'children-spa' => [
                [
                    'title_templates' => [
                        '{age}歳の{gender}の子を{facility}に連れて行くのは{appropriateness}だと思いませんか？',
                        'シングル{parent}の{situation}について{opinion}と思いませんか？',
                        '子どもの{aspect}はいつまで{guidance}べきだと思いませんか？'
                    ],
                    'variables' => [
                        'age' => ['3', '4', '5', '6', '7', '8'],
                        'gender' => ['男の子', '女の子', '異性の子', '同性の子'],
                        'facility' => ['温泉', '銭湯', 'プール', '更衣室', 'トイレ'],
                        'appropriateness' => ['問題ない', '微妙', '避けるべき', '施設による', '周りの理解次第'],
                        'parent' => ['ファザー', 'マザー', '親'],
                        'situation' => ['入浴問題', '着替え問題', '外出の悩み', '周囲の目', '子どもの成長'],
                        'opinion' => ['理解すべき', '配慮が必要', '仕方ない', '工夫が必要'],
                        'aspect' => ['入浴', '着替え', '外出', '買い物', 'トイレ'],
                        'guidance' => ['付き添う', '見守る', '一人にする', '教える']
                    ]
                ]
            ],
            
            'ladies-day' => [
                [
                    'title_templates' => [
                        '{service}の女性割引は{judgment}だと思いませんか？',
                        '{establishment}の{discount}について{opinion}と思いませんか？',
                        'ジェンダー別{treatment}は{appropriateness}だと思いませんか？'
                    ],
                    'variables' => [
                        'service' => ['映画', '美容院', 'レストラン', 'カラオケ', 'ジム', 'ゴルフ'],
                        'judgment' => ['差別的', '企業戦略', '問題ない', '時代遅れ', '見直すべき'],
                        'establishment' => ['映画館', '美容室', '飲食店', '娯楽施設', 'スポーツ施設'],
                        'discount' => ['レディースデー', 'メンズデー', '学割', 'シニア割', '平日割'],
                        'opinion' => ['賛成', '反対', '条件次第', '理解できる', '複雑'],
                        'treatment' => ['料金設定', 'サービス内容', '営業時間', '利用制限', '特典'],
                        'appropriateness' => ['適切', '不適切', '時代に合わない', '見直しが必要']
                    ]
                ]
            ]
        ];
    }
    
    private function generateTitle(array $template): string
    {
        $titleTemplate = $template['title_templates'][array_rand($template['title_templates'])];
        
        // テンプレート内の変数を置換
        return preg_replace_callback('/\{(\w+)\}/', function($matches) use ($template) {
            $variable = $matches[1];
            if (isset($template['variables'][$variable])) {
                return $template['variables'][$variable][array_rand($template['variables'][$variable])];
            }
            return $matches[0];
        }, $titleTemplate);
    }
    
    private function generateContent(array $template): string
    {
        if (!isset($template['content_templates'])) {
            return '皆さんのご意見をお聞かせください。様々な視点からの建設的な議論をお待ちしています。';
        }
        
        $contentTemplate = $template['content_templates'][array_rand($template['content_templates'])];
        
        return preg_replace_callback('/\{(\w+)\}/', function($matches) use ($template) {
            $variable = $matches[1];
            if (isset($template['variables'][$variable])) {
                return $template['variables'][$variable][array_rand($template['variables'][$variable])];
            }
            return $matches[0];
        }, $contentTemplate);
    }
    
    private function getRandomFlair(): string
    {
        $flairs = [
            '🔥激論中', '💙賛成多数', '💔反対多数', '⚖️意見分裂', '📊データ求む',
            '❤️体験談', '🤔考察', '📰ニュース', '💡提案', '🎯議論',
            '📈話題', '💬雑談', '🆕新規', '⭐注目', '🎪エンタメ',
            '📚教養', '💰お金', '🏠生活', '👥人間関係', '🌟トレンド'
        ];
        
        return $flairs[array_rand($flairs)];
    }
}
