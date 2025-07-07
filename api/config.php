<?php
// 数据库配置 - 支持环境变量
define('DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: 'your_password');
define('DB_NAME', $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'hifini_db');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4');

// 创建数据库连接
function getDatabase() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        // 在生产环境中记录错误但不暴露详细信息
        error_log("数据库连接失败: " . $e->getMessage());
        if (getenv('APP_ENV') === 'production') {
            die("数据库连接失败，请稍后重试");
        } else {
            die("数据库连接失败: " . $e->getMessage());
        }
    }
}

// 网站配置
define('SITE_TITLE', $_ENV['SITE_TITLE'] ?? getenv('SITE_TITLE') ?: 'HiFiNi 快照搜索');
define('SITE_DESCRIPTION', $_ENV['SITE_DESCRIPTION'] ?? getenv('SITE_DESCRIPTION') ?: '高品质音乐资源搜索平台');
?> 