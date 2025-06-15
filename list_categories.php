<?php
// Oturum başlatılır.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı giriş yapmamışsa, giriş sayfasına yönlendir.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Veritabanı bağlantı dosyası dahil edilir.
require_once 'includes/db.php';

// Header dosyası dahil edilir.
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];
$message = "";
$error = false;

// POST isteğiyle kategori ekleme işlemi yapılır
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_name'])) {
    $category_name = trim($_POST['category_name']);
    $category_type = $_POST['category_type']; // 'income' (Gelir) veya 'expense' (Gider)

    if (empty($category_name)) {
        $message = "Kategori adı boş olamaz.";
        $error = true;
    } else {
        // Aynı türde kategori adının benzersizliği kontrol edilir
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND type = ?");
        $stmt_check->bind_param("ss", $category_name, $category_type);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $category_exists = $result_check->fetch_row()[0] > 0;
        $stmt_check->close();

        if ($category_exists) {
            $message = "Bu kategori adı ve türü zaten mevcut.";
            $error = true;
        } else {
            // Yeni kategori veritabanına eklenir
            $stmt_insert = $conn->prepare("INSERT INTO categories (name, type) VALUES (?, ?)");
            $stmt_insert->bind_param("ss", $category_name, $category_type);

            if ($stmt_insert->execute()) {
                $message = "Kategori başarıyla eklendi.";
                // Form verisi temizlenir
                $_POST = array();
            } else {
                $message = "Kategori eklenirken bir hata oluştu: " . $conn->error;
                $error = true;
            }
            $stmt_insert->close();
        }
    }
}

// Mevcut kategoriler veritabanından çekilir
$categories = [];
$stmt_categories = $conn->prepare("SELECT id, name, type FROM categories ORDER BY type ASC, name ASC");
$stmt_categories->execute();
$result_categories = $stmt_categories->get_result();
while ($row = $result_categories->fetch_assoc()) {
    $categories[] = $row;
}
$stmt_categories->close();

$conn->close();
?>

<!-- HTML içerik başlar -->
<div class="container container-main">
    <div class="list-container">
        <h2 class="text-center mb-4 text-primary fw-bold">Kategorileri Yönet</h2>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $error ? 'alert-danger' : 'alert-success'; ?>" role="alert">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card p-4 mb-4 shadow-sm rounded-3">
            <h4 class="card-title mb-3">Yeni Kategori Ekle</h4>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-3">
                    <label for="category_name" class="form-label">Kategori Adı</label>
                    <input type="text" class="form-control rounded-3" id="category_name" name="category_name" value="<?php echo htmlspecialchars($_POST['category_name'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="category_type" class="form-label">Kategori Türü</label>
                    <select class="form-select rounded-3" id="category_type" name="category_type" required>
                        <option value="income" <?php echo (isset($_POST['category_type']) && $_POST['category_type'] == 'income') ? 'selected' : ''; ?>>Gelir</option>
                        <option value="expense" <?php echo (isset($_POST['category_type']) && $_POST['category_type'] == 'expense') ? 'selected' : ''; ?>>Gider</option>
                    </select>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Kategori Ekle</button>
                </div>
            </form>
        </div>

        <h3 class="mt-5 mb-3 text-primary fw-bold">Mevcut Kategoriler</h3>
        <?php if (empty($categories)): ?>
            <div class="alert alert-info text-center" role="alert">
                Henüz tanımlı kategori bulunmamaktadır. Yukarıdaki formdan yeni bir kategori ekleyebilirsiniz.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover rounded-3 overflow-hidden">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Kategori Adı</th>
                            <th scope="col">Türü</th>
                            <th scope="col">İşlemler</th> <!-- İşlemler sütunu aktif -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td>
                                    <?php
                                    if ($category['type'] == 'income') {
                                        echo '<span class="badge bg-success">Gelir</span>';
                                    } elseif ($category['type'] == 'expense') {
                                        echo '<span class="badge bg-danger">Gider</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <!-- Sil butonu ve onay modalını tetikleyici -->
                                    <button type="button" class="btn btn-sm btn-danger btn-action"
                                            data-bs-toggle="modal" data-bs-target="#deleteCategoryModal"
                                            data-category-id="<?php echo htmlspecialchars($category['id']); ?>"
                                            data-category-name="<?php echo htmlspecialchars($category['name']); ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Kategori Silme Onayı Modalı -->
            <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteCategoryModalLabel">Kategori Silme Onayı</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>"<span id="modalCategoryName"></span>"</strong> kategorisini silmek istediğinizden emin misiniz?</p>
                            <p class="text-danger">Bu kategoriye bağlı işlemler varsa, bu kategori silinemez. Önce ilişkili işlemleri güncellemeniz veya silmeniz gerekebilir.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <a href="#" id="confirmDeleteCategoryBtn" class="btn btn-danger">Evet, Sil</a>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Kategori silme modalı için JavaScript
                document.addEventListener('DOMContentLoaded', function () {
                    const deleteCategoryModal = document.getElementById('deleteCategoryModal');
                    deleteCategoryModal.addEventListener('show.bs.modal', function (event) {
                        // Silme butonunu tetikleyen elementi al
                        const button = event.relatedTarget;
                        // Data özelliklerinden kategori ID ve adını al
                        const categoryId = button.getAttribute('data-category-id');
                        const categoryName = button.getAttribute('data-category-name');

                        // Modaldaki yer tutucuları güncelle
                        const modalCategoryName = deleteCategoryModal.querySelector('#modalCategoryName');
                        modalCategoryName.textContent = categoryName;

                        // Silme onay linkinin href özelliğini güncelle
                        const confirmDeleteBtn = deleteCategoryModal.querySelector('#confirmDeleteCategoryBtn');
                        confirmDeleteBtn.href = `<?php echo BASE_URL; ?>delete_category.php?id=${categoryId}`;
                    });
                });
            </script>

        <?php endif; ?>
    </div>
</div>

<?php
// Footer dosyası dahil edilir.
require_once 'includes/footer.php';
?>
