<?php
// update_db.php
include 'db.php';

echo "<h3>Updating Database...</h3>";

try {
    // Tambah kolom registration_status jika belum ada
    // SQLite tidak support "IF NOT EXISTS" pada ADD COLUMN di versi lama, 
    // jadi kita coba saja, kalau error berarti sudah ada (ignored).
    $sql = "ALTER TABLE squad_entries ADD COLUMN registration_status TEXT DEFAULT 'None'";
    $pdo->exec($sql);
    
    echo "Column 'registration_status' added successfully.<br>";
    echo "Update Selesai! Silakan hapus file ini dan kembali ke <a href='index.php'>Aplikasi</a>.";

} catch (PDOException $e) {
    echo "Info: " . $e->getMessage() . " (Mungkin kolom sudah ada, abaikan saja).<br>";
    echo "<a href='index.php'>Kembali</a>";
}
?>