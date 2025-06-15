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
$success_message = '';

$user_families = []; // Kullanıcının üyesi olduğu aileler
$categories = [];    // İşlem kategorileri

// Kullanıcının üyesi olduğu aileleri çek
$stmt_families = $conn->prepare("SELECT f.id, f.name FROM families f JOIN family_members fm ON f.id = fm.family_id WHERE fm.user_id = ? ORDER BY f.name ASC");
$stmt_families->bind_param("i", $user_id);
$stmt_families->execute();
$result_families = $stmt_families->get_result();
while ($row = $result_families->fetch_assoc()) {
    $user_families[] = $row;
}
$stmt_families->close();

// Kategorileri çek (gelir ve gider tiplerini de al)
$stmt_categories = $conn->prepare("SELECT id, name, type FROM categories ORDER BY name ASC");
$stmt_categories->execute();
$result_categories = $stmt_categories->get_result();
while ($row = $result_categories->fetch_assoc()) {
    $categories[] = $row;
}
$stmt_categories->close();


// Form gönderildi mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al
    $family_id = filter_input(INPUT_POST, 'family_id', FILTER_VALIDATE_INT);
    $transaction_type = trim($_POST['transaction_type']);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $transaction_date = trim($_POST['transaction_date']);
    $description = trim($_POST['description']);

    // ----- Veri Doğrulama (Validation) -----

    // Aile seçimi kontrolü
    if (!$family_id || !in_array($family_id, array_column($user_families, 'id'))) {
        $errors[] = "Geçerli bir aile seçmelisiniz.";
    }

    // İşlem tipi kontrolü
    if (!in_array($transaction_type, ['income', 'expense'])) {
        $errors[] = "Geçerli bir işlem tipi seçmelisiniz (Gelir/Gider).";
    }

    // Kategori seçimi kontrolü
    // Seçilen kategorinin tipinin işlem tipiyle eşleştiğinden emin ol
    $selected_category_type = null;
    foreach ($categories as $cat) {
        if ($cat['id'] == $category_id) {
            $selected_category_type = $cat['type'];
            break;
        }
    }

    if (!$category_id || !$selected_category_type || $selected_category_type !== $transaction_type) {
        $errors[] = "Geçerli bir kategori seçmelisiniz ve kategorinin tipi işlem tipiyle eşleşmelidir.";
    }

    // Miktar kontrolü
    if (!$amount || $amount <= 0) {
        $errors[] = "Miktar sıfırdan büyük bir sayı olmalıdır.";
    }

    // Tarih kontrolü
    if (empty($transaction_date)) {
        $errors[] = "Tarih boş bırakılamaz.";
    } elseif (!strtotime($transaction_date)) { // Geçerli bir tarih formatı mı?
        $errors[] = "Geçerli bir tarih formatı giriniz.";
    }

    // Açıklama kontrolü (isteğe bağlı ama uzunluk kontrolü yapılabilir)
    if (strlen($description) > 255) {
        $errors[] = "Açıklama 255 karakteri geçmemelidir.";
    }

    // ----- İşlem Kaydı -----
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO transactions (family_id, user_id, category_id, amount, type, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidsss", $family_id, $user_id, $category_id, $amount, $transaction_type, $description, $transaction_date);

        if ($stmt->execute()) {
            $success_message = "İşlem başarıyla kaydedildi!";
            // Form alanlarını temizle
            $_POST = array(); // Tüm POST verilerini temizleyerek formu sıfırla
        } else {
            $errors[] = "İşlem kaydedilirken bir hata oluştu: " . $conn->error;
        }
        $stmt->close();
    }
}

