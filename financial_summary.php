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
$errors = [];
$total_income = 0;
$total_expense = 0;
$net_balance = 0;
$selected_family_id = null;
$start_date = null;
$end_date = null;

$user_families = []; // Kullanıcının üyesi olduğu aileler

// Kullanıcının üyesi olduğu aileleri çek
$stmt_families = $conn->prepare("SELECT f.id, f.name FROM families f JOIN family_members fm ON f.id = fm.family_id WHERE fm.user_id = ? ORDER BY f.name ASC");
$stmt_families->bind_param("i", $user_id);
$stmt_families->execute();
$result_families = $stmt_families->get_result();
while ($row = $result_families->fetch_assoc()) {
    $user_families[] = $row;
}
$stmt_families->close();

// Form gönderildi mi kontrol et (POST metoduyla)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_family_id = filter_input(INPUT_POST, 'family_id', FILTER_VALIDATE_INT);
    $start_date = trim($_POST['start_date']);
    $end_date = trim($_POST['end_date']);

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

    if (!$selected_family_id || !$is_user_member_of_selected_family) {
        $errors[] = "Geçerli bir aile seçmelisiniz.";
    }

    if (empty($start_date) || !strtotime($start_date)) {
        $errors[] = "Başlangıç tarihi boş olamaz veya geçersiz formatta.";
    }
    if (empty($end_date) || !strtotime($end_date)) {
        $errors[] = "Bitiş tarihi boş olamaz veya geçersiz formatta.";
    }
    if (strtotime($start_date) > strtotime($end_date)) {
        $errors[] = "Başlangıç tarihi bitiş tarihinden sonra olamaz.";
    }

    if (empty($errors) && $selected_family_id) {
        // Toplam Geliri Çek
        $stmt_income = $conn->prepare("SELECT SUM(amount) as total FROM transactions WHERE family_id = ? AND type = 'income' AND transaction_date BETWEEN ? AND ?");
        $stmt_income->bind_param("iss", $selected_family_id, $start_date, $end_date);
        $stmt_income->execute();
        $result_income = $stmt_income->get_result()->fetch_assoc();
        $total_income = $result_income['total'] ?? 0;
        $stmt_income->close();

        // Toplam Gideri Çek
        $stmt_expense = $conn->prepare("SELECT SUM(amount) as total FROM transactions WHERE family_id = ? AND type = 'expense' AND transaction_date BETWEEN ? AND ?");
        $stmt_expense->bind_param("iss", $selected_family_id, $start_date, $end_date);
        $stmt_expense->execute();
        $result_expense = $stmt_expense->get_result()->fetch_assoc();
        $total_expense = $result_expense['total'] ?? 0;
        $stmt_expense->close();

        $net_balance = $total_income - $total_expense;
    }

} else {
    // Sayfa ilk yüklendiğinde varsayılan tarih aralığı: Bu ay
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
}

// Veritabanı bağlantısını kapat
$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finansal Özet - Aile Bütçesi Uygulaması</title>
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
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .summary-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            width: 100%;
            margin-top: 20px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.3s ease, border-color 0.3s ease;
            border-radius: 8px;
            font-weight: bold;
            padding: 10px 18px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .alert {
            border-radius: 8px;
        }
        .summary-card {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        .summary-card.income {
            background-color: #d4edda; /* Light green */
            color: #155724; /* Dark green */
            border: 1px solid #c3e6cb;
        }
        .summary-card.expense {
            background-color: #f8d7da; /* Light red */
            color: #721c24; /* Dark red */
            border: 1px solid #f5c6cb;
        }
        .summary-card.balance-positive {
            background-color: #cce5ff; /* Light blue */
            color: #004085; /* Dark blue */
            border: 1px solid #b8daff;
        }
        .summary-card.balance-negative {
            background-color: #f8d7da; /* Light red */
            color: #721c24; /* Dark red */
            border: 1px solid #f5c6cb;
        }
        .summary-card h4 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        .summary-card p {
            font-size: 2rem;
            margin-bottom: 0;
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
                        <a class="nav-link" href="list_transactions.php">İşlemleri Listele</a>
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
                        <a class="nav-link" href="edit_transaction.php">İşlem Düzenle</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="financial_summary.php">Finansal Özet</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Çıkış Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <div class="summary-container">
            <h2 class="text-center mb-4 text-primary fw-bold">Finansal Özet</h2>

            <?php
            // Hata mesajlarını göster
            if (!empty($errors)) {
                echo '<div class="alert alert-danger" role="alert">';
                echo '<ul>';
                foreach ($errors as $error) {
                    echo '<li>' . htmlspecialchars($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label for="family_id" class="form-label">Aile Seçin:</label>
                        <select class="form-select rounded-3" id="family_id" name="family_id" required>
                            <option value="">Bir aile seçin...</option>
                            <?php foreach ($user_families as $family): ?>
                                <option value="<?php echo htmlspecialchars($family['id']); ?>" <?php echo (isset($_POST['family_id']) ? $_POST['family_id'] == $family['id'] : $selected_family_id == $family['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($family['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Başlangıç Tarihi:</label>
                        <input type="date" class="form-control rounded-3" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">Bitiş Tarihi:</label>
                        <input type="date" class="form-control rounded-3" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Göster</button>
                    </div>
                </div>
            </form>

            <?php if ($selected_family_id && empty($errors)): ?>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="summary-card income">
                            <h4>Toplam Gelir</h4>
                            <p><?php echo number_format($total_income, 2, ',', '.') . ' TL'; ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-card expense">
                            <h4>Toplam Gider</h4>
                            <p><?php echo number_format($total_expense, 2, ',', '.') . ' TL'; ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-card <?php echo ($net_balance >= 0) ? 'balance-positive' : 'balance-negative'; ?>">
                            <h4>Net Bakiye</h4>
                            <p><?php echo number_format($net_balance, 2, ',', '.') . ' TL'; ?></p>
                        </div>
                    </div>
                </div>
            <?php elseif ($selected_family_id && !empty($errors)): ?>
                <!-- Hatalar zaten yukarıda gösterildi, burada ek bir mesaj gerekmez -->
            <?php else: ?>
                <div class="alert alert-info text-center" role="alert">
                    Lütfen yukarıdan bir aile ve tarih aralığı seçerek finansal özeti görüntüleyin.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS (Popper.js ile birlikte) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
