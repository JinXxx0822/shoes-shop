<?php
/**
 * 数据库连接配置（课程项目）
 * XAMPP 默认环境：用户名 root，密码为空。
 * 若你的 MySQL 密码不同，请修改 DB_PASS。
 */
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'shoes_shop');
define('DB_USER', 'root');
define('DB_PASS', '');

// 统一时区，保证“分时段上架”判断与本地时间一致
date_default_timezone_set('Asia/Shanghai');

/**
 * 建立 PDO 连接，返回 PDO 对象；失败时直接终止并提示。
 */
function db_connect()
{
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die('数据库连接失败：' . htmlspecialchars($e->getMessage())
            . '<br>请确认已启动 MySQL 并导入 database/shoes_shop.sql，详见 README.md。');
    }
}
