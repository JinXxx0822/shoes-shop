</main>
<footer class="site-footer">
    <div class="container">
        <p>PHP + MySQL 课程项目 · 商品分批分时段上架演示</p>
        <p class="muted">数据库脚本：<code>database/shoes_shop.sql</code></p>
    </div>
</footer>
<script>
// 页面顶部实时时钟（纯前端，仅供演示，不影响后端定时逻辑）
(function () {
    var el = document.getElementById('clock');
    if (!el) return;
    function tick() {
        var d = new Date();
        var p = function (n) { return (n < 10 ? '0' : '') + n; };
        el.textContent = d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate())
            + ' ' + p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
    }
    tick();
    setInterval(tick, 1000);
})();
</script>
</body>
</html>
