<?php
// includes/header.php
session_start();

// Eğer kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">Aile Bütçesi</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="dashboard.php">Ana Sayfa</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="families.php">Ailelerim</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="list_transaction.php">Harcamalar</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="list_categories.php">Kategoriler</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="financial_summary.php">Bütçe Özeti</a>
        </li>
      </ul>
      
      <ul class="navbar-nav">
        <li class="nav-item">
          <span class="navbar-text me-3">
            Hoşgeldin, <?= htmlspecialchars($_SESSION['username']) ?>
          </span>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logout.php">Çıkış</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
