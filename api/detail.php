<?php
require_once 'config.php';

// 获取线程ID
$tid = isset($_GET['tid']) ? trim($_GET['tid']) : '';

if (empty($tid)) {
    header('Location: index.php');
    exit;
}

try {
    $pdo = getDatabase();
    
    // 获取线程详情
    $sql = "SELECT id, url, tid, title, author, date, content, 
                   baidu_extract_code, lanzou_extract_code, timestamp,
                   created_at, updated_at
            FROM threads 
            WHERE tid = :tid 
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':tid', $tid, PDO::PARAM_STR);
    $stmt->execute();
    
    $thread = $stmt->fetch();
    
    if (!$thread) {
        $error = '未找到该帖子';
    } else {
    // 获取下载链接
    $linksSql = "SELECT id, link_text, link_url, link_type, created_at
                 FROM download_links 
                 WHERE thread_tid = :tid
                 ORDER BY id ASC";
    
    $linksStmt = $pdo->prepare($linksSql);
    $linksStmt->bindParam(':tid', $tid, PDO::PARAM_STR);
    $linksStmt->execute();
    
    $downloadLinks = $linksStmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error = '获取详情失败: ' . $e->getMessage();
}

// 格式化日期
function formatDate($dateString) {
    return date('Y-m-d H:i:s', strtotime($dateString));
}

