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
$transaction_id = null;
$transaction_data = null; // Düzenlenecek işlemin mevcut verileri
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

// İşlem ID'si URL'den geldiyse, mevcut verileri çek
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $transaction_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$transaction_id) {
        $errors[] = "Geçersiz işlem ID'si.";
    } else {
        // ----- Güvenlik Kontrolü: Kullanıcı bu işlemi düzenlemeye yetkili mi? -----
        // İşlemin ait olduğu aileyi bul ve kullanıcının o aileye üye olup olmadığını kontrol et.
        $stmt_fetch_transaction = $conn->prepare("
            SELECT t.id, t.family_id, t.user_id, t.category_id, t.amount, t.type, t.description, t.transaction_date, f.name as family_name, c.name as category_name
            FROM transactions t
            JOIN families f ON t.family_id = f.id
            JOIN categories c ON t.category_id = c.id
            WHERE t.id = ?
        ");
        $stmt_fetch_transaction->bind_param("i", $transaction_id);
        $stmt_fetch_transaction->execute();
        $result_fetch_transaction = $stmt_fetch_transaction->get_result();

        if ($result_fetch_transaction->num_rows == 1) {
            $transaction_data = $result_fetch_transaction->fetch_assoc();

            // Kullanıcının bu işleme ait aileye üye olup olmadığını kontrol et
            $is_user_member = false;
            foreach ($user_families as $family) {
                if ($family['id'] == $transaction_data['family_id']) {
                    $is_user_member = true;
                    break;
                }
            }

            if (!$is_user_member) {
                $errors[] = "Yetkisiz erişim: Bu işlemi düzenlemeye yetkiniz yok.";
                $transaction_data = null; // Yetkisizse verileri sıfırla
            }
        } else {
            $errors[] = "Düzenlenecek işlem bulunamadı.";
        }
        $stmt_fetch_transaction->close();
    }
} else {
    $errors[] = "Düzenlenecek işlem ID'si belirtilmedi.";
}


