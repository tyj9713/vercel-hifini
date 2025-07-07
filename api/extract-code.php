<?php
// 设置CORS头部
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '只允许POST请求']);
    exit;
}

// 获取线程ID
$input = json_decode(file_get_contents('php://input'), true);
$tid = isset($input['tid']) ? trim($input['tid']) : (isset($_POST['tid']) ? trim($_POST['tid']) : '');

if (empty($tid)) {
    echo json_encode(['success' => false, 'message' => '线程ID不能为空']);
    exit;
}

// 加密函数
function encryptCode($text) {
    if (empty($text)) return '';
    
    // 密钥 - 可以更改这个值来改变加密强度
    $key = 'HiFiNi2024MusicForum';
    
    // 第一步：异或加密
    $encrypted = '';
    $keyLength = strlen($key);
    for ($i = 0; $i < strlen($text); $i++) {
        $encrypted .= chr(ord($text[$i]) ^ ord($key[$i % $keyLength]));
    }
    
    // 第二步：Base64编码
    $base64 = base64_encode($encrypted);
    
    // 第三步：字符串反转
    $reversed = strrev($base64);
    
    // 第四步：添加随机前缀和后缀混淆
    $prefix = substr(md5(mt_rand()), 0, 6);
    $suffix = substr(md5(mt_rand()), 0, 6);
    
    return $prefix . $reversed . $suffix;
}

// 生成假的混淆数据
function generateFakeData() {
    $fakeStrings = [
        'aGVsbG93b3JsZA==',
        'bXVzaWNmb3J1bQ==',
        'aGlmaW5pcGhw',
        'ZXh0cmFjdGNvZGU=',
        'YmFpZHVuZXRkaXNr',
        'bGFuem91Y2xvdWQ=',
        'd2VidXBsb2Fk',
        'ZG93bmxvYWRsaW5r'
    ];
    
    return $fakeStrings[array_rand($fakeStrings)] . substr(md5(mt_rand()), 0, 8);
}

try {
    $pdo = getDatabase();
    
    // 获取提取码
    $sql = "SELECT baidu_extract_code, lanzou_extract_code
            FROM threads 
            WHERE tid = :tid 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':tid', $tid, PDO::PARAM_STR);
    $stmt->execute();
    
    $thread = $stmt->fetch();
    
    if (!$thread) {
        echo json_encode(['success' => false, 'message' => '未找到该线程']);
        exit;
    }
    
    // 加密提取码
    $codes = [
        'baidu' => $thread['baidu_extract_code'] ? encryptCode($thread['baidu_extract_code']) : generateFakeData(),
        'lanzou' => $thread['lanzou_extract_code'] ? encryptCode($thread['lanzou_extract_code']) : generateFakeData()
    ];
    
    // 调试信息（生产环境可以删除）
    if (getenv('APP_ENV') !== 'production') {
        error_log("TID: $tid, Baidu: " . ($thread['baidu_extract_code'] ?: 'empty') . ", Lanzou: " . ($thread['lanzou_extract_code'] ?: 'empty'));
    }
    
    // 添加额外的混淆数据
    $response = [
        'success' => true,
        'codes' => $codes,
        'tid' => $tid,
        'timestamp' => time(),
        'hash' => md5($tid . time()),
        'data' => [
            'token' => generateFakeData(),
            'session' => generateFakeData(),
            'verify' => generateFakeData()
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    
    // 在生产环境中隐藏详细错误信息
    if (getenv('APP_ENV') === 'production') {
        error_log('Extract code API error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '服务器内部错误，请稍后重试'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '获取提取码失败: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
}
?> 