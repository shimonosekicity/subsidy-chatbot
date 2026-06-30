# 下関市 補助金ナビ API

## セットアップ

1. `api/` フォルダごとサーバーに配置する
2. `data/flow.json` と `data/subsidies.json` が同階層の `data/` にあることを確認する
3. PHPが動くサーバー（PHP 7.4以上）であればそのまま動作する

## エンドポイント一覧

### フロー全件取得
```
GET /api/?endpoint=flow
```
全ノードのフローデータをJSONで返す。

---

### 特定ノード取得
```
GET /api/?endpoint=flow&node=start
```
指定したノードIDのデータだけ返す。

**レスポンス例（質問ノード）:**
```json
{
  "node_id": "start",
  "node": {
    "message": "あなたのご状況に近いものを選んでください。",
    "choices": [
      { "label": "移住・転入を考えている", "next": "migration" },
      { "label": "住宅を取得・改修したい", "next": "housing" }
    ]
  }
}
```

---

### ノード＋補助金詳細を結合（推奨）
```
GET /api/?endpoint=node&node=result_s001
```
ノード情報に加えて、そのノードに紐づく補助金の詳細情報を合わせて返す。
チャットボットのUI実装に最適。

**レスポンス例（結果ノード）:**
```json
{
  "node_id": "result_s001",
  "node": {
    "message": "移住支援金（東京23区）が対象です。",
    "subsidy_ids": ["s001"],
    "type": "result"
  },
  "subsidy_details": {
    "s001": {
      "name": "移住支援金（東京23区）",
      "summary": "...",
      "amount": "単身60万円 / 世帯100万円",
      "conditions": "...",
      "deadline": "...",
      "contact": "...",
      "url": "https://..."
    }
  }
}
```

---

### 補助金全件取得
```
GET /api/?endpoint=subsidies
```

---

### 特定補助金取得
```
GET /api/?endpoint=subsidies&id=s001
```

---

## 利用例（JavaScript）

```javascript
// ① スタートノードを取得
const res = await fetch('https://your-server.example.com/api/?endpoint=node&node=start');
const data = await res.json();

// data.node.message  → 質問テキスト
// data.node.choices  → 選択肢の配列
// data.node.type === 'result' → 結果ノード
// data.subsidy_details → 補助金詳細（結果ノードのみ）

// ② ユーザーが選択肢を選んだら次のノードIDを取得
const nextNodeId = data.node.choices[0].next;
const next = await fetch(`https://your-server.example.com/api/?endpoint=node&node=${nextNodeId}`);
```

## CORS

デフォルトで `Access-Control-Allow-Origin: *` を返します。
特定ドメインに限定する場合は `api/index.php` の冒頭を変更してください。

```php
header('Access-Control-Allow-Origin: https://your-site.example.com');
```

## 動作環境

- PHP 7.4 以上
- Apache / Nginx（mod_rewriteは不要）