// 格式化内容，处理提取码按钮
function formatContent($content, $tid) {
    if (!$content) return '';
    
    // 处理多个连续的换行符，统一为单个换行
    $formatted = preg_replace('/(\r\n|\r|\n){2,}/', "\n", $content);
    
    // 处理百度网盘和夸克网盘链接
    $formatted = preg_replace_callback(
        '/(https:\/\/pan\.baidu\.com\/s\/[a-zA-Z0-9_-]+|https:\/\/pan\.quark\.cn\/s\/[a-zA-Z0-9_-]+)(\s*提取码[\s:：]*\*+[\s\S]*?点击免费获取[\s\S]*?\*+)/i',
        function($matches) use ($tid) {
            $link = $matches[1];
            $linkType = '';
            
            if (strpos($link, 'pan.baidu.com') !== false) {
                $linkType = 'baidu';
            } else if (strpos($link, 'pan.quark.cn') !== false) {
                $linkType = 'baidu'; // 夸克网盘使用百度类型的提取码
            }
            
            return '<div class="my-3 p-3 bg-gray-50 rounded-lg border">
                <div class="text-sm font-medium text-gray-700 mb-1">下载</div>
                <div class="mb-2">
                    <a href="' . $link . '" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm break-all">' . $link . '</a>
                </div>
                <div class="text-sm font-medium text-gray-700 mb-1">提取码</div>
                <button onclick="getExtractCode(\'' . $tid . '\', this, \'' . $linkType . '\')" class="extract-code-btn w-full px-3 py-1.5 bg-green-100 text-green-800 text-sm rounded border border-green-300 hover:bg-green-200 transition-colors">
                    点击免费获取
                </button>
            </div>';
        },
        $formatted
    );
    
    // 处理蓝奏云链接 - 扩展正则表达式以匹配更多情况
    $formatted = preg_replace_callback(
        '/(https:\/\/[a-zA-Z0-9.-]*\.?lanz[a-zA-Z0-9.-]*\.com\/[a-zA-Z0-9_-]+)(\s*提取码[\s:：]*\*+[\s\S]*?点击免费获取[\s\S]*?\*+)/i',
        function($matches) use ($tid) {
            $link = $matches[1];
            return '<div class="my-3 p-3 bg-gray-50 rounded-lg border">
                <div class="text-sm font-medium text-gray-700 mb-1">下载</div>
                <div class="mb-2">
                    <a href="' . $link . '" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm break-all">' . $link . '</a>
                </div>
                <div class="text-sm font-medium text-gray-700 mb-1">提取码</div>
                <button onclick="getExtractCode(\'' . $tid . '\', this, \'lanzou\')" class="extract-code-btn w-full px-3 py-1.5 bg-green-100 text-green-800 text-sm rounded border border-green-300 hover:bg-green-200 transition-colors">
                    点击免费获取
                </button>
            </div>';
        },
        $formatted
    );
    
    // 处理没有链接的提取码按钮 - 先处理有冒号的为蓝奏云
    $formatted = preg_replace(
        '/提取码[\s]*:[\s:：]*\*+[\s\S]*?点击免费获取[\s\S]*?\*+/i',
        '<div class="my-3 p-3 bg-gray-50 rounded-lg border">
            <div class="text-sm font-medium text-gray-700 mb-1">提取码</div>
            <button onclick="getExtractCode(\'' . $tid . '\', this, \'lanzou\')" class="extract-code-btn w-full px-3 py-1.5 bg-green-100 text-green-800 text-sm rounded border border-green-300 hover:bg-green-200 transition-colors">
                点击免费获取
            </button>
        </div>',
        $formatted
    );
    
    // 处理没有链接的提取码按钮 - 没有冒号的为百度/夸克
    $formatted = preg_replace(
        '/提取码[\s]*\*+[\s\S]*?点击免费获取[\s\S]*?\*+/i',
        '<div class="my-3 p-3 bg-gray-50 rounded-lg border">
            <div class="text-sm font-medium text-gray-700 mb-1">提取码</div>
            <button onclick="getExtractCode(\'' . $tid . '\', this, \'baidu\')" class="extract-code-btn w-full px-3 py-1.5 bg-green-100 text-green-800 text-sm rounded border border-green-300 hover:bg-green-200 transition-colors">
                点击免费获取
            </button>
        </div>',
        $formatted
    );
    
    // 自动将普通链接转换为可点击的链接（但不包括已经被处理的链接）
    $formatted = preg_replace(
        '/(https?:\/\/[^\s<>"\']+)(?![^<]*<\/a>)/i',
        '<a href="$1" target="_blank" class="text-blue-600 hover:text-blue-800 break-all">$1</a>',
        $formatted
    );
    
    // 最后处理HTML转义（除了我们创建的HTML标签）
    $formatted = htmlspecialchars($formatted, ENT_NOQUOTES);
    
    // 恢复我们创建的HTML标签
    $formatted = str_replace('&lt;div', '<div', $formatted);
    $formatted = str_replace('&lt;/div&gt;', '</div>', $formatted);
    $formatted = str_replace('&lt;button', '<button', $formatted);
    $formatted = str_replace('&lt;/button&gt;', '</button>', $formatted);
    $formatted = str_replace('&lt;a', '<a', $formatted);
    $formatted = str_replace('&lt;/a&gt;', '</a>', $formatted);
    $formatted = str_replace('/&gt;', '/>', $formatted);
    $formatted = str_replace('&gt;', '>', $formatted);
    $formatted = str_replace('&quot;', '"', $formatted);
    $formatted = str_replace('&amp;', '&', $formatted);
    
    // 处理换行 - 只转换单个换行符
    $formatted = nl2br($formatted);
    
    return $formatted;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($thread) ? htmlspecialchars($thread['title']) : '帖子详情'; ?> - <?php echo SITE_TITLE; ?></title>
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#6B7280',
                        accent: '#10B981',
                        dark: '#1F2937',
                        darker: '#111827',
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .forum-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .search-glow:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .post-content {
            line-height: 1.8;
        }
        .post-content p {
            margin-bottom: 1rem;
        }
        .extract-code-btn.loading {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .extract-code-btn.success {
            background-color: #BBF7D0 !important;
            color: #065F46 !important;
            border-color: #86EFAC !important;
        }
        .extract-code-btn.success:hover {
            background-color: #A7F3D0 !important;
            color: #064E3B !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- 导航栏 -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex-shrink-0 flex items-center">
                        <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                            </svg>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900">HiFiNi 音乐论坛</h1>
                    </a>
                </div>
                <!-- 搜索框 -->
                <div class="flex-1 max-w-lg mx-8">
                    <form action="search.php" method="GET" class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="q"
                            placeholder="搜索音乐资源..."
                            class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all duration-200 search-glow"
                        >
                    </form>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="history.back()" class="text-gray-600 hover:text-primary transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <a href="index.php" class="text-gray-600 hover:text-primary transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- 主要内容 -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <?php if (!empty($error)): ?>
        <!-- 错误信息 -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <div>
                    <h3 class="text-lg font-semibold text-red-800"><?php echo htmlspecialchars($error); ?></h3>
                    <div class="mt-2">
                        <a href="index.php" class="text-red-700 hover:text-red-900 font-medium">返回首页</a>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        
        <!-- 帖子内容 -->
        <div class="bg-white rounded-lg forum-shadow">
            <!-- 帖子头部 -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($thread['author']); ?></h3>
                            <p class="text-sm text-gray-500">楼主</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">发布时间</p>
                        <p class="text-sm font-medium text-gray-900"><?php echo formatDate($thread['date']); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- 帖子主体 -->
            <div class="p-6">
                <!-- 标题 -->
                <div class="mb-6">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($thread['title']); ?></h1>
                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            TID: <?php echo htmlspecialchars($thread['tid']); ?>
                        </span>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ID: <?php echo $thread['id']; ?>
                        </span>
                        <?php if (!empty($thread['url'])): ?>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            <a href="<?php echo htmlspecialchars($thread['url']); ?>" target="_blank" class="text-primary hover:text-primary/80">原帖链接</a>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- 内容 -->
                <div class="prose max-w-none post-content">
                    <div class="bg-gray-50 rounded-lg p-6">
                        <?php echo formatContent($thread['content'], $thread['tid']); ?>
                    </div>
                </div>
                
                <!-- 下载链接 -->
                <?php if (!empty($downloadLinks)): ?>
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        下载链接
                    </h3>
                    <div class="grid gap-3">
                        <?php foreach ($downloadLinks as $link): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-2 min-w-0">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                <span class="text-gray-700 text-sm break-all"><?php echo htmlspecialchars($link['link_text']); ?></span>
                            </div>
                            <a href="<?php echo htmlspecialchars($link['link_url']); ?>" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-primary hover:text-primary/80 transition-colors whitespace-nowrap flex-shrink-0">
                                访问链接
                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- 帖子底部 -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-6">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                            </svg>
                            音乐资源
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent/10 text-accent">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            高品质
                        </span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="window.print()" class="p-1.5 text-gray-500 hover:text-gray-700 transition-colors rounded-md hover:bg-gray-100" title="打印">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                        </button>
                        <button onclick="sharePost()" class="p-1.5 text-gray-500 hover:text-gray-700 transition-colors rounded-md hover:bg-gray-100" title="分享">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </main>

    <!-- 页脚 -->
    <footer class="bg-white border-t border-gray-200 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center">
                <p class="text-gray-500 text-sm">
                    © 2024 HiFiNi 音乐论坛. 致力于分享高品质音乐资源
                </p>
            </div>
        </div>
    </footer>

    <script>
        // 解密函数
        function decryptCode(encryptedText) {
            if (!encryptedText) return '';
            
            try {
                // 密钥 - 必须与后端一致
                const key = 'HiFiNi2024MusicForum';
                
                // 第一步：去除前缀和后缀（各6个字符）
                if (encryptedText.length < 12) return '';
                const withoutFix = encryptedText.substring(6, encryptedText.length - 6);
                
                // 第二步：字符串反转
                const reversed = withoutFix.split('').reverse().join('');
                
                // 第三步：Base64解码
                const base64Decoded = atob(reversed);
                
                // 第四步：异或解密
                let decrypted = '';
                for (let i = 0; i < base64Decoded.length; i++) {
                    decrypted += String.fromCharCode(
                        base64Decoded.charCodeAt(i) ^ key.charCodeAt(i % key.length)
                    );
                }
                
                // 调试信息（可以在生产环境中移除）
                // console.log('解密调试:', {
                //     原始: encryptedText,
                //     去前后缀: withoutFix,
                //     反转: reversed,
                //     解密结果: decrypted,
                //     验证结果: isValidExtractCode(decrypted)
                // });
                
                return decrypted;
            } catch (error) {
                console.error('解密失败:', error);
                return '';
            }
        }
        
        // 验证提取码格式
        function isValidExtractCode(code) {
            if (!code || typeof code !== 'string') return false;
            
            // 去除可能的空白字符
            code = code.trim();
            
            // 检查是否包含常见的提取码模式
            const patterns = [
                /^[a-zA-Z0-9]{4}$/,        // 4位字母数字 (最常见)
                /^[a-zA-Z0-9]{6}$/,        // 6位字母数字
                /^[a-zA-Z0-9]{8}$/,        // 8位字母数字
                /^[a-zA-Z]{4}$/,           // 4位字母
                /^[0-9]{4}$/,              // 4位数字
                /^[a-zA-Z0-9]{3}$/,        // 3位字母数字
                /^[a-zA-Z0-9]{5}$/,        // 5位字母数字
                /^[a-zA-Z0-9]{1,12}$/      // 1-12位字母数字 (兜底)
            ];
            
            const isValid = patterns.some(pattern => pattern.test(code));
            
            // 调试信息（可以在生产环境中移除）
            // console.log('验证提取码:', {
            //     输入: code,
            //     长度: code.length,
            //     验证结果: isValid
            // });
            
            return isValid;
        }
        
        // 获取提取码
        async function getExtractCode(tid, button, type = 'all') {
            const originalText = button.innerHTML;
            
            // 设置加载状态
            button.innerHTML = '获取中...';
            button.disabled = true;
            button.classList.add('loading');
            
            try {
                const response = await fetch('get_extract_code.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `tid=${encodeURIComponent(tid)}`
                });
                
                if (!response.ok) {
                    throw new Error('获取提取码失败');
                }
                
                const data = await response.json();
                if (data.success) {
                    // 调试信息（可以在生产环境中移除）
                    // console.log('获取到的数据:', {
                    //     请求类型: type,
                    //     返回数据: data.codes,
                    //     TID: tid
                    // });
                    
                    let codes = [];
                    let extractCode = '';
                    
                    // 根据类型获取相应的提取码
                    if (type === 'baidu' && data.codes.baidu) {
                        const decryptedBaidu = decryptCode(data.codes.baidu);
                        if (decryptedBaidu && isValidExtractCode(decryptedBaidu)) {
                            extractCode = decryptedBaidu;
                            codes.push(decryptedBaidu); // 不带文字，只显示提取码
                        }
                    } else if (type === 'lanzou') {
                        // 蓝奏云智能获取：尝试两个字段，优先使用能成功解密验证的
                        let foundValid = false;
                        
                        // 先尝试lanzou字段
                        if (data.codes.lanzou) {
                            const decryptedLanzou = decryptCode(data.codes.lanzou);
                            if (decryptedLanzou && isValidExtractCode(decryptedLanzou)) {
                                extractCode = decryptedLanzou;
                                codes.push(decryptedLanzou);
                                foundValid = true;
                            }
                        }
                        
                        // 如果lanzou字段没有有效提取码，再尝试baidu字段
                        if (!foundValid && data.codes.baidu) {
                            const decryptedBaidu = decryptCode(data.codes.baidu);
                            if (decryptedBaidu && isValidExtractCode(decryptedBaidu)) {
                                extractCode = decryptedBaidu;
                                codes.push(decryptedBaidu);
                                foundValid = true;
                            }
                        }
                    } else if (type === 'all') {
                        // 显示所有可用的提取码
                        if (data.codes.baidu) {
                            const decryptedBaidu = decryptCode(data.codes.baidu);
                            if (decryptedBaidu && isValidExtractCode(decryptedBaidu)) {
                                codes.push(`百度网盘: ${decryptedBaidu}`);
                            }
                        }
                        if (data.codes.lanzou) {
                            const decryptedLanzou = decryptCode(data.codes.lanzou);
                            if (decryptedLanzou && isValidExtractCode(decryptedLanzou)) {
                                codes.push(`蓝奏云: ${decryptedLanzou}`);
                            }
                        }
                    }
                    
                    if (codes.length > 0) {
                        // 根据类型设置不同的显示文本和样式
                        if (type === 'baidu') {
                            button.innerHTML = '' + extractCode + '';
                            button.classList.remove('loading', 'bg-green-100', 'text-green-800', 'border-green-300');
                            button.classList.add('success', 'bg-green-200', 'text-green-900', 'border-green-400');
                        } else if (type === 'lanzou') {
                            button.innerHTML = '' + extractCode + '';
                            button.classList.remove('loading', 'bg-green-100', 'text-green-800', 'border-green-300');
                            button.classList.add('success', 'bg-green-200', 'text-green-900', 'border-green-400');
                        } else {
                            button.innerHTML = codes.join(' | ');
                            button.classList.remove('loading');
                            button.classList.add('success');
                        }
                        
                        // 添加复制功能
                        button.onclick = function() {
                            if (type === 'baidu' || type === 'lanzou') {
                                copyToClipboard(extractCode);
                            } else {
                                copyToClipboard(codes.join(' | '));
                            }
                        };
                    } else {
                        let noCodeText = '';
                        if (type === 'baidu') {
                            noCodeText = '暂无提取码';
                        } else if (type === 'lanzou') {
                            noCodeText = '暂无提取码';
                        } else {
                            noCodeText = '暂无提取码';
                        }
                        button.innerHTML = noCodeText;
                        button.classList.remove('loading');
                    }
                } else {
                    throw new Error(data.message || '获取失败');
                }
            } catch (error) {
                console.error('获取提取码错误:', error);
                button.innerHTML = '获取失败';
                button.classList.remove('loading');
            } finally {
                button.disabled = false;
            }
        }
        
        // 复制到剪贴板
        function copyToClipboard(text) {
            // 清理文本，移除星号和前缀，只保留提取码
            let cleanText = text.replace(/百度网盘提取码: |蓝奏云提取码: |百度网盘: |蓝奏云: |\*{6,}/g, '').trim();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(cleanText).then(() => {
                    showToast('提取码已复制: ' + cleanText);
                }).catch(() => {
                    showToast('复制失败，请手动选择');
                });
            } else {
                // 兼容性处理
                const textArea = document.createElement('textarea');
                textArea.value = cleanText;
                textArea.style.position = 'fixed';
                textArea.style.left = '-9999px';
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    showToast('提取码已复制: ' + cleanText);
                } catch (err) {
                    showToast('复制失败，请手动选择');
                }
                document.body.removeChild(textArea);
            }
        }
        
        // 显示提示消息
        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-accent text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-opacity duration-300';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 2000);
        }
        
        // 分享帖子
        function sharePost() {
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    url: window.location.href
                });
            } else {
                // 复制链接到剪贴板
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('链接已复制到剪贴板');
                });
            }
        }
    </script>
</body>
</html> 