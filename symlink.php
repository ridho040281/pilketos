<?php
/**
 * Script Otomatis Pembuat Symlink Storage Laravel di Hosting / cPanel
 * Akses file ini lewat browser: https://pilketos.mtsn1blitar.sch.id/symlink.php
 */

if (file_exists(__DIR__ . '/../storage/app/public')) {
    $target = realpath(__DIR__ . '/../storage/app/public');
    $link = __DIR__ . '/storage';
} elseif (file_exists(__DIR__ . '/storage/app/public')) {
    $target = realpath(__DIR__ . '/storage/app/public');
    $link = __DIR__ . '/public/storage';
} else {
    $target = __DIR__ . '/storage/app/public';
    $link = __DIR__ . '/public/storage';
    @mkdir($target, 0755, true);
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Symlink Storage - Pilketos</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #1e293b; padding: 40px 20px; }
        .box { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        h2 { margin-top: 0; color: #0f172a; }
        code { background: #f1f5f9; padding: 3px 8px; border-radius: 6px; font-size: 13px; color: #4338ca; word-break: break-all; }
        .success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 15px; border-radius: 10px; margin: 20px 0; }
        .warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 15px; border-radius: 10px; margin: 20px 0; }
        .btn { display: inline-block; background: #4f46e5; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; font-size: 14px; margin-top: 10px; }
    </style>
</head>
<body>
<div class="box">
    <h2>🛠️ Setup Symlink Storage Laravel</h2>
    <p>Target Folder Asli:<br><code><?= htmlspecialchars($target ?: 'Belum terdeteksi') ?></code></p>
    <p>Lokasi Shortcut Publik:<br><code><?= htmlspecialchars($link) ?></code></p>

    <?php
    if (is_link($link) || is_file($link)) {
        @unlink($link);
        echo "<div class='warning'>ℹ️ Shortcut/link lama yang rusak berhasil dibersihkan.</div>";
    }

    if (!file_exists($target)) {
        @mkdir($target, 0755, true);
    }

    $created = false;
    if (function_exists('symlink')) {
        $created = @symlink($target, $link);
    }

    if ($created) {
        echo "<div class='success'>";
        echo "<strong>BERHASIL! 🎉</strong><br>";
        echo "Tautan storage server berhasil dibuat. Gambar Logo Sekolah dan Favicon sekarang sudah dapat tampil normal di internet.";
        echo "</div>";
        echo "<p style='font-size: 13px; color: #64748b;'><em>Catatan: Demi keamanan, Anda dapat menghapus file <code>symlink.php</code> ini setelah selesai.</em></p>";
        echo "<a href='/' class='btn'>Buka Aplikasi Pilketos &rarr;</a>";
    } else {
        echo "<div class='warning'>";
        echo "<strong>PERINGATAN</strong><br>";
        echo "Fungsi <code>symlink()</code> dinonaktifkan oleh penyedia hosting Anda. Namun tenang, rute fallback bawaan di aplikasi kami akan otomatis membaca gambar secara langsung.";
        echo "</div>";
        echo "<a href='/' class='btn'>Buka Aplikasi Pilketos &rarr;</a>";
    }
    ?>
</div>
</body>
</html>
