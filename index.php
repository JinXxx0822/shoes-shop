<?php
/**
 * 商品列表页（首页）
 * 功能：
 *   1) 展示所有批次及各自的上架状态（进行中 / 已结束 / 未开始）
 *   2) 仅展示「当前时间位于批次上架时间段内」的商品（分批、分时段上架）
 *   3) 支持按名称/款式关键词搜索，并分页展示（优化项）
 */
$pageTitle = '商品列表 · 鞋仓';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$pdo = db_connect();
$now = date('Y-m-d H:i:s'); // 当前时刻（与 MySQL NOW() 时区一致）

/**
 * 根据上架时间段计算批次状态
 * @return string on|upcoming|over
 */
function batch_state($start, $end, $now)
{
    if ($now >= $start && $now <= $end) return 'on';       // 进行中
    if ($now < $start)                  return 'upcoming';  // 未开始
    return 'over';                                          // 已结束
}

// ---- 搜索与分页参数（优化项）----
$keyword = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
if (mb_strlen($keyword) > 50) $keyword = mb_substr($keyword, 0, 50); // 限制搜索词长度
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$perPage = 6; // 每页商品数

// 1) 所有批次（含时间段、状态）
$batches = $pdo->query(
    "SELECT id, batch_name, start_time, end_time, remark
     FROM batches ORDER BY start_time ASC"
)->fetchAll();
foreach ($batches as &$b) {
    $b['state'] = batch_state($b['start_time'], $b['end_time'], $now);
}
unset($b);

// 2) 正在上架的批次ID集合（后端定时展示的核心过滤条件）
$onSaleBatchIds = array_column(
    array_filter($batches, function ($b) { return $b['state'] === 'on'; }),
    'id'
);

// 3) 构造在售商品的查询条件：批次过滤 + 关键词过滤
$where  = [];
$params = [];
if ($onSaleBatchIds) {
    $placeholders = implode(',', array_fill(0, count($onSaleBatchIds), '?'));
    $where[]  = "p.batch_id IN ($placeholders)";
    $params   = array_values($onSaleBatchIds);
}
if ($keyword !== '') {
    $where[] = "(p.name LIKE ? OR p.style LIKE ?)";
    $like = '%' . $keyword . '%';
    array_push($params, $like, $like);
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// 4) 统计总数 + 计算分页
$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM products p JOIN batches b ON p.batch_id = b.id $whereSql"
);
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);
if ($totalPages < 1) $totalPages = 1;
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage; // $page/$perPage 均已转为 int，可安全内联

// 5) 查询当前页在售商品
$onSaleProducts = [];
if ($total > 0) {
    $stmt = $pdo->prepare(
        "SELECT p.id, p.name, p.style, p.price, p.stock, p.image, p.description,
                b.batch_name
         FROM products p
         JOIN batches b ON p.batch_id = b.id
         $whereSql
         ORDER BY p.id ASC
         LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($params);
    $onSaleProducts = $stmt->fetchAll();
}
?>

<!-- 页面小标题 -->
<section class="page-head">
    <h1>鞋品上新</h1>
    <p>以下商品按批次、分时段上架，仅在上架时间段内展示。</p>
</section>

<!-- 搜索框 -->
<section class="search-bar">
    <form action="index.php" method="get" class="search-form">
        <input type="search" name="q" value="<?php echo htmlspecialchars($keyword, ENT_QUOTES); ?>"
               placeholder="搜索鞋名或款式…" class="search-input">
        <button type="submit" class="search-btn">搜索</button>
        <?php if ($keyword !== ''): ?>
            <a href="index.php" class="search-clear">清除</a>
        <?php endif; ?>
    </form>
</section>

<!-- 批次状态总览 -->
<section class="batch-panel">
    <h2>上架批次</h2>
    <?php if (empty($batches)): ?>
        <p class="empty-text">暂无批次。</p>
    <?php else: ?>
        <table class="batch-table">
            <thead>
                <tr>
                    <th>批次</th>
                    <th>上架时间段</th>
                    <th>状态</th>
                    <th>说明</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($batches as $b): ?>
                <tr>
                    <td><?php echo htmlspecialchars($b['batch_name']); ?></td>
                    <td><?php echo htmlspecialchars($b['start_time']); ?> ~ <?php echo htmlspecialchars($b['end_time']); ?></td>
                    <td>
                        <span class="tag tag-<?php echo $b['state']; ?>">
                            <?php
                                $label = ['on' => '进行中', 'upcoming' => '未开始', 'over' => '已结束'];
                                echo $label[$b['state']];
                            ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($b['remark']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="muted">当前时间：<?php echo htmlspecialchars($now); ?></p>
    <?php endif; ?>
</section>

<!-- 正在上架商品 -->
<section class="goods-panel">
    <h2>
        <?php if ($keyword !== ''): ?>
            搜索「<?php echo htmlspecialchars($keyword); ?>」的结果
        <?php else: ?>
            当前在售鞋品
        <?php endif; ?>
    </h2>
    <?php if (empty($onSaleProducts)): ?>
        <p class="empty-text">
            <?php echo $keyword !== '' ? '没有匹配到在售商品，换个关键词试试。' : '当前没有处于上架时间段的商品，请稍后再来。'; ?>
        </p>
    <?php else: ?>
        <div class="goods-grid">
        <?php foreach ($onSaleProducts as $p): ?>
            <div class="goods-card">
                <a href="detail.php?id=<?php echo (int)$p['id']; ?>">
                    <div class="goods-img">
                        <?php if ($p['image']): ?>
                            <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                        <?php else: ?>
                            <span class="img-placeholder">暂无图片</span>
                        <?php endif; ?>
                    </div>
                    <div class="goods-info">
                        <span class="batch-label"><?php echo htmlspecialchars($p['batch_name']); ?></span>
                        <h3 class="goods-name"><?php echo htmlspecialchars($p['name']); ?></h3>
                        <p class="goods-style">款式：<?php echo htmlspecialchars($p['style']); ?></p>
                        <p class="goods-price">¥<?php echo number_format($p['price'], 2); ?></p>
                        <p class="goods-stock">库存：<?php echo (int)$p['stock']; ?> 双</p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
        </div>

        <!-- 分页导航 -->
        <?php if ($totalPages > 1): ?>
        <nav class="pagination">
            <?php
                $query = $keyword !== '' ? '&q=' . urlencode($keyword) : '';
                if ($page > 1) {
                    echo '<a class="page-link" href="index.php?page=' . ($page - 1) . $query . '">上一页</a>';
                }
                echo '<span class="page-info">第 ' . $page . ' / ' . $totalPages . ' 页（共 ' . $total . ' 件）</span>';
                if ($page < $totalPages) {
                    echo '<a class="page-link" href="index.php?page=' . ($page + 1) . $query . '">下一页</a>';
                }
            ?>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
