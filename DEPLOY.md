# Vercel部署指南

## 快速部署

### 方法一：一键部署
[![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https://github.com/your-username/hifiniphp)

### 方法二：手动部署

1. **Fork 项目到您的 GitHub 账户**

2. **登录 Vercel 控制台**
   - 访问 [vercel.com](https://vercel.com)
   - 使用 GitHub 账户登录

3. **导入项目**
   - 点击 "New Project"
   - 选择您 fork 的项目
   - 点击 "Import"

4. **配置环境变量**
   在 Vercel 控制台中添加以下环境变量：
   
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

5. **部署项目**
   - 点击 "Deploy"
   - 等待部署完成

## 项目结构

```
hifiniphp/
├── api/                    # Vercel Serverless Functions
│   ├── index.php          # 主页
│   ├── search.php         # 搜索页面
│   ├── detail.php         # 详情页面
│   ├── get_extract_code.php # 提取码API
│   ├── check-deployment.php # 部署检查
│   └── config.php         # 配置文件
├── vercel.json            # Vercel配置
├── package.json           # 项目配置
├── database.sql           # 数据库初始化脚本
├── deploy.sh              # 部署脚本 (Linux/Mac)
├── deploy.bat             # 部署脚本 (Windows)
└── README.md              # 项目说明
```

## 数据库配置

### 推荐的数据库服务

#### 1. PlanetScale (推荐)
- 免费的无服务器 MySQL 平台
- 自动扩展和备份
- 支持分支功能

**设置步骤：**
1. 访问 [planetscale.com](https://planetscale.com)
2. 创建账户并新建数据库
3. 获取连接字符串
4. 在 Vercel 中配置环境变量

#### 2. Railway
- 支持 MySQL 的云平台
- 简单易用

**设置步骤：**
1. 访问 [railway.app](https://railway.app)
2. 创建 MySQL 服务
3. 获取连接信息
4. 在 Vercel 中配置环境变量

#### 3. Supabase
- 开源的 Firebase 替代方案
- 提供 PostgreSQL 数据库

**设置步骤：**
1. 访问 [supabase.com](https://supabase.com)
2. 创建新项目
3. 获取数据库连接信息
4. 在 Vercel 中配置环境变量

### 数据库结构

创建所需的数据表：

```sql
-- 创建 threads 表
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

-- 创建 download_links 表
CREATE TABLE download_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_tid VARCHAR(255) NOT NULL,
    link_text TEXT,
    link_url TEXT,
    link_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_tid) REFERENCES threads(tid)
);

-- 创建索引以提高搜索性能
CREATE INDEX idx_threads_title ON threads(title(255));
CREATE INDEX idx_threads_author ON threads(author);
CREATE INDEX idx_threads_tid ON threads(tid);
```

## 访问路径

部署完成后，您可以通过以下路径访问：

- **主页**: `https://your-domain.vercel.app/`
- **搜索**: `https://your-domain.vercel.app/search`
- **详情**: `https://your-domain.vercel.app/detail`
- **提取码API**: `https://your-domain.vercel.app/api/extract-code`
- **部署检查**: `https://your-domain.vercel.app/check-deployment`

## 自定义域名

1. 在 Vercel 控制台中选择您的项目
2. 进入 "Settings" → "Domains"
3. 添加您的自定义域名
4. 按照提示配置 DNS 记录

## 性能优化

### 缓存配置
项目已配置 Vercel Edge Cache：
- API 响应缓存 5 分钟
- 支持 stale-while-revalidate 策略

### 数据库优化
- 为搜索字段创建索引
- 使用分页减少数据传输
- 考虑使用 Redis 缓存热门搜索结果

## 监控和日志

1. **Vercel Analytics**
   - 在项目设置中启用 Analytics
   - 查看网站访问统计

2. **错误监控**
   - 查看 Vercel 控制台中的 Functions 日志
   - 配置错误通知

## 常见问题

### Q: 部署失败怎么办？
A: 检查以下几点：
- 环境变量是否正确配置
- 数据库连接是否正常
- PHP 版本是否兼容
- PHP 文件是否都在 `api` 目录下

### Q: 数据库连接失败？
A: 确保：
- 数据库服务正在运行
- 连接字符串正确
- 数据库用户有足够的权限

### Q: 搜索功能不工作？
A: 检查：
- 数据库表是否存在
- 是否有数据
- 索引是否创建正确

### Q: Unmatched function pattern 错误？
A: 确保：
- 所有PHP文件都在 `api` 目录下
- `vercel.json` 中的 `functions` 配置正确
- 使用 `functions` 而不是 `builds` 配置

## 本地开发

1. 克隆项目：
```bash
git clone https://github.com/your-username/hifiniphp.git
cd hifiniphp
```

2. 配置环境变量：
```bash
# 创建 .env 文件
cp env.example .env

# 编辑 .env 文件，填入您的数据库信息
```

3. 启动本地服务器：
```bash
# 使用 Vercel CLI (推荐)
vercel dev

# 或者使用 PHP 内置服务器
cd api
php -S localhost:8000
```

4. 访问 http://localhost:3000 (Vercel CLI) 或 http://localhost:8000 (PHP 内置服务器)

## 技术支持

如果您在部署过程中遇到问题，请：
1. 检查 Vercel 控制台中的错误日志
2. 查看本文档的常见问题部分
3. 在 GitHub 上创建 Issue
4. 访问 [Vercel 错误文档](https://vercel.link/unmatched-function-pattern) 获取更多帮助 