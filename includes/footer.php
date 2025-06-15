<?php
// Bu dosya, tüm sayfaların sonunda dahil edilecek ortak alt bilgi (footer) kısmıdır.
// <main>, <body> ve <html> etiketlerini kapatır, JavaScript dosyalarını içerir.
?>
    </main> <!-- Header dosyasında açılan <main> etiketini kapatır -->

    <footer class="bg-light text-center py-3 mt-auto shadow-sm">
        <div class="container">
            <small>&copy; <?= date('Y') ?> Aile Bütçesi Uygulaması. Tüm hakları saklıdır.</small>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle (Popper da dahil) -->
    <!-- Bu script, sayfanın içeriği yüklendikten sonra çalışarak performansı artırır. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
