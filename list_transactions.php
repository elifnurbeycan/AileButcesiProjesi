<?php
// Oturum başlatılır.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Eğer kullanıcı giriş yapmamışsa, login sayfasına yönlendir.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Veritabanı bağlantı dosyasını dahil et.
require_once 'includes/db.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$selected_family_id = null;
$transactions = []; // Listelenecek işlemler

// Kullanıcının üyesi olduğu aileleri çek
$user_families = [];
$stmt_families = $conn->prepare("SELECT f.id, f.name FROM families f JOIN family_members fm ON f.id = fm.family_id WHERE fm.user_id = ? ORDER BY f.name ASC");
$stmt_families->bind_param("i", $user_id);
$stmt_families->execute();
$result_families = $stmt_families->get_result();
while ($row = $result_families->fetch_assoc()) {
    $user_families[] = $row;
}
$stmt_families->close();

// Eğer bir aile ID'si URL'den geldiyse veya formdan seçildiyse
if (isset($_GET['family_id']) && !empty($_GET['family_id'])) {
    $selected_family_id = filter_input(INPUT_GET, 'family_id', FILTER_VALIDATE_INT);
} elseif (isset($_POST['family_id']) && !empty($_POST['family_id'])) {
    $selected_family_id = filter_input(INPUT_POST, 'family_id', FILTER_VALIDATE_INT);
}

// Seçilen aile ID'sinin kullanıcının üyesi olduğu ailelerden biri olduğunu doğrula
$is_user_member_of_selected_family = false;
if ($selected_family_id) {
    foreach ($user_families as $family) {
        if ($family['id'] == $selected_family_id) {
            $is_user_member_of_selected_family = true;
            break;
        }
    }
}

// İşlemleri çek (eğer geçerli bir aile seçilmişse)
if ($selected_family_id && $is_user_member_of_selected_family) {
    // transactions, categories, users tablolarını JOIN ederek detaylı bilgileri al
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
    // Eğer henüz bir aile seçilmemişse veya geçersiz bir aile seçilmişse
    // Burada bir uyarı mesajı gösterilebilir
}


// Veritabanı bağlantısını kapat
$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İşlemleri Listele - Aile Bütçesi Uygulaması</title>
    <!-- Bootstrap CSS v5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Kendi özel CSS dosyanız -->
    <link href="css/style.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background-color: #007bff;
            padding: 15px 0;
        }
        .navbar-brand {
            font-weight: bold;
            color: #fff !important;
            font-size: 1.5rem;
        }
        .navbar-nav .nav-link {
            color: #fff !important;
            margin-left: 20px;
            font-weight: 500;
        }
        .navbar-nav .nav-link:hover {
            color: rgba(255, 255, 255, 0.8) !important;
        }
        .container-main {
            flex-grow: 1;
            padding: 40px 0;
        }
        .list-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            margin: 0 auto;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .table th {
            background-color: #e9ecef;
            color: #495057;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.03);
        }
        .text-income {
            color: #28a745; /* Yeşil */
            font-weight: bold;
        }
        .text-expense {
            color: #dc3545; /* Kırmızı */
            font-weight: bold;
        }
        .btn-action {
            margin-right: 5px;
            border-radius: 5px;
        }
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .form-select {
            border-radius: 8px;
        }
        .family-select-form {
            margin-bottom: 20px;
            padding: 20px;
            background-color: #f2f4f6;
            border-radius: 10px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Aile Bütçesi</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Anasayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_transaction.php">İşlem Ekle</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="list_transactions.php">İşlemleri Listele</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_families.php">Ailelerim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_family.php">Aile Oluştur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="join_family.php">Aileye Katıl</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Çıkış Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <div class="list-container">
            <h2 class="text-center mb-4 text-primary fw-bold">İşlemlerimi Listele</h2>

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

            <?php if (!$selected_family_id || !$is_user_member_of_selected_family): ?>
                <div class="alert alert-info text-center" role="alert">
                    Lütfen yukarıdaki menüden bir aile seçerek işlemlerinizi görüntüleyin.
                    <?php if (empty($user_families)): ?>
                        <br>Henüz hiçbir aileye üye değilsiniz. <a href="create_family.php" class="alert-link">Yeni bir aile oluşturun</a> veya <a href="join_family.php" class="alert-link">mevcut bir aileye katılın</a>.
                    <?php endif; ?>
                </div>
            <?php elseif (empty($transactions)): ?>
                <div class="alert alert-warning text-center" role="alert">
                    Seçilen aile için henüz hiçbir işlem bulunmamaktadır.
                    <a href="add_transaction.php" class="alert-link">Yeni bir işlem ekle</a>.
                </div>
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
                                        <!-- Düzenle butonu - ileride geliştirilecek -->
                                        <a href="edit_transaction.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-info btn-action" title="Düzenle">
                                            <i class="fas fa-edit"></i> <!-- Font Awesome ikonu için link eklenecek -->
                                        </a>
                                        <!-- Sil butonu - ileride geliştirilecek -->
                                        <a href="delete_transaction.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-danger btn-action" title="Sil" onclick="return confirm('Bu işlemi silmek istediğinizden emin misiniz?');">
                                            <i class="fas fa-trash-alt"></i> <!-- Font Awesome ikonu için link eklenecek -->
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

    <!-- Font Awesome İkon Kütüphanesi -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js" xintegrity="sha512-..." crossorigin="anonymous"></script>
    <!-- Bootstrap JS (Popper.js ile birlikte) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
