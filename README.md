# HiFiNi 音乐搜索系统

一个基于PHP开发的高品质音乐资源搜索系统，使用TailwindCSS设计的响应式界面，支持手机和电脑访问。

## 功能特点

✅ **响应式设计** - 完美适配手机和电脑
✅ **智能搜索** - 支持标题、作者、内容多字段搜索
✅ **分页加载** - 支持大量数据的分页展示和"加载更多"
✅ **实时搜索** - 输入关键词即时搜索（防抖优化）
✅ **扁平化界面** - 现代化的UI设计
✅ **AJAX无刷新** - 流畅的用户体验
✅ **提取码获取** - 一键获取网盘提取码
✅ **详情模态框** - 快速查看帖子详情
✅ **搜索统计** - 显示搜索结果数量和分页信息

## 技术栈

- **前端**: HTML5 + TailwindCSS + Alpine.js
- **后端**: PHP 7.4+ + MySQL 5.7+
- **数据库**: MySQL (utf8mb4)
- **Web服务器**: Apache/Nginx

## 安装步骤

### 1. 环境要求

- PHP 7.4 或更高版本
- MySQL 5.7 或更高版本
- Apache/Nginx Web服务器
- PDO MySQL扩展

### 2. 下载代码

将所有PHP文件上传到您的Web服务器目录。

### 3. 配置数据库

