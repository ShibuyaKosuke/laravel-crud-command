# laravel-crud-command

前もって作成されたデータベースから情報を取得し、artisan コマンドで CRUD に必要なファイルを一括で出力します。

## 特徴

1. MySQLのテーブルコメント、およびカラムコメントから翻訳用のファイルを生成します。
    - resourcees/lang/{locale}/tables.php
    - resourcees/lang/{locale}/columns.php
2. MySQLのテーブル定義からバリデーションルール作成します。
    - rules/{model}.php
3. モデル作成
    - テーブルのカラムからプロパティを自動生成します。
    - 外部キー制約から **belongsTo と hasMany、belongsToMany メソッド** を出力します。
4. コントローラー作成
    - CRUDに必要なメソッドを全て出力します。
5. グローバルスコープ作成
    - 各モデルに対して一つのグローバルスコープクラスを作成します。
6. フォームリクエスト・クラス作成
    - テーブル定義から自動的にルールを出力します。
7. ビュー・コンポーザー作成
    - 外部キーの定義からフォーム部品をビューに渡すロジックを自動的に生成します。
8. ビュー作成
    - 一覧、詳細、新規作成、更新 を自動で生成します。
9. パンくずリスト作成
    - CRUDで生成したファイルには自動的にパンくずリストを出力します。
10. テンプレートのカスタマイズ
    - プロジェクトによっては、出力するテンプレートをカスタマイズする必要があると思います。その場合は、stub をお好みの形で編集しておくことが可能です。

## インストール

```bash
composer require shibuyakosuke/laravel-crud-command
```

## セットアップ

##### 1. 何よりも先に、マイグレーションファイルを作成することから始めます。例示のように、コメントと外部キーの設定を必ず行ってください。

- モデルを生成するテーブルには必ず、テーブルコメントをつけてください。
- 多対多の中間テーブルにはコメントをつけてはいけません。

テーブルコメント機能については、[diplodocker/comments-loader](https://github.com/diplodocker/comments-loader) を利用しています。

```php
use Illuminate\Database\Schema\Blueprint;

Schema::create('users', function (Blueprint $table) {
    $table->id()->comment('ID');
    $table->unsignedBigInteger('role_id')->nullable()->comment('ロールID');
    $table->unsignedBigInteger('company_id')->nullable()->comment('会社ID');
    $table->string('name')->comment('氏名');
    $table->string('email')->unique()->comment('メールアドレス');
    $table->timestamp('email_verified_at')->nullable()->comment('メール認証日時');
    $table->string('password')->comment('パスワード');
    $table->rememberToken()->comment('リメンバートークン');
    $table->timestamp('created_at')->nullable()->comment('作成日時');
    $table->timestamp('updated_at')->nullable()->comment('更新日時');
    $table->softDeletes()->comment('削除日時');

    $table->tableComment('ユーザー'); // Table comment helps you to make language files.

    // Foreign key helps you to make belongsTo methods, hasMany methods and views .
    $table->foreign('role_id')->references('id')->on('roles');
    $table->foreign('company_id')->references('id')->on('companies');
});
```

##### 2. マイグレーションを実行する

```bash
php artisan migrate
```

##### 3. config/app.php を編集して言語を設定する

```
'locale' => 'ja',
```

##### 4. リソースを出力します

```bash
php artisan crud:setup
```

##### 5. CRUDファイルを全て出力します

```bash
php artisan make:crud users
```

###### オプション

- `--force` <br>
ファイルが存在しても、上書きして出力します。
- `--api` <br>
通常のコントローラを出力せず、REST用のコントローラのみを出力します。
- `--with-api` <br>
通常のコントローラとREST用のコントローラを出力します。`--api`と同時に指定はできません。
- `--sortable` <br>
テーブルのソート機能を合わせて出力します。
- `--with-export` <br>
テーブルのエクスポート機能を合わせて出力します。
- `--with-filter` <br>
テーブルのフィルタ機能を合わせて出力します。
- `--with-trashed` <br>
論理削除したレーコードも表示します。

## その他コマンド

出力するファイルをカスタマイズする場合、以下のコマンドを実行すると、/stubs ディレクトリに .stub を拡張子にファイルが複数出力されます。
出力したファイルをカスタマイズしてください。

```bash
php artisan stub:publish
```
