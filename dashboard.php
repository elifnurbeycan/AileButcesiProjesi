<?php
// Oturum daha önce başlatılmadıysa başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Giriş yapılmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Veritabanı bağlantısını ekle
require_once 'includes/db.php';

// Sayfanın üst kısmını (menü vs.) ekle
require_once 'includes/header.php';

// Oturumdan kullanıcı bilgileri alınır
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Kullanıcı'; // Kullanıcı adı yoksa varsayılan olarak "Kullanıcı" göster

$total_overall_balance = 0; // Tüm ailelerin net bakiyesi için başlangıç

// Kullanıcının üyesi olduğu ailelerin ID'lerini al
$user_family_ids = [];
$stmt_user_families = $conn->prepare("SELECT family_id FROM family_members WHERE user_id = ?");
$stmt_user_families->bind_param("i", $user_id);
$stmt_user_families->execute();
$result_user_families = $stmt_user_families->get_result();
while ($row = $result_user_families->fetch_assoc()) {
    $user_family_ids[] = $row['family_id'];
}
$stmt_user_families->close();

// Eğer en az bir aileye üyeyse, o ailelerin finansal özet bilgilerini getir
if (!empty($user_family_ids)) {
    $family_ids_string = implode(',', $user_family_ids);

    // Gelirlerin toplamını al
    $query_total_income = "SELECT SUM(amount) as total_income FROM transactions WHERE family_id IN (" . $family_ids_string . ") AND type = 'income'";
    $result_total_income = $conn->query($query_total_income);
    $row_income = $result_total_income->fetch_assoc();
    $total_income = $row_income['total_income'] ?? 0;

    // Giderlerin toplamını al
    $query_total_expense = "SELECT SUM(amount) as total_expense FROM transactions WHERE family_id IN (" . $family_ids_string . ") AND type = 'expense'";
    $result_total_expense = $conn->query($query_total_expense);
    $row_expense = $result_total_expense->fetch_assoc();
    $total_expense = $row_expense['total_expense'] ?? 0;

    // Net bakiyeyi hesapla
    $total_overall_balance = $total_income - $total_expense;
}

$conn->close();
?>

<div class="container container-main d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="welcome-card text-center p-4 bg-white rounded shadow-sm">
        <h3 class="text-primary mb-3">Hoş Geldin, <?= htmlspecialchars($username); ?>!</h3>
        <p class="lead mb-3">Aile Bütçesi Yönetim Uygulamasına hoş geldin.</p>
        <p>Yukarıdaki menüden harcamalarını yönetebilir, aile bilgilerini düzenleyebilir ve daha fazlasını yapabilirsin.</p>

        <div class="mt-4 p-3 rounded-3 shadow-sm
            <?php echo ($total_overall_balance >= 0) ? 'bg-success text-white' : 'bg-danger text-white'; ?>">
            <h4 class="mb-2">Tüm Ailelerdeki Toplam Net Bakiye</h4>
            <p class="fs-2 fw-bold"><?php echo number_format($total_overall_balance, 2, ',', '.') . ' TL'; ?></p>
        </div>

    </div>
</div>

<?php
// Sayfanın alt kısmını (footer) ekle
require_once 'includes/footer.php';
?>