编辑 `config.php` 文件，修改数据库配置：

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password'); // 修改为您的MySQL密码
define('DB_NAME', 'hifini_db');
```

### 4. 导入数据

使用之前创建的 `import_to_mysql.py` 脚本导入JSON数据到MySQL数据库：

```bash
python import_to_mysql.py
```

### 5. 设置Web服务器

确保Web服务器已启用：
- mod_rewrite (Apache)
- PHP-FPM (Nginx)

### 6. 访问网站

在浏览器中访问您的网站域名即可开始使用。

## 文件结构

```
hifini-search/
├── index.php              # 主页面
├── config.php             # 数据库配置
├── search.php             # 搜索API
├── detail.php             # 详情API
├── get_extract_code.php   # 获取提取码API
├── .htaccess              # Apache配置
├── README.md              # 说明文档
└── database_structure.md  # 数据库结构文档
```

## 使用方法

### 1. 搜索音乐

在首页搜索框中输入关键词，系统支持以下搜索范围：
- **歌曲名称** - 在标题中搜索
- **艺术家名称** - 在作者字段中搜索
- **专辑名称** - 在标题和内容中搜索
- **内容关键词** - 在帖子内容中搜索

**搜索特性**：
- 🔍 **智能排序** - 标题匹配优先，其次作者匹配，最后内容匹配
- 📄 **分页加载** - 默认显示100条结果，支持"加载更多"
- ⚡ **实时搜索** - 输入时自动搜索（300ms防抖）
- 📊 **结果统计** - 显示搜索结果总数和分页信息

### 2. 查看详情

点击搜索结果中的任一项，会弹出详情模态框，显示完整的帖子内容。

### 3. 获取提取码

在详情页面中，点击"点击获取提取码"按钮，系统会显示：
- 百度网盘提取码
- 蓝奏云提取码

## API接口

### 搜索接口

**URL**: `search.php`
**方法**: POST
**参数**: 
- `query`: 搜索关键词（必需）
- `page`: 页码（可选，默认1）
- `limit`: 每页数量（可选，默认100，最大200）

**返回示例**:
```json
{
  "success": true,
  "results": [
    {
      "id": 1,
      "tid": "123",
      "title": "歌曲名称",
      "author": "hifini",
      "date": "2023-01-01 12:00:00",
      "content": "帖子内容..."
    }
  ],
  "total": 1500,
  "current_page": 1,
  "per_page": 100,
  "total_pages": 15,
  "has_next_page": true,
  "has_previous_page": false,
  "query": "搜索词"
}
```

### 详情接口

**URL**: `detail.php`
**方法**: POST
**参数**: 
- `tid`: 线程ID

**返回示例**:
```json
{
  "success": true,
  "result": {
    "id": 1,
    "tid": "123",
    "title": "歌曲名称",
    "author": "hifini",
    "date": "2023-01-01 12:00:00",
    "content": "完整内容...",
    "baidu_extract_code": "1234",
    "lanzou_extract_code": "abcd",
    "download_links": [...]
  }
}
```

### 提取码接口

**URL**: `get_extract_code.php`
**方法**: POST
**参数**: 
- `tid`: 线程ID

**返回示例**:
```json
{
  "success": true,
  "codes": {
    "baidu": "1234",
    "lanzou": "abcd"
  },
  "tid": "123"
}
```

## 自定义配置

### 修改网站信息

在 `config.php` 中修改：
```php
define('SITE_TITLE', 'HiFiNi 音乐搜索');
define('SITE_DESCRIPTION', '高品质音乐资源搜索平台');
```

### 修改搜索结果数量

在 `search.php` 中修改：
```php
$limit = isset($_POST['limit']) ? min(200, max(10, intval($_POST['limit']))) : 100;
// 默认100条，最多200条，最少10条
```

### 修改界面颜色

在 `index.php` 中修改TailwindCSS配置：
```javascript
tailwind.config = {
    theme: {
        extend: {
            colors: {
                primary: '#3B82F6',  // 主色调
                secondary: '#6B7280', // 次要色调
            }
        }
    }
}
```

## 常见问题

### Q: 搜索没有结果？
A: 请确保：
1. 数据库已正确导入
2. 数据库连接配置正确
3. 搜索关键词存在于数据库中

### Q: 提取码获取失败？
A: 请确保：
1. 对应的帖子存在提取码数据
2. 数据库中extract_codes字段有值

### Q: 界面显示不正常？
A: 请确保：
1. 网络连接正常（需要加载TailwindCSS）
2. 浏览器支持现代JavaScript特性

### Q: 如何添加新的搜索字段？
A: 修改 `search.php` 中的SQL语句：
```sql
WHERE title LIKE :query OR content LIKE :query
```

## 许可证

MIT License - 您可以自由使用、修改和分发此代码。

## 贡献

欢迎提交Issue和Pull Request来改进这个项目。

## 联系方式

如有问题，请创建GitHub Issue。

## Vercel部署说明

### 方法一：通过Vercel CLI

1. 安装Vercel CLI：
```bash
npm install -g vercel
```

2. 部署项目：
```bash
vercel
```

### 方法二：通过GitHub集成

1. 将项目推送到GitHub
2. 在Vercel控制台中导入项目
3. 配置环境变量
4. 部署完成

### 环境变量配置

在Vercel控制台中配置以下环境变量：

```
DB_HOST=your_database_host
DB_USER=your_database_user
DB_PASS=your_database_password
DB_NAME=your_database_name
DB_CHARSET=utf8mb4
SITE_TITLE=HiFiNi 快照搜索
SITE_DESCRIPTION=高品质音乐资源搜索平台
APP_ENV=production
```

### 推荐的数据库服务

- **PlanetScale**: 无服务器MySQL平台
- **Railway**: 支持MySQL的云平台
- **Supabase**: 开源的Firebase替代方案
- **Amazon RDS**: AWS托管的关系数据库服务

## 数据库结构

### threads表
```sql
CREATE TABLE threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tid VARCHAR(255) NOT NULL UNIQUE,
    title TEXT NOT NULL,
    author VARCHAR(255),
    date DATETIME,
    content LONGTEXT,
    url TEXT,
    baidu_extract_code VARCHAR(255),
    lanzou_extract_code VARCHAR(255),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### download_links表
```sql
CREATE TABLE download_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_tid VARCHAR(255) NOT NULL,
    link_text TEXT,
    link_url TEXT,
    link_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_tid) REFERENCES threads(tid)
);
```

## 缓存配置

项目配置了Vercel Edge Cache，API响应缓存5分钟：

```json
{
  "source": "/api/(.*)",
  "headers": [
    {
      "key": "Cache-Control",
      "value": "s-maxage=300, stale-while-revalidate=60"
    }
  ]
}
```

## 安全特性

- XSS保护
- 内容类型嗅探保护
- 点击劫持保护
- 错误信息过滤（生产环境）

## 文件结构

```
hifini-search/
├── index.php              # 主页面
├── config.php             # 数据库配置
├── search.php             # 搜索API
├── detail.php             # 详情API
├── get_extract_code.php   # 获取提取码API
├── .htaccess              # Apache配置
├── README.md              # 说明文档
└── database_structure.md  # 数据库结构文档
```

## 许可证

MIT License 