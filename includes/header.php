<?php
/**
 * 公共页面头部。用法：
 *   $pageTitle = '页面标题';
 *   require_once __DIR__ . '/includes/header.php';
 */
if (!isset($pageTitle)) {
    $pageTitle = '鞋仓 · 简易鞋类售卖';
}

// 启动会话（购物车基于 session 实现）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 购物车商品总件数（导航角标）
$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cartCount = array_sum($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="index.php">👟 鞋仓 ShoeShop</a>
        <span class="subtitle">简易鞋类售卖 · 分批分时段上架</span>
        <a class="cart-link" href="cart.php">🛒 购物车
            <span class="cart-badge" id="cartCount"><?php echo (int)$cartCount; ?></span>
        </a>
        <span class="clock" id="clock"></span>
    </div>
</header>
<main class="container">
