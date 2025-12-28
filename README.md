# 鞋仓 ShoeShop —— 简易鞋类售卖 Web 页面（PHP + MySQL 课程项目）

基于 **PHP + MySQL** 搭建的简易鞋子商品售卖网页，核心业务为**商品分批、分时段上架**：
不同批次的鞋子分别设定独立的上架时间段，页面仅在对应时间段内展示该批次商品。

## 功能清单

- 商品列表浏览：展示鞋子图片、款式、价格、库存，并以卡片网格呈现
- 商品详情查看：独立详情页，展示商品介绍、价格、库存、所属批次及上架时段
- 分批、分时段上架：批次表 `batches` 记录每批的 `start_time ~ end_time`，
  页面仅展示「当前时间位于该时间段内」的批次商品
- 批次状态总览：首页展示各批次「进行中 / 未开始 / 已结束」三种状态
- 商品搜索与分页：支持按鞋名/款式关键词搜索，并分页浏览在售商品

## 项目结构

```
shoes-shop/
├── index.php                 # 商品列表页（首页，含分批分时段过滤）
├── detail.php                # 商品详情页（含在售状态校验）
├── config/
│   └── db.php                # 数据库连接配置（PDO）
├── includes/
│   ├── header.php            # 公共页面头部
│   └── footer.php            # 公共页面底部（含实时时钟）
├── assets/
│   ├── css/style.css         # 页面样式
│   └── images/               # 鞋子商品图片（shoe1~shoe9.jpg）
├── database/
│   └── shoes_shop.sql        # 建库建表 + 示例数据脚本
└── README.md
```

## 运行环境

- PHP 7.4+（建议 8.x），需启用 `pdo_mysql` 扩展
- MySQL / MariaDB
- 推荐直接使用 XAMPP / phpStudy 等集成环境

## 部署步骤（以 XAMPP 为例）

1. **放置代码**：将本 `shoes-shop` 目录复制到 XAMPP 的 `htdocs` 下，
   最终路径形如 `C:\xampp\htdocs\shoes-shop`。

2. **启动服务**：打开 XAMPP Control Panel，启动 **Apache** 和 **MySQL**。

3. **导入数据库**：
   - 方式 A（命令行）：在 MySQL 命令行执行
     `SOURCE C:/xampp/htdocs/shoes-shop/database/shoes_shop.sql;`
   - 方式 B（phpMyAdmin）：打开 `http://localhost/phpmyadmin`，
     导入 `database/shoes_shop.sql` 文件。
   - 脚本会自动删除并重建 `shoes_shop` 数据库、建表并插入示例数据。

4. **修改数据库密码（如需）**：若你的 MySQL 的 root 密码不为空，
   编辑 `config/db.php` 中的 `DB_PASS`。

5. **访问页面**：浏览器打开 `http://localhost/shoes-shop/index.php`。

## 分批分时段上架的实现思路

- 数据库中维护一张**批次表** `batches`，每个批次记录上架时间段
  （`start_time` / `end_time`）；商品表 `products` 通过 `batch_id` 归属到某个批次。
- 页面查询商品时，**把「当前时间」与批次时间段做比较**，只取出
  `当前时间 ∈ [start_time, end_time]` 的商品进行展示，从而实现定时、分批发布。
  ```sql
  SELECT p.* FROM products p
  JOIN batches b ON p.batch_id = b.id
  WHERE NOW() BETWEEN b.start_time AND b.end_time;
  ```
- 示例数据中的批次时间基于 `NOW()` 相对生成，因此任意时间部署都会有一个
  「进行中」批次可演示；如需固定真实时间，可自行修改 `shoes_shop.sql` 中的批次时间。

## 常见问题

| 现象 | 处理 |
| --- | --- |
| 页面报「数据库连接失败」 | 确认 MySQL 已启动；确认已导入 SQL；核对 `config/db.php` 账号密码 |
| 商品图片不显示 | 确认 `assets/images/` 下有 `shoe1~shoe9.jpg` |
| 首页显示「暂无上架商品」 | 说明当前时间不在任何批次时间段内，可修改批次时间或等待上架 |
