<?php
// Oturum başlatılır, kullanıcı bilgilerine erişmek için gerekli.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı giriş yapmamışsa, login sayfasına yönlendir.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Veritabanı bağlantısını dahil et.
require_once 'includes/db.php';

// Header dosyasını ekle.
// Burada <head> ve <body> tag'larının açıldığını varsayıyoruz.
// Bu yüzden sayfada HTML/HEAD/BODY etiketleri yok.
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$families = []; // Kullanıcının üye olduğu aileleri tutacak dizi

// Kullanıcının ait olduğu aileleri ve rollerini veritabanından alıyoruz.
// family_members ile families tablolarını join ediyoruz.
$stmt = $conn->prepare("SELECT f.id, f.name, fm.role FROM families f JOIN family_members fm ON f.id = fm.family_id WHERE fm.user_id = ? ORDER BY f.name ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Gelen aileleri diziye ekle.
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $families[] = $row;
    }
}
$stmt->close();

// Veritabanı bağlantısını kapatıyoruz.
$conn->close();
?>

<div class="container container-main">
    <div class="family-list-container">
        <h2 class="text-center mb-4 text-primary fw-bold">Ailelerim</h2>

        <?php if (empty($families)): ?>
            <div class="alert alert-info text-center" role="alert">
                Henüz bir aileniz yok. Yeni aile oluşturabilir veya var olan bir aileye katılabilirsiniz.
            </div>
            <div class="text-center">
                <a href="create_family.php" class="btn btn-primary me-2">Yeni Aile Oluştur</a>
                <a href="join_family.php" class="btn btn-success">Mevcut Aileye Katıl</a>
            </div>
        <?php else: ?>
            <div class="alert alert-success text-center mb-4" role="alert">
                Üyesi olduğunuz aileler aşağıda listeleniyor.
            </div>
            <?php foreach ($families as $family): ?>
                <div class="family-card">
                    <div>
                        <h5><?php echo htmlspecialchars($family['name']); ?></h5>
                        <span class="badge"><?php echo htmlspecialchars(ucfirst($family['role'])); ?></span>
                    </div>
                    <div>
                        <!-- Aile detay sayfasına yönlendiren link -->
                        <a href="<?php echo BASE_URL; ?>family_details.php?id=<?php echo $family['id']; ?>" class="btn btn-sm btn-outline-primary me-2">Detaylar</a>
                        <!-- İleride eklenecek: Aileden ayrılma butonu -->
                        <!-- <a href="leave_family.php?id=<?php echo $family['id']; ?>" class="btn btn-sm btn-danger">Ayrıl</a> -->
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="text-center mt-4">
                <a href="create_family.php" class="btn btn-primary me-2">Yeni Aile Oluştur</a>
                <a href="join_family.php" class="btn btn-success">Mevcut Aileye Katıl</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Footer dosyasını dahil ediyoruz.
// Footer.php içinde </body> ve </html> kapanış tag'ları var.
require_once 'includes/footer.php';
?>
