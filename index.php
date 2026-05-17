<?php
require_once 'route.php';
require_once 'bootstrap.php';

if (isStandaloneRoute($page)) {
    renderRoute($page);
    exit;
}

// Sayfa içeriğini önceden yakalıyoruz. 
// Bu sayede sayfa dosyası içinde tanımlanan $pageTitle gibi değişkenler topbar.php'den önce hazır olur.
ob_start();
renderRoute($page);
$pageContent = ob_get_clean();
?>

<!DOCTYPE html>
<html lang="tr" class="h-full">
<?php require_once 'layouts/head.php'; ?>
<body class="min-h-svh bg-background text-foreground">
<div id="toaster" class="toaster" data-position="bottom-right" popover="manual"></div>
<div class="app-shell">
  <?php require_once 'layouts/sidebar.php'; ?>
  
  <div class="app-view">
    <?php require_once 'layouts/topbar.php'; ?>
    
    <main class="app-main p-4 md:p-6">
      <?php echo $pageContent; ?>
    </main>
  </div>
</div>
</body>
</html>
