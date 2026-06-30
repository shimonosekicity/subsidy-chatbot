<?php
/**
 * 下関市 補助金ナビ API
 *
 * エンドポイント一覧:
 *   GET /api/?endpoint=flow                    フロー全件取得
 *   GET /api/?endpoint=flow&node=start         特定ノード取得
 *   GET /api/?endpoint=subsidies               補助金全件取得
 *   GET /api/?endpoint=subsidies&id=s001       特定補助金取得
 *   GET /api/?endpoint=node&node=start         ノード＋補助金詳細を結合して返す
 */

// --- CORS ヘッダー（必要に応じて Origin を絞る） ---
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- データファイルのパス（このファイルから見た相対パス） ---
define('DATA_DIR', __DIR__ . '/../data/');
define('FLOW_FILE',      DATA_DIR . 'flow.json');
define('SUBSIDIES_FILE', DATA_DIR . 'subsidies.json');

// --- ヘルパー ---
function load_json(string $path): array {
    if (!file_exists($path)) {
        respond(404, ['error' => 'Data file not found: ' . basename($path)]);
    }
    $data = json_decode(file_get_contents($path), true);
    if ($data === null) {
        respond(500, ['error' => 'JSON parse error: ' . basename($path)]);
    }
    return $data;
}

function respond(int $status, mixed $body): never {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// --- ルーティング ---
$endpoint = $_GET['endpoint'] ?? '';

switch ($endpoint) {

    // ── /api/?endpoint=flow[&node=<id>] ──────────────────────────────
    case 'flow':
        $flow = load_json(FLOW_FILE);
        $node = $_GET['node'] ?? null;

        if ($node !== null) {
            if (!isset($flow[$node])) {
                respond(404, ['error' => "Node '{$node}' not found"]);
            }
            respond(200, [
                'node_id' => $node,
                'node'    => $flow[$node],
            ]);
        }

        respond(200, $flow);

    // ── /api/?endpoint=subsidies[&id=<id>] ───────────────────────────
    case 'subsidies':
        $subsidies = load_json(SUBSIDIES_FILE);
        $id = $_GET['id'] ?? null;

        if ($id !== null) {
            if (!isset($subsidies[$id])) {
                respond(404, ['error' => "Subsidy '{$id}' not found"]);
            }
            respond(200, [
                'id'      => $id,
                'subsidy' => $subsidies[$id],
            ]);
        }

        respond(200, $subsidies);

    // ── /api/?endpoint=node&node=<id> ────────────────────────────────
    // ノード情報 + 結果ノードの場合は補助金詳細を結合して返す
    case 'node':
        $node_id = $_GET['node'] ?? null;
        if ($node_id === null) {
            respond(400, ['error' => 'Query parameter "node" is required']);
        }

        $flow      = load_json(FLOW_FILE);
        $subsidies = load_json(SUBSIDIES_FILE);

        if (!isset($flow[$node_id])) {
            respond(404, ['error' => "Node '{$node_id}' not found"]);
        }

        $node = $flow[$node_id];

        // result ノードに subsidy_ids があれば補助金詳細を付与
        $subsidy_details = [];
        if (isset($node['subsidy_ids']) && is_array($node['subsidy_ids'])) {
            foreach ($node['subsidy_ids'] as $sid) {
                if (isset($subsidies[$sid])) {
                    $subsidy_details[$sid] = $subsidies[$sid];
                }
            }
        }

        respond(200, [
            'node_id'         => $node_id,
            'node'            => $node,
            'subsidy_details' => $subsidy_details,
        ]);

    // ── ドキュメント ──────────────────────────────────────────────────
    case '':
    default:
        respond(200, [
            'name'    => '下関市 補助金ナビ API',
            'version' => '1.0.0',
            'endpoints' => [
                'GET /api/?endpoint=flow'                   => 'フロー全件取得',
                'GET /api/?endpoint=flow&node={id}'         => '特定ノード取得',
                'GET /api/?endpoint=subsidies'              => '補助金全件取得',
                'GET /api/?endpoint=subsidies&id={id}'      => '特定補助金取得',
                'GET /api/?endpoint=node&node={id}'         => 'ノード＋補助金詳細を結合して返す',
            ],
        ]);
}
