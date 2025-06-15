<?php
// Oturum başlatılıyor, böylece oturum verilerine erişebiliriz.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı giriş yapmamışsa, giriş sayfasına yönlendiriyoruz.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Veritabanı bağlantısını dahil ediyoruz.
require_once 'includes/db.php';

// Sayfanın üst kısmı için header dosyasını ekliyoruz.
// Burada <head> ve <body> açılış etiketleri yer alıyor, 
// bu yüzden add_transaction.php içinde HTML, HEAD, BODY etiketlerine gerek yok.
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$errors = [];
$success_message = '';
$families = []; // Kullanıcının ait olduğu aileler burada tutulacak.
$categories = []; // Gider ve gelir kategorileri buraya yüklenecek.

// Kullanıcının üye olduğu aileleri veritabanından alıyoruz.
$stmt_families = $conn->prepare("SELECT f.id, f.name FROM families f JOIN family_members fm ON f.id = fm.family_id WHERE fm.user_id = ? ORDER BY f.name ASC");
$stmt_families->bind_param("i", $user_id);
$stmt_families->execute();
$result_families = $stmt_families->get_result();
while ($row = $result_families->fetch_assoc()) {
    $families[] = $row;
}
$stmt_families->close();

// Kategorileri veritabanından çekiyoruz.
// Burada kategorilerin tipi de (gelir/gider) alınarak işlem türüne göre filtrelenmek üzere kullanılıyor.
$stmt_categories = $conn->prepare("SELECT id, name, type FROM categories ORDER BY name ASC");
$stmt_categories->execute();
$result_categories = $stmt_categories->get_result();
while ($row = $result_categories->fetch_assoc()) {
    $categories[] = $row;
}
$stmt_categories->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri alıyoruz.
    $family_id = $_POST['family_id'] ?? null;
    $type = $_POST['type'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $amount = $_POST['amount'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d'); // Tarih boşsa bugünün tarihi kullanılır.

    // Form verilerini kontrol ediyoruz.
    if (empty($family_id) || !in_array($family_id, array_column($families, 'id'))) {
        $errors[] = "Lütfen geçerli bir aile seçin.";
    }
    if (!in_array($type, ['income', 'expense'])) {
        $errors[] = "İşlem türü olarak 'Gelir' veya 'Gider' seçmelisiniz.";
    }

    // Seçilen kategori ve işlem türünün uyumlu olup olmadığını kontrol ediyoruz.
    $selected_category_type = null;
    foreach ($categories as $cat) {
        if ($cat['id'] == $category_id) {
            $selected_category_type = $cat['type'];
            break;
        }
    }
    if (empty($category_id) || !$selected_category_type || $selected_category_type !== $type) {
        $errors[] = "Kategori seçimi geçersiz veya işlem türü ile uyumsuz.";
    }

    if (!is_numeric($amount) || $amount <= 0) {
        $errors[] = "Miktar pozitif ve sayısal bir değer olmalı.";
    }
    if (empty($description)) {
        $errors[] = "Açıklama boş bırakılamaz.";
    }

    // Tarihin doğru formatta olup olmadığını kontrol ediyoruz (YYYY-AA-GG).
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $transaction_date)) {
        $errors[] = "Tarih formatı YYYY-AA-GG şeklinde olmalıdır.";
    }

    if (empty($errors)) {
        // Veritabanına işlemi ekliyoruz.
        // Parametre bağlamada sıralama ve türler doğru olmalı.
        $stmt_insert = $conn->prepare("INSERT INTO transactions (family_id, user_id, type, category_id, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("iisidss", $family_id, $user_id, $type, $category_id, $amount, $description, $transaction_date);

        if ($stmt_insert->execute()) {
            $success_message = "İşlem başarıyla kaydedildi.";
            // Form verilerini temizleyerek sayfada temiz bir form gösteriyoruz.
            $_POST = array();
        } else {
            $errors[] = "İşlem kaydedilirken hata oluştu: " . $conn->error;
        }
        $stmt_insert->close();
    }
}

$conn->close();
?>

