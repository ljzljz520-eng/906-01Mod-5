-- 创建数据库
CREATE DATABASE IF NOT EXISTS torrent_search CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 授权给 baobei 用户
GRANT ALL PRIVILEGES ON torrent_search.* TO 'baobei'@'%';
FLUSH PRIVILEGES;

USE torrent_search;

-- 创建搜索历史表
CREATE TABLE IF NOT EXISTS search_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  keyword VARCHAR(50) NOT NULL COMMENT '搜索源',
  query VARCHAR(255) NOT NULL COMMENT '搜索关键词',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  INDEX idx_created_at (created_at),
  INDEX idx_keyword (keyword)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='搜索历史表';

-- 创建收藏表
CREATE TABLE IF NOT EXISTS favorites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(500) NOT NULL COMMENT 'Torrent 名称',
  magnet TEXT NOT NULL COMMENT '磁力链接',
  size VARCHAR(50) COMMENT '文件大小',
  seeders INT DEFAULT 0 COMMENT '做种数',
  leechers INT DEFAULT 0 COMMENT '下载数',
  category VARCHAR(100) COMMENT '分类',
  source VARCHAR(50) COMMENT '来源站点',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  INDEX idx_created_at (created_at),
  INDEX idx_source (source),
  UNIQUE INDEX idx_magnet (magnet(255))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收藏表';

-- 插入示例搜索历史数据
INSERT INTO search_history (keyword, query, created_at) VALUES
('1337x', 'Avengers', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
('yts', 'Inception', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('eztv', 'Breaking Bad', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('1337x', 'The Matrix', DATE_SUB(NOW(), INTERVAL 5 DAY));

-- 插入示例收藏数据
INSERT INTO favorites (name, magnet, size, seeders, leechers, category, source, created_at) VALUES
(
  'Avengers: Endgame (2019) [1080p]',
  'magnet:?xt=urn:btih:EXAMPLE1234567890ABCDEF&dn=Avengers+Endgame',
  '2.5 GB',
  1250,
  85,
  'Movies',
  '1337x',
  DATE_SUB(NOW(), INTERVAL 1 DAY)
),
(
  'The Dark Knight (2008) [720p]',
  'magnet:?xt=urn:btih:EXAMPLE0987654321FEDCBA&dn=The+Dark+Knight',
  '1.8 GB',
  890,
  42,
  'Movies',
  'yts',
  DATE_SUB(NOW(), INTERVAL 3 DAY)
);

-- 创建安全公告表
CREATE TABLE IF NOT EXISTS security_advisories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  component_name VARCHAR(255) NOT NULL COMMENT '组件名称',
  affected_versions VARCHAR(500) NOT NULL COMMENT '受影响版本范围',
  fixed_version VARCHAR(100) COMMENT '修复版本',
  severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium' COMMENT '严重程度',
  title VARCHAR(500) NOT NULL COMMENT '漏洞标题',
  description TEXT COMMENT '漏洞描述',
  alternative_resources TEXT COMMENT '替代资源，JSON格式存储多个',
  status ENUM('active', 'resolved') NOT NULL DEFAULT 'active' COMMENT '状态：active-活跃，resolved-已解除',
  resolved_at TIMESTAMP NULL COMMENT '解除时间',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  INDEX idx_component_name (component_name),
  INDEX idx_severity (severity),
  INDEX idx_status (status),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='安全漏洞公告表';

-- 插入示例安全公告数据
INSERT INTO security_advisories (component_name, affected_versions, fixed_version, severity, title, description, alternative_resources, status, created_at) VALUES
(
  'log4j',
  '2.0-beta9 到 2.14.1',
  '2.17.1',
  'critical',
  'Log4j2 JNDI 注入漏洞 (CVE-2021-44228)',
  'Apache Log4j2 中存在 JNDI 注入漏洞，攻击者可通过构造恶意请求在目标服务器上执行任意代码。该漏洞影响范围广泛，危害严重。',
  '["升级到 Log4j 2.17.1 或更高版本", "使用 Logback 作为替代日志框架"]',
  'active',
  DATE_SUB(NOW(), INTERVAL 30 DAY)
),
(
  'openssl',
  '1.0.1 到 1.0.1f',
  '1.0.1g',
  'high',
  'OpenSSL Heartbleed 漏洞 (CVE-2014-0160)',
  'Heartbleed 漏洞允许攻击者读取内存中的敏感信息，包括私钥、密码等。该漏洞存在于 OpenSSL 的 TLS 心跳扩展实现中。',
  '["升级到 OpenSSL 1.0.1g 或更高版本"]',
  'resolved',
  DATE_SUB(NOW(), INTERVAL 365 DAY)
),
(
  'struts2',
  '2.0.0 到 2.3.32, 2.5 到 2.5.10.1',
  '2.3.33, 2.5.12',
  'critical',
  'Struts2 远程代码执行漏洞 (CVE-2017-5638)',
  'Apache Struts2 存在远程代码执行漏洞，攻击者可通过构造恶意的 Content-Type 头执行任意命令。',
  '["升级到 Struts 2.3.33 或 2.5.12 及以上版本", "使用 Spring MVC 作为替代框架"]',
  'active',
  DATE_SUB(NOW(), INTERVAL 180 DAY)
);

-- 显示表结构
SHOW TABLES;
SELECT '✅ Database initialized successfully!' AS status;