// Form gönderildi mi kontrol et (POST metoduyla)
if ($_SERVER["REQUEST_METHOD"] == "POST" && $transaction_data) { // Sadece geçerli bir işlem varsa POST işleme başla
    // Form verilerini al
    $family_id = filter_input(INPUT_POST, 'family_id', FILTER_VALIDATE_INT);
    $transaction_type = trim($_POST['transaction_type']);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $transaction_date = trim($_POST['transaction_date']);
    $description = trim($_POST['description']);
    $current_transaction_id = filter_input(INPUT_POST, 'transaction_id', FILTER_VALIDATE_INT); // Gizli alandan gelen ID

    // URL'den gelen ID ile POST'tan gelen ID'nin eşleştiğinden emin ol
    if ($current_transaction_id != $transaction_id) {
        $errors[] = "İşlem ID'si uyuşmuyor. Güvenlik hatası.";
    }

    // ----- Veri Doğrulama (Validation) -----
    // Aile seçimi kontrolü (kullanıcının üyesi olduğu bir aile mi?)
    if (!$family_id || !in_array($family_id, array_column($user_families, 'id'))) {
        $errors[] = "Geçerli bir aile seçmelisiniz.";
    }

    // İşlem tipi kontrolü
    if (!in_array($transaction_type, ['income', 'expense'])) {
        $errors[] = "Geçerli bir işlem tipi seçmelisiniz (Gelir/Gider).";
    }

    // Kategori seçimi kontrolü
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
    } elseif (!strtotime($transaction_date)) {
        $errors[] = "Geçerli bir tarih formatı giriniz.";
    }

    // Açıklama kontrolü
    if (strlen($description) > 255) {
        $errors[] = "Açıklama 255 karakteri geçmemelidir.";
    }

    // ----- İşlemi Güncelleme -----
    if (empty($errors)) {
        $stmt_update = $conn->prepare("UPDATE transactions SET family_id = ?, category_id = ?, amount = ?, type = ?, description = ?, transaction_date = ?, updated_at = NOW() WHERE id = ?");
        // Not: user_id (işlemi yapan kullanıcı) burada güncellenmiyor, çünkü işlem kimin tarafından girildiği değişmez.
        $stmt_update->bind_param("iiidssi", $family_id, $category_id, $amount, $transaction_type, $description, $transaction_date, $transaction_id);

        if ($stmt_update->execute()) {
            $success_message = "İşlem başarıyla güncellendi!";
            // Güncelledikten sonra formu yeni verilerle tekrar doldur
            $transaction_data['family_id'] = $family_id;
            $transaction_data['type'] = $transaction_type;
            $transaction_data['category_id'] = $category_id;
            $transaction_data['amount'] = $amount;
            $transaction_data['description'] = $description;
            $transaction_data['transaction_date'] = $transaction_date;
        } else {
            $errors[] = "İşlem güncellenirken bir hata oluştu: " . $conn->error;
        }
        $stmt_update->close();
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
    <title>İşlem Düzenle - Aile Bütçesi Uygulaması</title>
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
        .transaction-form-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 600px;
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
            padding: 12px 20px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .alert {
            border-radius: 8px;
        }
        .category-income {
            color: #28a745;
            font-weight: bold;
        }
        .category-expense {
            color: #dc3545;
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
                        <a class="nav-link active" aria-current="page" href="edit_transaction.php">İşlem Düzenle</a>
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
            <h2 class="text-center mb-4 text-primary fw-bold">İşlemi Düzenle</h2>

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

            if ($transaction_data): // Sadece geçerli işlem verisi varsa formu göster
            ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . htmlspecialchars($transaction_id); ?>" method="post">
                <input type="hidden" name="transaction_id" value="<?php echo htmlspecialchars($transaction_id); ?>">

                <div class="mb-3">
                    <label for="family_id" class="form-label">Hangi Aile İçin?</label>
                    <select class="form-select rounded-3" id="family_id" name="family_id" required>
                        <option value="">Bir aile seçin...</option>
                        <?php foreach ($user_families as $family): ?>
                            <option value="<?php echo htmlspecialchars($family['id']); ?>" <?php echo (isset($_POST['family_id']) ? $_POST['family_id'] == $family['id'] : $transaction_data['family_id'] == $family['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($family['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="transaction_type" class="form-label">İşlem Tipi</label>
                    <select class="form-select rounded-3" id="transaction_type" name="transaction_type" required>
                        <option value="">İşlem tipi seçin...</option>
                        <option value="income" <?php echo (isset($_POST['transaction_type']) ? $_POST['transaction_type'] == 'income' : $transaction_data['type'] == 'income') ? 'selected' : ''; ?>>Gelir</option>
                        <option value="expense" <?php echo (isset($_POST['transaction_type']) ? $_POST['transaction_type'] == 'expense' : $transaction_data['type'] == 'expense') ? 'selected' : ''; ?>>Gider</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="category_id" class="form-label">Kategori</label>
                    <select class="form-select rounded-3" id="category_id" name="category_id" required>
                        <option value="">Kategori seçin...</option>
                        <?php
                        foreach ($categories as $category) {
                            $selected = ((isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) || (!isset($_POST['category_id']) && $transaction_data['category_id'] == $category['id'])) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($category['id']) . '" data-type="' . htmlspecialchars($category['type']) . '" ' . $selected . '>';
                            echo htmlspecialchars($category['name']);
                            echo '</option>';
                        }
                        ?>
                    </select>
                    <!-- JavaScript ile kategori filtreleme -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const transactionTypeSelect = document.getElementById('transaction_type');
                            const categorySelect = document.getElementById('category_id');
                            // Tüm orijinal kategori seçeneklerini klonla, çünkü appendChild seçeneği taşır.
                            const allCategoryOptions = Array.from(categorySelect.options).map(opt => opt.cloneNode(true));

                            function filterCategories() {
                                const selectedType = transactionTypeSelect.value;
                                const currentCategoryId = categorySelect.value; // Kullanıcının seçtiği mevcut kategori ID'si
                                categorySelect.innerHTML = '<option value="">Kategori seçin...</option>'; // Seçenekleri temizle

                                let foundSelected = false; // Mevcut seçili kategori tipiyle eşleşiyor mu kontrolü

                                allCategoryOptions.forEach(option => {
                                    if (option.value === "") return; // "Kategori seçin..." seçeneğini atla
                                    const categoryType = option.getAttribute('data-type');
                                    if (selectedType === '' || categoryType === selectedType) {
                                        categorySelect.appendChild(option);
                                        if (option.value === currentCategoryId) {
                                            foundSelected = true;
                                        }
                                    }
                                });

                                // Eğer mevcut seçili kategori, yeni filtrelenmiş listede yoksa, "Kategori seçin..."'i seç.
                                if (!foundSelected && currentCategoryId !== "") {
                                     categorySelect.value = "";
                                } else if (currentCategoryId) {
                                     categorySelect.value = currentCategoryId; // Önceden seçili olanı tekrar seç
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
                    <input type="number" step="0.01" min="0" class="form-control rounded-3" id="amount" name="amount" value="<?php echo htmlspecialchars(isset($_POST['amount']) ? $_POST['amount'] : $transaction_data['amount']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="transaction_date" class="form-label">Tarih</label>
                    <input type="date" class="form-control rounded-3" id="transaction_date" name="transaction_date" value="<?php echo htmlspecialchars(isset($_POST['transaction_date']) ? $_POST['transaction_date'] : $transaction_data['transaction_date']); ?>" required>
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label">Açıklama (İsteğe Bağlı)</label>
                    <textarea class="form-control rounded-3" id="description" name="description" rows="3"><?php echo htmlspecialchars(isset($_POST['description']) ? $_POST['description'] : $transaction_data['description']); ?></textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">İşlemi Güncelle</button>
                    <a href="list_transactions.php" class="btn btn-secondary mt-2">İptal / Geri Dön</a>
                </div>
            </form>
            <?php else: // İşlem bulunamadığında veya yetkisiz erişimde ?>
                <div class="alert alert-danger" role="alert">
                    İşlem bulunamadı veya bu işlemi düzenlemeye yetkiniz yok.
                </div>
                <div class="text-center mt-3">
                    <a href="list_transactions.php" class="btn btn-info">Tüm İşlemleri Görüntüle</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS (Popper.js ile birlikte) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
