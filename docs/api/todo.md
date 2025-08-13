# ToDoリスト API設計書
## 1.概要
ToDoリストを管理するためのRESTful API。  
ユーザーはToDoの作成・取得・更新・削除を行える。

### 1-1.基本情報
- **API バージョン**: v1
- **ベースURL**: `https://api.example.com/v1`
- **データ形式**: JSON
- **文字エンコーディング**: UTF-8

### 1-2.認証方式
- **認証方式**: Bearer Token (JWT)
- **ヘッダー**: `Authorization: Bearer {token}`
- **トークン有効期限**: 24時間

## 2.機能一覧
- ToDo一覧取得（ページネーション・フィルタリング・ソート対応）
- ToDo詳細取得
- ToDo作成
- ToDo更新
- ToDo削除
  
## 3.API仕様

### 3-1.ToDo一覧取得
- **URL**: `/api/todos`
- **Method**: `GET`
- **概要**: 登録されているToDoを全件取得する（ページネーション対応）
- **認証**: 必須
- **クエリパラメータ**:
  - `page` (optional): ページ番号（デフォルト: 1）
  - `limit` (optional): 1ページあたりの件数（デフォルト: 20, 最大: 100）
  - `completed` (optional): 完了状態でフィルタ（true/false）
  - `sort` (optional): ソート項目（created_at, updated_at, title）
  - `order` (optional): ソート順序（asc, desc, デフォルト: desc）

- **レスポンス例**
```json
{
  "data": [
    {
      "id": 1,
      "title": "買い物に行く",
      "description": "牛乳と卵",
      "completed": false,
      "created_at": "2025-08-10T10:00:00Z",
      "updated_at": "2025-08-10T12:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "total_pages": 1
  },
  "links": {
    "first": "/api/todos?page=1",
    "last": "/api/todos?page=1",
    "prev": null,
    "next": null
  }
}
```

- **レスポンスヘッダー**
  - `X-RateLimit-Limit`: リクエスト制限数
  - `X-RateLimit-Remaining`: 残りリクエスト数
  - `X-RateLimit-Reset`: 制限リセット時刻
  - `Cache-Control`: `public, max-age=300`

- **ステータスコード**
  - 200 OK
  - 401 Unauthorized（認証エラー）
  - 429 Too Many Requests（レート制限超過）

### 3-2.ToDo詳細取得
- **URL**: `/api/todos/{id}`
- **Method**: `GET`
- **概要**: 指定したIDのToDo詳細を取得
- **認証**: 必須
- **パスパラメータ**:
  - `id` (required): ToDoのID（整数）

- **レスポンス例**
```json
{
  "data": {
    "id": 1,
    "title": "買い物に行く",
    "description": "牛乳と卵",
    "completed": false,
    "created_at": "2025-08-10T10:00:00Z",
    "updated_at": "2025-08-10T12:00:00Z"
  }
}
```

- **レスポンスヘッダー**
  - `Cache-Control`: `public, max-age=600`

- **ステータスコード**
  - 200 OK
  - 401 Unauthorized（認証エラー）
  - 404 Not Found（存在しない場合）

### 3-3.ToDo作成
- **URL**: `/api/todos`
- **Method**: `POST`
- **概要**: 新しいToDoを登録する
- **認証**: 必須
- **Content-Type**: `application/json`

- **リクエストボディ**
```json
{
  "title": "掃除をする",
  "description": "リビングとキッチン",
  "completed": false
}
```

- **レスポンス例**
```json
{
  "data": {
    "id": 2,
    "title": "掃除をする",
    "description": "リビングとキッチン",
    "completed": false,
    "created_at": "2025-08-13T09:00:00Z",
    "updated_at": "2025-08-13T09:00:00Z"
  },
  "message": "Todo created successfully"
}
```

- **ステータスコード**
  - 201 Created
  - 400 Bad Request（リクエスト形式エラー）
  - 401 Unauthorized（認証エラー）
  - 422 Unprocessable Entity（バリデーションエラー）

### 3-4.ToDo更新
- **URL**: `/api/todos/{id}`
- **Method**: `PUT` または `PATCH`
- **概要**: 指定したToDoを更新
- **認証**: 必須
- **パスパラメータ**:
  - `id` (required): ToDoのID（整数）
- **Content-Type**: `application/json`

- **リクエストボディ**
```json
{
  "completed": true
}
```

- **レスポンス例**
```json
{
  "data": {
    "id": 1,
    "title": "買い物に行く",
    "description": "牛乳と卵",
    "completed": true,
    "created_at": "2025-08-10T10:00:00Z",
    "updated_at": "2025-08-13T09:10:00Z"
  },
  "message": "Todo updated successfully"
}
```

- **ステータスコード**
  - 200 OK
  - 400 Bad Request（リクエスト形式エラー）
  - 401 Unauthorized（認証エラー）
  - 404 Not Found（存在しない場合）
  - 422 Unprocessable Entity（バリデーションエラー）

### 3-5.ToDo削除
- **URL**: `/api/todos/{id}`
- **Method**: `DELETE`
- **概要**: 指定したToDoを削除
- **認証**: 必須
- **パスパラメータ**:
  - `id` (required): ToDoのID（整数）

- **レスポンス例**
```json
{
  "message": "Todo deleted successfully",
  "status": "success"
}
```

- **ステータスコード**
  - 200 OK
  - 401 Unauthorized（認証エラー）
  - 404 Not Found（存在しない場合）

## 4.エラーレスポンス

### 4-1.バリデーションエラー（422）
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "title": [
      "The title field is required."
    ],
    "title": [
      "The title may not be greater than 255 characters."
    ]
  }
}
```

### 4-2.認証エラー（401）
```json
{
  "message": "Unauthenticated.",
  "error": "UNAUTHENTICATED"
}
```

### 4-3.レート制限エラー（429）
```json
{
  "message": "Too many requests.",
  "error": "RATE_LIMIT_EXCEEDED",
  "retry_after": 60
}
```

### 4-4.サーバーエラー（500）
```json
{
  "message": "Internal server error.",
  "error": "INTERNAL_ERROR"
}
```

## 5.バリデーションルール
| フィールド  | 型      | 必須 | 制約               | 説明           |
| ----------- | ------- | ---- | ------------------ | -------------- |
| title       | string  | ○    | 最大255文字        | ToDoのタイトル |
| description | string  | ×    | 最大1000文字       | ToDoの詳細説明 |
| completed   | boolean | ×    | デフォルトは false | 完了状態       |

## 6.認証・認可

### 6-1.認証フロー
1. ユーザーがログインAPIで認証情報を送信
2. サーバーがJWTトークンを発行
3. 以降のAPIリクエストで`Authorization`ヘッダーにトークンを設定

### 6-2.トークン形式
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### 6-3.権限管理
- ユーザーは自分のToDoのみアクセス可能
- 他のユーザーのToDoへのアクセスは403 Forbidden

## 7.レート制限
- **制限**: 1分間に100リクエスト
- **ヘッダー**: `X-RateLimit-*`で制限情報を返却
- **超過時**: 429 Too Many Requests

## 8.キャッシュ戦略
- **GET /api/todos**: 5分間キャッシュ
- **GET /api/todos/{id}**: 10分間キャッシュ
- **POST/PUT/DELETE**: キャッシュ無効化

## 9.セキュリティ
- HTTPS通信必須
- CORS設定による適切なオリジン制限
- SQLインジェクション対策
- XSS対策
- CSRF対策


