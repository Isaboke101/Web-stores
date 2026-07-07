<?php
/**
 * admin/partials/sidebar.php
 * Shared sidebar included on every admin page.
 * On mobile: hamburger opens, internal X closes.
 * Logo sits top-right, X button top-left of the drawer.
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- External hamburger — opens the sidebar on mobile -->
<button class="mobile-menu-btn" id="sidebar-toggle" aria-label="Open navigation">
  <i class="fa-solid fa-bars"></i>
</button>

<!-- Tap-outside overlay -->
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<aside class="admin-sidebar" id="admin-sidebar">

  <!-- Sidebar header: X close button (left) + logo (right) -->
  <div class="sidebar-brand">
    <button class="sidebar-close-btn" id="sidebar-close" aria-label="Close navigation">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div>
      <span class="brand-word">injili</span>
      <span class="brand-sub">Admin</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <a href="dashboard.php"
       class="nav-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
      <i class="fa-solid fa-gauge"></i> Dashboard
    </a>
    <a href="orders.php"
       class="nav-item <?= in_array($currentPage, ['orders.php','order_detail.php']) ? 'active' : '' ?>">
      <i class="fa-solid fa-bag-shopping"></i> Orders
    </a>
    <a href="products.php"
       class="nav-item <?= $currentPage === 'products.php' ? 'active' : '' ?>">
      <i class="fa-solid fa-shirt"></i> Products
    </a>
    <a href="analytics.php"
       class="nav-item <?= $currentPage === 'analytics.php' ? 'active' : '' ?>">
      <i class="fa-solid fa-chart-line"></i> Analytics
    </a>
  </nav>

  <div class="sidebar-foot">
    <a href="../index.html" class="nav-item" target="_blank">
      <i class="fa-solid fa-store"></i> View Store
    </a>
    <a href="logout.php" class="nav-item nav-signout">
      <i class="fa-solid fa-right-from-bracket"></i> Sign Out
    </a>
  </div>
</aside>

<script>
(function () {
  var openBtn  = document.getElementById('sidebar-toggle');
  var closeBtn = document.getElementById('sidebar-close');
  var sidebar  = document.getElementById('admin-sidebar');
  var overlay  = document.getElementById('sidebar-overlay');

  function open() {
    sidebar.classList.add('mobile-open');
    overlay.classList.add('visible');
    openBtn.classList.add('hidden'); /* hide external hamburger */
  }

  function close() {
    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('visible');
    openBtn.classList.remove('hidden'); /* restore external hamburger */
  }

  openBtn.addEventListener('click', open);
  closeBtn.addEventListener('click', close);
  overlay.addEventListener('click', close);

  /* Auto-close when a nav link is tapped on mobile */
  var links = document.querySelectorAll('.admin-sidebar .nav-item');
  for (var i = 0; i < links.length; i++) {
    links[i].addEventListener('click', function () {
      if (window.innerWidth <= 768) close();
    });
  }
})();
</script>