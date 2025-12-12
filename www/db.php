<?php
// db.php - VERSI SQLITE UNTUK PHP DESKTOP
try {
    // Database akan dibuat otomatis menjadi file bernama 'database.sqlite' di folder yang sama
    $pdo = new PDO("sqlite:" . __DIR__ . "/database.sqlite");
    
    // Aktifkan Error Mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Aktifkan Foreign Keys di SQLite
    $pdo->exec("PRAGMA foreign_keys = ON;");
    
} catch (PDOException $e) {
    die("Error Database: " . $e->getMessage());
}
?>