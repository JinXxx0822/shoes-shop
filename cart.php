<?php
/**
 * 购物车页面
 * 功能：
 *   1) 基于 session 存储购物车（商品ID => 数量）
 *   2) 支持加入、增加、减少、删除、清空
 *   3) 校验商品仅在售（处于上架时间段内）且不超过库存
 *   4) 展示购物车明细与总价
 *
 * 动作处理必须在任何输出（含 header）之前完成，以便使用 header('Location') 重定向。
 */
$pageTitle = '购物车 · 鞋仓';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';

$pdo = db_connect();

// 初始化购物车
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ---- 处理动作（GET：action + id）----
$action = isset($_GET['action']) ? (string)$_GET['action'] : 'view';
$id     = isset($_GET['id'])     ? (int)$_GET['id']     : 0;

// 取某商品的在售库存（仅在上架时间段内可见可购）
function cart_stock($pdo, $id)
{
    $stmt = $pdo->prepare(
        "SELECT p.stock FROM products p
         JOIN batches b ON p.batch_id = b.id
         WHERE p.id = ? AND NOW() BETWEEN b.start_time AND b.end_time"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['stock'] : 0;
}

if ($action !== 'view') {
    switch ($action) {
        case 'add':   // 加入购物车（数量 +1，校验在售与库存）
            if ($id > 0) {
                $stock = cart_stock($pdo, $id);
                $qty   = isset($_SESSION['cart'][$id]) ? (int)$_SESSION['cart'][$id] : 0;
                if ($stock > 0 && $qty < $stock) {
                    $_SESSION['cart'][$id] = $qty + 1;
                }
            }
            break;
        case 'inc':   // 增加数量（不超过库存）
            if ($id > 0 && isset($_SESSION['cart'][$id])) {
                $stock = cart_stock($pdo, $id);
                $qty   = (int)$_SESSION['cart'][$id];
                if ($qty < $stock) {
                    $_SESSION['cart'][$id] = $qty + 1;
                }
            }
            break;
        case 'dec':   // 减少数量（减到 0 则移除）
            if ($id > 0 && isset($_SESSION['cart'][$id])) {
                if (--$_SESSION['cart'][$id] <= 0) {
                    unset($_SESSION['cart'][$id]);
                }
            }
            break;
        case 'del':   // 删除单件商品
            if ($id > 0) {
                unset($_SESSION['cart'][$id]);
            }
            break;
        case 'clear': // 清空购物车
            $_SESSION['cart'] = [];
            break;
    }
    header('Location: cart.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';

// ---- 读取购物车商品明细 ----
$items       = [];
$totalAmount = 0;
if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT p.id, p.name, p.style, p.price, p.stock, p.image, b.batch_name
         FROM products p
         JOIN batches b ON p.batch_id = b.id
         WHERE p.id IN ($ph) AND NOW() BETWEEN b.start_time AND b.end_time"
    );
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $r) {
        $qty = min((int)$_SESSION['cart'][$r['id']], (int)$r['stock']);
        if ($qty <= 0) continue;
        $r['qty']     = $qty;
        $r['subtotal'] = (float)$r['price'] * $qty;
        $totalAmount  += $r['subtotal'];
        $items[]      = $r;
    }
}
?>

<section class="page-head">
    <h1>我的购物车</h1>
    <p>选择心仪鞋品，加入购物车统一结算。</p>
</section>

<section class="cart-panel">
    <?php if (empty($items)): ?>
        <p class="empty-text">购物车还是空的，去<a href="index.php" class="link">商品列表</a>逛逛吧。</p>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>商品</th>
                    <th>单价</th>
                    <th>数量</th>
                    <th>小计</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td>
                        <div class="cart-goods">
                            <?php if ($it['image']): ?>
                                <img src="<?php echo htmlspecialchars($it['image']); ?>"
                                     alt="<?php echo htmlspecialchars($it['name']); ?>">
                            <?php endif; ?>
                            <div>
                                <a class="cart-name" href="detail.php?id=<?php echo (int)$it['id']; ?>">
                                    <?php echo htmlspecialchars($it['name']); ?>
                                </a>
                                <div class="muted"><?php echo htmlspecialchars($it['style']); ?>
                                    · <?php echo htmlspecialchars($it['batch_name']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="price">¥<?php echo number_format($it['price'], 2); ?></td>
                    <td>
                        <div class="qty-ctrl">
                            <a class="qty-btn" href="cart.php?action=dec&id=<?php echo (int)$it['id']; ?>">−</a>
                            <span class="qty-num"><?php echo (int)$it['qty']; ?></span>
                            <a class="qty-btn" href="cart.php?action=inc&id=<?php echo (int)$it['id']; ?>">+</a>
                        </div>
                    </td>
                    <td class="price">¥<?php echo number_format($it['subtotal'], 2); ?></td>
                    <td>
                        <a class="link-danger" href="cart.php?action=del&id=<?php echo (int)$it['id']; ?>">删除</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-footer">
            <a class="btn" href="index.php">← 继续购物</a>
            <a class="btn-danger" href="cart.php?action=clear">清空购物车</a>
            <span class="cart-total">合计：<strong>¥<?php echo number_format($totalAmount, 2); ?></strong></span>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