// Veritabanı bağlantısını kapat
$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İşlem Ekle - Aile Bütçesi Uygulaması</title>
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
            align-items: flex-start; /* Üste hizala */
        }
        .transaction-form-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            margin-top: 20px; /* Navbar'dan biraz boşluk bırak */
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
            padding: 12px 20px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .alert {
            border-radius: 8px;
        }
        /* Kategoriye göre renk değiştirme (JS ile veya PHP ile koşullu) */
        .category-income {
            color: #28a745; /* Yeşil */
            font-weight: bold;
        }
        .category-expense {
            color: #dc3545; /* Kırmızı */
            font-weight: bold;
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
                        <a class="nav-link active" aria-current="page" href="add_transaction.php">İşlem Ekle</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Harcamaları Listele</a> <!-- Henüz oluşturulmadı -->
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
        <div class="transaction-form-container">
            <h2 class="text-center mb-4 text-primary fw-bold">Yeni İşlem Ekle</h2>

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

            // Başarı mesajını göster
            if (!empty($success_message)) {
                echo '<div class="alert alert-success" role="alert">';
                echo htmlspecialchars($success_message);
                echo '</div>';
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label for="family_id" class="form-label">Hangi Aile İçin?</label>
                    <select class="form-select rounded-3" id="family_id" name="family_id" required>
                        <option value="">Bir aile seçin...</option>
                        <?php foreach ($user_families as $family): ?>
                            <option value="<?php echo htmlspecialchars($family['id']); ?>" <?php echo (isset($_POST['family_id']) && $_POST['family_id'] == $family['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($family['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="transaction_type" class="form-label">İşlem Tipi</label>
                    <select class="form-select rounded-3" id="transaction_type" name="transaction_type" required>
                        <option value="">İşlem tipi seçin...</option>
                        <option value="income" <?php echo (isset($_POST['transaction_type']) && $_POST['transaction_type'] == 'income') ? 'selected' : ''; ?>>Gelir</option>
                        <option value="expense" <?php echo (isset($_POST['transaction_type']) && $_POST['transaction_type'] == 'expense') ? 'selected' : ''; ?>>Gider</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="category_id" class="form-label">Kategori</label>
                    <select class="form-select rounded-3" id="category_id" name="category_id" required>
                        <option value="">Kategori seçin...</option>
                        <?php
                        // Kategorileri işlem tipine göre filtrelemek için JavaScript kullanılabilir.
                        // Şimdilik tüm kategorileri listeliyoruz ve PHP tarafında doğrulama yapıyoruz.
                        foreach ($categories as $category) {
                            $selected = (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($category['id']) . '" data-type="' . htmlspecialchars($category['type']) . '" ' . $selected . '>';
                            echo htmlspecialchars($category['name']);
                            echo '</option>';
                        }
                        ?>
                    </select>
                    <!-- JavaScript ile kategori filtreleme için basit bir script eklenebilir -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const transactionTypeSelect = document.getElementById('transaction_type');
                            const categorySelect = document.getElementById('category_id');
                            const allCategoryOptions = Array.from(categorySelect.options); // Tüm orijinal seçenekleri sakla

                            function filterCategories() {
                                const selectedType = transactionTypeSelect.value;
                                categorySelect.innerHTML = '<option value="">Kategori seçin...</option>'; // Seçenekleri temizle

                                allCategoryOptions.forEach(option => {
                                    if (option.value === "") return; // "Kategori seçin..." seçeneğini atla
                                    const categoryType = option.getAttribute('data-type');
                                    if (selectedType === '' || categoryType === selectedType) {
                                        categorySelect.appendChild(option);
                                    }
                                });
                                // Yeniden filtreledikten sonra önceden seçili olanı kontrol et
                                const currentCategoryId = "<?php echo isset($_POST['category_id']) ? htmlspecialchars($_POST['category_id']) : ''; ?>";
                                if (currentCategoryId) {
                                    categorySelect.value = currentCategoryId;
                                }
                            }

                            transactionTypeSelect.addEventListener('change', filterCategories);

                            // Sayfa yüklendiğinde ve transaction_type seçili ise kategorileri filtrele
                            filterCategories();
                        });
                    </script>
                </div>

                <div class="mb-3">
                    <label for="amount" class="form-label">Miktar (TL)</label>
                    <input type="number" step="0.01" min="0" class="form-control rounded-3" id="amount" name="amount" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="transaction_date" class="form-label">Tarih</label>
                    <input type="date" class="form-control rounded-3" id="transaction_date" name="transaction_date" value="<?php echo htmlspecialchars($_POST['transaction_date'] ?? date('Y-m-d')); ?>" required>
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label">Açıklama (İsteğe Bağlı)</label>
                    <textarea class="form-control rounded-3" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">İşlemi Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS (Popper.js ile birlikte) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
