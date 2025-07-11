# 賛否両論.com

Laravel + Inertia.js + React で構築された議論プラットフォーム

## 🚀 本番環境でのサンプルデータ作成

本番環境にサンプル議題を追加するには、以下の手順でシーダーを実行してください：

### Laravel Cloud環境でのシーダー実行

#### 方法1: ブラウザからの実行（推奨）

1. **現在のデータ状況を確認**
   - https://sanpi-ryoron-website-mvp-anlmgn.laravel.cloud/admin/seed-status

2. **シーダー実行**
   - https://sanpi-ryoron-website-mvp-anlmgn.laravel.cloud/admin/seed?password=sanpi-ryoron-2024

#### 方法2: Laravel Cloudダッシュボード

1. **Laravel Cloudダッシュボードにアクセス**
   - https://cloud.laravel.com/ にログイン
   - プロジェクト「sanpi-ryoron-website-mvp」を選択

2. **シーダー実行コマンド**
   ```bash
   php artisan db:seed
   ```

3. **特定のシーダーのみ実行する場合**
   ```bash
   # コミュニティデータのみ
   php artisan db:seed --class=CommunitySeeder
   
   # 議題データのみ  
   php artisan db:seed --class=TopicSeeder
   
   # コメントデータのみ
   php artisan db:seed --class=CommentSeeder
   ```

### 含まれるサンプルデータ

**コミュニティ (5つ):**
- デート代の支払い議論
- 女性専用車両議論  
- 子どもの温泉問題
- レディースデー議論
- 電車マナー議論

**議題 (8つ):**
- デート代は男性が全額払うべき？
- 女性専用車両は男性差別？
- 6歳の異性の子どもを温泉に連れて行くのは問題ない？
- レディースデーは男性差別？
- 電車内でのメイクはマナー違反？
- など...

**ユーザー:**
- テストユーザー 100名
- 投票とコメント用のダミーデータ

### 注意事項

- シーダーは一度だけ実行してください
- 既存データがある場合は重複する可能性があります
- 本番環境での実行前に必ずバックアップを取ることをお勧めします

## 🛠️ 開発環境でのセットアップ

```bash
# 依存関係のインストール
composer install
npm install

# 環境設定
cp .env.example .env
php artisan key:generate

# データベース作成
php artisan migrate
php artisan db:seed

# アセットビルド
npm run build

# 開発サーバー起動
php artisan serve
```

## 📁 プロジェクト構成

- **Backend**: Laravel 11
- **Frontend**: React + TypeScript + Inertia.js
- **UI**: Tailwind CSS + shadcn/ui
- **Authentication**: WorkOS
- **Database**: PostgreSQL (本番) / SQLite (開発) 