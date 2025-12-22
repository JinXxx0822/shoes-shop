<?php
/**
 * 商品详情页
 * 功能：读取指定商品并展示详情；
 *       校验商品所属批次是否处于上架时间段，若已结束/未开始则给出提示。
 */
$pageTitle = '商品详情 · 鞋仓';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pdo = db_connect();
$now = date('Y-m-d H:i:s');

$stmt = $pdo->prepare(
    "SELECT p.id, p.name, p.style, p.price, p.stock, p.image, p.description,
            b.batch_name, b.start_time, b.end_time
     FROM products p
     JOIN batches b ON p.batch_id = b.id
     WHERE p.id = ?"
);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo '<div class="empty-box"><p>商品不存在或已被删除。</p><p><a class="btn" href="index.php">返回商品列表</a></p></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// 判断当前是否在上架时间段内
$onSale = ($now >= $product['start_time'] && $now <= $product['end_time']);
$statusText = $onSale
    ? '在售'
    : ($now < $product['start_time'] ? '未开始' : '已结束');
?>

<div class="detail-box">
    <div class="detail-img">
        <?php if ($product['image']): ?>
            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        <?php else: ?>
            <span class="img-placeholder">暂无图片</span>
        <?php endif; ?>
    </div>

    <div class="detail-info">
        <span class="tag tag-<?php echo $onSale ? 'on' : 'over'; ?>"><?php echo $statusText; ?></span>
        <span class="batch-label"><?php echo htmlspecialchars($product['batch_name']); ?></span>
        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
        <p class="detail-style">款式：<?php echo htmlspecialchars($product['style']); ?></p>
        <p class="detail-price">¥<?php echo number_format($product['price'], 2); ?></p>
        <p class="detail-stock">库存：<?php echo (int)$product['stock']; ?> 双</p>

        <div class="detail-desc">
            <h3>商品介绍</h3>
            <p><?php echo nl2br(htmlspecialchars($product['description'] ?: '暂无描述。')); ?></p>
        </div>

        <p class="muted">
            上架时间段：<?php echo htmlspecialchars($product['start_time']); ?> ~
            <?php echo htmlspecialchars($product['end_time']); ?>
        </p>

        <?php if ($onSale): ?>
            <p><a class="btn" href="index.php">← 返回列表继续选购</a></p>
        <?php else: ?>
            <p class="warn">该批次当前不在上架时间段内，商品不可购买。</p>
            <p><a class="btn" href="index.php">← 返回列表</a></p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
