# 認証API仕様書

## 概要
Next.jsフロントエンドと連携するLaravel Sanctum認証APIの仕様書です。

## 基本情報
- **認証方式**: Laravel Sanctum (Bearer Token)
- **ベースURL**: `http://localhost/api`
- **データ形式**: JSON
- **文字エンコーディング**: UTF-8

## APIエンドポイント

### 1. ユーザー登録

#### リクエスト
- **URL**: `/auth/register`
- **Method**: `POST`
- **Content-Type**: `application/json`

#### リクエストボディ
```json
{
  "name": "テストユーザー",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### レスポンス（成功）
```json
{
  "message": "ユーザー登録が完了しました",
  "data": {
    "user": {
      "id": 1,
      "name": "テストユーザー",
      "email": "test@example.com",
      "created_at": "2025-08-18T10:00:00Z"
    },
    "token": "1|abcdef123456...",
    "token_type": "Bearer"
  }
}
```

#### ステータスコード
- `201 Created`: 登録成功
- `422 Unprocessable Entity`: バリデーションエラー

### 2. ユーザーログイン

#### リクエスト
- **URL**: `/auth/login`
- **Method**: `POST`
- **Content-Type**: `application/json`

#### リクエストボディ
```json
{
  "email": "test@example.com",
  "password": "password123"
}
```

#### レスポンス（成功）
```json
{
  "message": "ログインに成功しました",
  "data": {
    "user": {
      "id": 1,
      "name": "テストユーザー",
      "email": "test@example.com",
      "created_at": "2025-08-18T10:00:00Z"
    },
    "token": "2|abcdef123456...",
    "token_type": "Bearer"
  }
}
```

#### ステータスコード
- `200 OK`: ログイン成功
- `401 Unauthorized`: 認証失敗
- `422 Unprocessable Entity`: バリデーションエラー

### 3. ユーザーログアウト

#### リクエスト
- **URL**: `/auth/logout`
- **Method**: `POST`
- **認証**: 必須
- **ヘッダー**: `Authorization: Bearer {token}`

#### レスポンス（成功）
```json
{
  "message": "ログアウトしました",
  "status": "success"
}
```

#### ステータスコード
- `200 OK`: ログアウト成功
- `401 Unauthorized`: 認証エラー

### 4. ユーザー情報取得

#### リクエスト
- **URL**: `/auth/user`
- **Method**: `GET`
- **認証**: 必須
- **ヘッダー**: `Authorization: Bearer {token}`

#### レスポンス（成功）
```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "テストユーザー",
      "email": "test@example.com",
      "created_at": "2025-08-18T10:00:00Z",
      "updated_at": "2025-08-18T10:00:00Z"
    }
  }
}
```

#### ステータスコード
- `200 OK`: 取得成功
- `401 Unauthorized`: 認証エラー

### 5. 認証状態確認

#### リクエスト
- **URL**: `/auth/check`
- **Method**: `GET`
- **認証**: 必須
- **ヘッダー**: `Authorization: Bearer {token}`

#### レスポンス（認証済み）
```json
{
  "message": "認証されています",
  "authenticated": true,
  "data": {
    "user": {
      "id": 1,
      "name": "テストユーザー",
      "email": "test@example.com"
    }
  }
}
```

#### レスポンス（未認証）
```json
{
  "message": "認証されていません",
  "authenticated": false
}
```

#### ステータスコード
- `200 OK`: 認証済み
- `401 Unauthorized`: 未認証

## バリデーションルール

### ユーザー登録
| フィールド | 型 | 必須 | 制約 |
|-----------|----|----|----|
| name | string | ○ | 最大255文字 |
| email | string | ○ | メール形式、一意 |
| password | string | ○ | 最小8文字 |
| password_confirmation | string | ○ | passwordと一致 |

### ユーザーログイン
| フィールド | 型 | 必須 | 制約 |
|-----------|----|----|----|
| email | string | ○ | メール形式 |
| password | string | ○ | - |

## エラーレスポンス

### バリデーションエラー（422）
```json
{
  "message": "バリデーションエラー",
  "error": "VALIDATION_ERROR",
  "errors": {
    "email": [
      "メールアドレスは必須です"
    ],
    "password": [
      "パスワードは必須です"
    ]
  }
}
```

### 認証エラー（401）
```json
{
  "message": "メールアドレスまたはパスワードが正しくありません",
  "error": "INVALID_CREDENTIALS"
}
```

### サーバーエラー（500）
```json
{
  "message": "ユーザー登録に失敗しました",
  "error": "REGISTRATION_FAILED"
}
```

## Next.jsでの使用例

### ユーザー登録
```javascript
const register = async (userData) => {
  const response = await fetch('/api/auth/register', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(userData),
  });
  
  const data = await response.json();
  
  if (response.ok) {
    // トークンを保存
    localStorage.setItem('token', data.data.token);
    return data;
  } else {
    throw new Error(data.message);
  }
};
```

### ログイン
```javascript
const login = async (credentials) => {
  const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(credentials),
  });
  
  const data = await response.json();
  
  if (response.ok) {
    // トークンを保存
    localStorage.setItem('token', data.data.token);
    return data;
  } else {
    throw new Error(data.message);
  }
};
```

### 認証が必要なAPI呼び出し
```javascript
const getUserInfo = async () => {
  const token = localStorage.getItem('token');
  
  const response = await fetch('/api/auth/user', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
    },
  });
  
  const data = await response.json();
  
  if (response.ok) {
    return data;
  } else {
    throw new Error(data.message);
  }
};
```

## セキュリティ考慮事項

1. **HTTPS通信**: 本番環境では必ずHTTPSを使用
2. **トークン管理**: フロントエンドでトークンを安全に保存
3. **パスワードポリシー**: 強力なパスワードを要求
4. **レート制限**: ログイン試行回数を制限
5. **セッション管理**: 適切なセッションタイムアウト設定

## テスト

認証APIのテストは `tests/Feature/AuthApiTest.php` に実装されています。

```bash
# 認証APIのテスト実行
php artisan test tests/Feature/AuthApiTest.php
```
