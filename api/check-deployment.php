<?php
// 部署状态检查脚本
// 访问此文件以检查应用程序的运行状态

header('Content-Type: application/json; charset=utf-8');

$status = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'environment' => getenv('APP_ENV') ?: 'development',
    'checks' => []
];

// 检查配置文件
$status['checks']['config_file'] = file_exists('config.php') ? 'OK' : 'ERROR';

// 检查数据库连接
try {
    require_once 'config.php';
    $pdo = getDatabase();
    $status['checks']['database_connection'] = 'OK';
    
    // 检查数据库表
    $tables = ['threads', 'download_links'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            $status['checks']['table_' . $table] = "OK ($count records)";
        } catch (Exception $e) {
            $status['checks']['table_' . $table] = 'ERROR: ' . $e->getMessage();
        }
    }
    
} catch (Exception $e) {
    $status['checks']['database_connection'] = 'ERROR: ' . $e->getMessage();
}

// 检查必要的PHP扩展
$extensions = ['pdo', 'pdo_mysql', 'json'];
foreach ($extensions as $ext) {
    $status['checks']['extension_' . $ext] = extension_loaded($ext) ? 'OK' : 'ERROR';
}

// 检查写入权限（如果需要）
$status['checks']['write_permissions'] = is_writable('.') ? 'OK' : 'WARNING';

// 检查环境变量
$env_vars = ['DB_HOST', 'DB_USER', 'DB_NAME'];
foreach ($env_vars as $var) {
    $value = getenv($var);
    $status['checks']['env_' . $var] = $value ? 'OK' : 'WARNING';
}

// 计算整体状态
$error_count = 0;
$warning_count = 0;

foreach ($status['checks'] as $check => $result) {
    if (strpos($result, 'ERROR') !== false) {
        $error_count++;
    } elseif (strpos($result, 'WARNING') !== false) {
        $warning_count++;
    }
}

if ($error_count > 0) {
    $status['overall_status'] = 'ERROR';
    $status['message'] = "发现 $error_count 个错误，$warning_count 个警告";
} elseif ($warning_count > 0) {
    $status['overall_status'] = 'WARNING';
    $status['message'] = "发现 $warning_count 个警告";
} else {
    $status['overall_status'] = 'OK';
    $status['message'] = '所有检查通过';
}

// 如果是通过浏览器访问，显示友好的HTML页面
if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false) {
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>部署状态检查</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100">
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold mb-6 text-gray-800">HiFiNi PHP 部署状态检查</h1>
                
                <div class="mb-6">
                    <div class="flex items-center mb-2">
                        <span class="text-sm font-semibold text-gray-600 mr-2">整体状态:</span>
                        <span class="px-3 py-1 rounded-full text-sm font-medium <?php 
                            echo $status['overall_status'] === 'OK' ? 'bg-green-100 text-green-800' : 
                                ($status['overall_status'] === 'WARNING' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                        ?>">
                            <?php echo $status['overall_status']; ?>
                        </span>
                    </div>
                    <p class="text-gray-600"><?php echo $status['message']; ?></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($status['checks'] as $check => $result): ?>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700"><?php echo ucfirst(str_replace('_', ' ', $check)); ?></span>
                            <span class="px-2 py-1 text-xs rounded <?php 
                                echo strpos($result, 'ERROR') !== false ? 'bg-red-100 text-red-700' : 
                                    (strpos($result, 'WARNING') !== false ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700');
                            ?>">
                                <?php echo $result; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <h3 class="font-semibold text-blue-800 mb-2">系统信息</h3>
                    <div class="text-sm text-blue-700">
                        <p>PHP版本: <?php echo $status['php_version']; ?></p>
                        <p>服务器: <?php echo $status['server_software']; ?></p>
                        <p>环境: <?php echo $status['environment']; ?></p>
                        <p>检查时间: <?php echo $status['timestamp']; ?></p>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <a href="/" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        返回首页
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
} else {
    // 返回JSON格式
    echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?> 