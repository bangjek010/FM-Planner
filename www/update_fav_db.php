<?php
// update_fav_db.php
include 'db.php';
echo "<h3>Membuat Tabel Global Favorites...</h3>";

try {
    $sql = "CREATE TABLE IF NOT EXISTS global_favorites (
        id_fav INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT,
        pos_code TEXT,
        current_age INTEGER,
        rating REAL,
        potential REAL,
        price TEXT,
        current_club TEXT,
        nationality TEXT,
        registration_status TEXT,
        peak_age_val INTEGER, 
        fav_top REAL, 
        fav_left REAL
    )";
    $pdo->exec($sql);
    echo "Berhasil! Tabel 'global_favorites' telah dibuat.<br>";
    echo "<a href='index.php'>Kembali ke Aplikasi</a>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>