<div class="container container-main">
    <div class="add-transaction-container">
        <h2 class="text-center mb-4 text-primary fw-bold">Yeni İşlem Ekle</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-3">
                <label for="family_id" class="form-label">Aile / Grup Seçin</label>
                <select class="form-select rounded-3" id="family_id" name="family_id" required>
                    <option value="">Aile Seçin...</option>
                    <?php foreach ($families as $family): ?>
                        <option value="<?php echo htmlspecialchars($family['id']); ?>" <?php echo (isset($_POST['family_id']) && $_POST['family_id'] == $family['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($family['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="type" class="form-label">İşlem Türü</label>
                <select class="form-select rounded-3" id="type" name="type" required>
                    <option value="">Seçiniz...</option>
                    <option value="income" <?php echo (isset($_POST['type']) && $_POST['type'] == 'income') ? 'selected' : ''; ?>>Gelir</option>
                    <option value="expense" <?php echo (isset($_POST['type']) && $_POST['type'] == 'expense') ? 'selected' : ''; ?>>Gider</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="category_id" class="form-label">Kategori</label>
                <select class="form-select rounded-3" id="category_id" name="category_id" required>
                    <option value="">Kategori Seçin...</option>
                    <?php
                    // JavaScript ile işlem türüne göre kategorileri filtrelemek için tüm kategorileri listeliyoruz.
                    foreach ($categories as $category):
                    ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>" data-type="<?php echo htmlspecialchars($category['type']); ?>"
                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <script>
                    // İşlem türü değiştiğinde kategori listesini filtreleyen kod
                    document.addEventListener('DOMContentLoaded', function() {
                        const transactionTypeSelect = document.getElementById('type');
                        const categorySelect = document.getElementById('category_id');
                        const allCategoryOptions = Array.from(categorySelect.options).map(opt => opt.cloneNode(true)); // Orijinal kategorileri yedekliyoruz.

                        function filterCategories() {
                            const selectedType = transactionTypeSelect.value;
                            const currentSelectedCategoryId = categorySelect.value;

                            // Kategori listesini temizle, ilk seçenek dışındaki tüm seçenekleri kaldır
                            categorySelect.innerHTML = '<option value="">Kategori Seçin...</option>';

                            let foundCurrentSelected = false;

                            allCategoryOptions.forEach(option => {
                                if (option.value === "") return; // Placeholder'ı atla
                                const categoryType = option.getAttribute('data-type');
                                if (selectedType === '' || categoryType === selectedType) {
                                    categorySelect.appendChild(option);
                                    if (option.value === currentSelectedCategoryId) {
                                        foundCurrentSelected = true;
                                    }
                                }
                            });

                            // Önceden seçili kategori filtreye uymuyorsa seçimi sıfırla
                            if (!foundCurrentSelected || (selectedType !== '' && allCategoryOptions.find(opt => opt.value === currentSelectedCategoryId && opt.getAttribute('data-type') === selectedType) === undefined)) {
                                categorySelect.value = "";
                            } else {
                                categorySelect.value = currentSelectedCategoryId;
                            }
                        }

                        transactionTypeSelect.addEventListener('change', filterCategories);

                        // Sayfa yüklendiğinde filtreyi çalıştır
                        filterCategories();
                    });
                </script>
            </div>

            <div class="mb-3">
                <label for="amount" class="form-label">Miktar</label>
                <input type="number" step="0.01" class="form-control rounded-3" id="amount" name="amount" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Açıklama</label>
                <textarea class="form-control rounded-3" id="description" name="description" rows="3" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="transaction_date" class="form-label">İşlem Tarihi</label>
                <input type="date" class="form-control rounded-3" id="transaction_date" name="transaction_date" value="<?php echo htmlspecialchars($_POST['transaction_date'] ?? date('Y-m-d')); ?>" required>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">İşlemi Ekle</button>
            </div>
        </form>
    </div>
</div>

<?php
// Sayfanın alt kısmı için footer dosyasını ekliyoruz.
// Burada </body> ve </html> kapanış etiketleri yer alıyor.
require_once 'includes/footer.php';
?>
