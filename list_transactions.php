<?php
// Oturum başlatılır (eğer henüz başlamadıysa).
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Veritabanı bağlantısı dahil edilir.
require_once 'includes/db.php';

// Sayfa üst kısmı (header) dahil edilir.
// Burada <head> ve <body> açılış etiketleri bulunur.
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$selected_family_id = null;
$transactions = []; // İşlemler bu diziye yüklenecek.

// Kullanıcının üye olduğu aileler veritabanından çekilir.
$user_families = [];
$stmt_families = $conn->prepare("SELECT f.id, f.name FROM families f JOIN family_members fm ON f.id = fm.family_id WHERE fm.user_id = ? ORDER BY f.name ASC");
$stmt_families->bind_param("i", $user_id);
$stmt_families->execute();
$result_families = $stmt_families->get_result();
while ($row = $result_families->fetch_assoc()) {
    $user_families[] = $row;
}
$stmt_families->close();

// Aile ID'si GET veya POST ile gelmişse al ve doğrula.
if (isset($_GET['family_id']) && !empty($_GET['family_id'])) {
    $selected_family_id = filter_input(INPUT_GET, 'family_id', FILTER_VALIDATE_INT);
} elseif (isset($_POST['family_id']) && !empty($_POST['family_id'])) {
    $selected_family_id = filter_input(INPUT_POST, 'family_id', FILTER_VALIDATE_INT);
}

// Seçilen aile kullanıcının aileleri arasında mı kontrol edilir.
$is_user_member_of_selected_family = false;
if ($selected_family_id) {
    foreach ($user_families as $family) {
        if ($family['id'] == $selected_family_id) {
            $is_user_member_of_selected_family = true;
            break;
        }
    }
}

// Geçerli bir aile seçilmişse işlemler çekilir.
if ($selected_family_id && $is_user_member_of_selected_family) {
    // transactions, categories ve users tabloları JOIN edilerek detaylar alınır.
    $stmt_transactions = $conn->prepare("SELECT t.id, t.amount, t.type, t.description, t.transaction_date, c.name as category_name, u.username as user_name FROM transactions t JOIN categories c ON t.category_id = c.id JOIN users u ON t.user_id = u.id WHERE t.family_id = ? ORDER BY t.transaction_date DESC, t.created_at DESC");
    $stmt_transactions->bind_param("i", $selected_family_id);
    $stmt_transactions->execute();
    $result_transactions = $stmt_transactions->get_result();

    if ($result_transactions->num_rows > 0) {
        while ($row = $result_transactions->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
    $stmt_transactions->close();
} else {
    // Geçersiz veya boş seçim durumunda işlem yapılmaz.
}

// Veritabanı bağlantısı kapatılır.
$conn->close();
?>

<!-- Sayfa içeriği başlangıcı -->
<div class="container container-main">
    <div class="list-container">
        <h2 class="text-center mb-4 text-primary fw-bold">İşlemlerimi Listele</h2>

        <!-- Aile seçimi formu -->
        <div class="family-select-form">
            <form action="list_transactions.php" method="GET" class="row g-3 align-items-center">
                <div class="col-md-8">
                    <label for="family_select" class="form-label visually-hidden">Aile Seç</label>
                    <select class="form-select rounded-3" id="family_select" name="family_id" onchange="this.form.submit()">
                        <option value="">Lütfen bir aile seçin...</option>
                        <?php foreach ($user_families as $family): ?>
                            <option value="<?php echo htmlspecialchars($family['id']); ?>" <?php echo ($selected_family_id == $family['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($family['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100 rounded-3">İşlemleri Getir</button>
                </div>
            </form>
        </div>

        <!-- Aile seçimi yoksa veya geçerli değilse uyarı göster -->
        <?php if (!$selected_family_id || !$is_user_member_of_selected_family): ?>
            <div class="alert alert-info text-center" role="alert">
                Lütfen yukarıdaki menüden bir aile seçerek işlemlerinizi görüntüleyin.
                <?php if (empty($user_families)): ?>
                    <br>Henüz hiçbir aileye üye değilsiniz. <a href="create_family.php" class="alert-link">Yeni bir aile oluşturun</a> veya <a href="join_family.php" class="alert-link">mevcut bir aileye katılın</a>.
                <?php endif; ?>
            </div>

        <!-- Seçilen aile için işlem yoksa uyarı -->
        <?php elseif (empty($transactions)): ?>
            <div class="alert alert-warning text-center" role="alert">
                Seçilen aile için henüz işlem bulunmamaktadır.
                <a href="add_transaction.php" class="alert-link">Yeni işlem ekleyin</a>.
            </div>

        <!-- İşlemler listelenir -->
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover rounded-3 overflow-hidden">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Tarih</th>
                            <th scope="col">Miktar</th>
                            <th scope="col">Tip</th>
                            <th scope="col">Kategori</th>
                            <th scope="col">Açıklama</th>
                            <th scope="col">Yapan</th>
                            <th scope="col">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr class="<?php echo ($transaction['type'] == 'expense') ? 'table-danger' : 'table-success'; ?>">
                                <td><?php echo htmlspecialchars($transaction['transaction_date']); ?></td>
                                <td class="<?php echo ($transaction['type'] == 'expense') ? 'text-expense' : 'text-income'; ?>">
                                    <?php echo htmlspecialchars(number_format($transaction['amount'], 2, ',', '.')) . ' TL'; ?>
                                </td>
                                <td><?php echo ($transaction['type'] == 'income') ? 'Gelir' : 'Gider'; ?></td>
                                <td><?php echo htmlspecialchars($transaction['category_name']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['description'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                                <td>
                                    <a href="edit_transaction.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-info btn-action" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_transaction.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-danger btn-action" title="Sil">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Sayfa alt kısmı (footer) dahil edilir.
// Burada </body> ve </html> kapanış etiketleri bulunur.
require_once 'includes/footer.php';
?>
