<?php
require_once 'config.php';

// 获取搜索关键词
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20; // 每页显示20条

if (empty($query)) {
    header('Location: /');
    exit;
}

try {
    $pdo = getDatabase();
    
    // 计算偏移量
    $offset = ($page - 1) * $limit;
    
    // 首先获取总数
    $countSql = "SELECT COUNT(*) as total 
                 FROM threads 
                 WHERE title LIKE :query";
    
    $countStmt = $pdo->prepare($countSql);
    $searchTerm = '%' . $query . '%';
    $countStmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
    $countStmt->execute();
    $totalCount = $countStmt->fetch()['total'];
    
    // 获取搜索结果
    $sql = "SELECT id, tid, title, author, date, content 
            FROM threads 
            WHERE title LIKE :query
            ORDER BY date DESC 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $results = $stmt->fetchAll();
    
    // 计算分页信息
    $totalPages = ceil($totalCount / $limit);
    
} catch (Exception $e) {
    $error = '搜索失败: ' . $e->getMessage();
    $results = [];
    $totalCount = 0;
    $totalPages = 0;
}

// 格式化日期
function formatDate($dateString) {
    return date('Y-m-d H:i', strtotime($dateString));
}

// 获取内容预览
function getPreview($content, $length = 150) {
    if (!$content) return '';
    $content = strip_tags($content);
    $content = preg_replace('/\s+/', ' ', $content);
    return mb_substr($content, 0, $length) . '...';
}

// 高亮搜索关键词
function highlightKeywords($text, $keywords) {
    if (!$keywords) return htmlspecialchars($text);
    $highlighted = str_ireplace($keywords, '<mark class="bg-yellow-200 text-yellow-800 px-1 rounded">' . $keywords . '</mark>', htmlspecialchars($text));
    return $highlighted;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>搜索结果: <?php echo htmlspecialchars($query); ?> - <?php echo SITE_TITLE; ?></title>
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
        .thread-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- 导航栏 -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="/" class="flex-shrink-0 flex items-center">
                        <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                            </svg>
                        </div>
                        <h1 class="text-xl font-bold text-gray-900">HiFiNi 快照论坛</h1>
                    </a>
                </div>
                <!-- 搜索框 -->
                <div class="flex-1 max-w-lg mx-8">
                    <form action="search" method="GET" class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="q"
                            value="<?php echo htmlspecialchars($query); ?>"
                            placeholder="搜索快照资源..."
                            class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all duration-200 search-glow"
                        >
                    </form>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-gray-600 hover:text-primary transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- 主要内容 -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- 搜索结果头部 -->
        <div class="bg-white rounded-lg forum-shadow p-6 mb-8">
            <div class="flex flex-col md:flex-row md:justify-between md:items-center">
                <div class="mb-4 md:mb-0">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">搜索结果</h2>
                    <p class="text-gray-600">
                        关键词: "<span class="font-semibold text-primary"><?php echo htmlspecialchars($query); ?></span>"
                        <?php if ($totalCount > 0): ?>
                            找到 <span class="font-semibold text-accent"><?php echo $totalCount; ?></span> 个结果
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if ($totalPages > 1): ?>
                <div class="flex items-center space-x-2 text-sm text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>第 <?php echo $page; ?> 页，共 <?php echo $totalPages; ?> 页</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($error)): ?>
        <!-- 错误信息 -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8">
            <div class="flex">
                <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <p class="text-red-800"><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($results) && empty($error)): ?>
        <!-- 无结果提示 -->
        <div class="bg-white rounded-lg forum-shadow p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 text-gray-400">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.469-.526-6.097-1.47L5 15.5v-2.25M13 13.5V7.5L12 6M17 7l-3 3-3-3"/>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">未找到相关结果</h3>
            <p class="text-gray-600 mb-6">请尝试使用不同的关键词或检查拼写</p>
            <a href="/" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                重新搜索
            </a>
        </div>
        <?php endif; ?>

        <?php if (!empty($results)): ?>
        <!-- 搜索结果列表 -->
        <div class="space-y-6">
            <?php foreach ($results as $index => $result): ?>
            <div class="bg-white rounded-lg forum-shadow p-6 thread-card transition-all duration-200">
                <div class="flex items-start space-x-4">
                    <!-- 楼层编号 -->
                    <div class="flex-shrink-0 w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                        <span class="text-sm font-semibold text-gray-600"><?php echo ($page - 1) * $limit + $index + 1; ?></span>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <!-- 标题 -->
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            <a href="detail?tid=<?php echo urlencode($result['tid']); ?>" class="hover:text-primary transition-colors">
                                <?php echo highlightKeywords($result['title'], $query); ?>
                            </a>
                        </h3>
                        
                        <!-- 元信息 -->
                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 mb-3">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <?php echo htmlspecialchars($result['author']); ?>
                            </span>
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <?php echo formatDate($result['date']); ?>
                            </span>
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                                TID: <?php echo htmlspecialchars($result['tid']); ?>
                            </span>
                        </div>
                        <!-- 操作按钮 -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                    </svg>
                                    快照资源
                                </span>
                            </div>
                            <a href="detail?tid=<?php echo urlencode($result['tid']); ?>" class="inline-flex items-center px-4 py-2 text-sm font-medium text-primary hover:text-primary/80 transition-colors">
                                查看详情
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 分页导航 -->
        <?php if ($totalPages > 1): ?>
        <div class="bg-white rounded-lg forum-shadow p-6 mt-8">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center">
                <div class="mb-4 sm:mb-0">
                    <p class="text-sm text-gray-700">
                        显示第 <span class="font-medium"><?php echo ($page - 1) * $limit + 1; ?></span> 到 
                        <span class="font-medium"><?php echo min($page * $limit, $totalCount); ?></span> 条结果，
                        共 <span class="font-medium"><?php echo $totalCount; ?></span> 条
                    </p>
                </div>
                
                <div class="flex items-center space-x-2">
                    <!-- 上一页 -->
                    <?php if ($page > 1): ?>
                    <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page - 1; ?>" 
                       class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        上一页
                    </a>
                    <?php endif; ?>
                    
                    <!-- 页码 -->
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    
                    if ($start > 1) {
                        echo '<a href="?q=' . urlencode($query) . '&page=1" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors">1</a>';
                        if ($start > 2) {
                            echo '<span class="px-2 py-1 text-gray-500">...</span>';
                        }
                    }
                    
                    for ($i = $start; $i <= $end; $i++) {
                        if ($i == $page) {
                            echo '<span class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-primary border border-primary rounded-lg">' . $i . '</span>';
                        } else {
                            echo '<a href="?q=' . urlencode($query) . '&page=' . $i . '" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors">' . $i . '</a>';
                        }
                    }
                    
                    if ($end < $totalPages) {
                        if ($end < $totalPages - 1) {
                            echo '<span class="px-2 py-1 text-gray-500">...</span>';
                        }
                        echo '<a href="?q=' . urlencode($query) . '&page=' . $totalPages . '" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors">' . $totalPages . '</a>';
                    }
                    ?>
                    
                    <!-- 下一页 -->
                    <?php if ($page < $totalPages): ?>
                    <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page + 1; ?>" 
                       class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors">
                        下一页
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </main>

    <!-- 页脚 -->
    <footer class="bg-white border-t border-gray-200 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center">
                <p class="text-gray-500 text-sm">
                    © 2024 HiFiNi 快照论坛. 致力于分享高品质快照资源
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
