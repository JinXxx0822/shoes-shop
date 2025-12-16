-- =====================================================================
--  简易鞋类售卖 Web 页面（课程项目） 数据库脚本
--
--  说明：
--  1) 适用于 MySQL / MariaDB（如 XAMPP 自带环境）。
--  2) 核心业务：商品分批、分时段上架。
--     - batches 批次表：每批定义一段「上架时间段」(start_time ~ end_time)
--     - products 商品表：通过 batch_id 归属到某个批次
--     - 页面仅展示「当前时间位于其批次上架时间段内」的商品
--  3) 示例批次时间用 NOW() 相对生成，任意时间部署都会有一个「进行中」批次，
--     方便演示「进行中 / 已结束 / 未开始」三种状态。
--  4) 导入方法见 README.md。
-- =====================================================================

DROP DATABASE IF EXISTS shoes_shop;
CREATE DATABASE shoes_shop DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE shoes_shop;

-- ---------------------------------------------------------------------
--  批次表：定义分批次、分时段上架的时间窗口
-- ---------------------------------------------------------------------
CREATE TABLE batches (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    batch_name  VARCHAR(50)  NOT NULL COMMENT '批次名称，如：第一批·冬季新款',
    start_time  DATETIME     NOT NULL COMMENT '上架开始时间',
    end_time    DATETIME     NOT NULL COMMENT '上架结束时间',
    remark      VARCHAR(255) DEFAULT '' COMMENT '批次说明'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='上架批次表（分时段）';

-- ---------------------------------------------------------------------
--  商品表：鞋子商品，属于某个批次
-- ---------------------------------------------------------------------
CREATE TABLE products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    batch_id    INT          NOT NULL COMMENT '所属批次，关联 batches.id',
    name        VARCHAR(100) NOT NULL COMMENT '鞋名',
    style       VARCHAR(100) NOT NULL COMMENT '款式',
    price       DECIMAL(10,2) NOT NULL COMMENT '售价',
    stock       INT          NOT NULL COMMENT '库存数量',
    image       VARCHAR(255) DEFAULT '' COMMENT '图片路径',
    description TEXT         COMMENT '商品描述',
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_batch FOREIGN KEY (batch_id) REFERENCES batches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品表';

-- ---------------------------------------------------------------------
--  示例批次数据（时间相对当前时刻生成，便于演示）
-- ---------------------------------------------------------------------
-- 第一批：正在上架中
INSERT INTO batches (batch_name, start_time, end_time, remark) VALUES
('第一批·冬季新款', DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_ADD(NOW(), INTERVAL 10 DAY), '正在上架中');

-- 第二批：已结束（商品不再展示）
INSERT INTO batches (batch_name, start_time, end_time, remark) VALUES
('第二批·经典休闲', DATE_SUB(NOW(), INTERVAL 40 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), '已结束，商品不再展示');

-- 第三批：未到上架时间
INSERT INTO batches (batch_name, start_time, end_time, remark) VALUES
('第三批·运动限量', DATE_ADD(NOW(), INTERVAL 10 DAY), DATE_ADD(NOW(), INTERVAL 40 DAY), '尚未到上架时间');

-- ---------------------------------------------------------------------
--  示例商品数据（每批 3 双）
-- ---------------------------------------------------------------------
-- 第一批
INSERT INTO products (batch_id, name, style, price, stock, image, description) VALUES
(1, '轻羽保暖雪地靴',   '冬靴·加绒',   299.00, 120, 'assets/images/shoe1.jpg', '加绒保暖，防滑耐磨，适合冬季出行。'),
(1, '复古高帮马丁靴',   '马丁靴·复古', 349.00, 80,  'assets/images/shoe2.jpg', '头层牛皮，复古做旧工艺，百搭耐穿。'),
(1, '加厚毛绒居家棉拖', '居家·棉拖',   89.00,  200, 'assets/images/shoe3.jpg', '毛绒内里，柔软舒适，居家必备。');

-- 第二批
INSERT INTO products (batch_id, name, style, price, stock, image, description) VALUES
(2, '经典小白鞋',     '休闲·小白鞋', 199.00, 150, 'assets/images/shoe4.jpg', '经典百搭小白鞋，透气轻盈。'),
(2, '网面透气运动鞋', '运动·网面',   259.00, 90,  'assets/images/shoe5.jpg', '透气网面，缓震鞋底，运动休闲两相宜。'),
(2, '帆布鞋低帮',     '休闲·帆布',   129.00, 180, 'assets/images/shoe6.jpg', '简约帆布，轻便舒适，通勤日常之选。');

-- 第三批
INSERT INTO products (batch_id, name, style, price, stock, image, description) VALUES
(3, '限量联名跑鞋',   '运动·限量', 699.00, 30,  'assets/images/shoe7.jpg', '限量联名款，回弹中底，收藏与跑步兼顾。'),
(3, '专业篮球鞋',     '运动·篮球', 599.00, 40,  'assets/images/shoe8.jpg', '高帮包裹，抓地防滑，实战利器。'),
(3, '情侣款运动拖鞋', '运动·拖鞋', 99.00,  300, 'assets/images/shoe9.jpg', '柔软 EVA 鞋底，轻便透气，休闲出街。');
