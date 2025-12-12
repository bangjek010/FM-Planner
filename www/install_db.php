<?php
// install_db.php - All in One Installer & Updater
session_start();     // Start session
session_unset();     // Hapus variabel session
session_destroy();   // Hancurkan session lama

// Pastikan db.php ada
if (!file_exists('db.php')) {
    die("Error: File 'db.php' tidak ditemukan. Pastikan file konfigurasi database ada.");
}
include 'db.php';

echo "<h3>CHECKING AND UPDATING DATABASE...</h3>";

try {
    $pdo->beginTransaction();

    // =========================================================
    // 1. TABLE users
    // =========================================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id_user INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        email TEXT NOT NULL,
        password_hash TEXT NOT NULL
    )");
    
    // Default User
    $chk = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if($chk == 0) {
        $pdo->exec("INSERT INTO users (username, email, password_hash) VALUES ('Manager', 'admin@fm.com', '123')");

    }

    // =========================================================
    // 2. TABLE teams
    // =========================================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS teams (
        id_team INTEGER PRIMARY KEY AUTOINCREMENT,
        id_user INTEGER,
        team_name TEXT,
        manager_name TEXT,
        FOREIGN KEY(id_user) REFERENCES users(id_user)
    )");

    // =========================================================
    // 3. TABLE seasons
    // =========================================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS seasons (
        id_season INTEGER PRIMARY KEY AUTOINCREMENT,
        id_team INTEGER,
        year_label TEXT,
        is_active INTEGER DEFAULT 1,
        FOREIGN KEY(id_team) REFERENCES teams(id_team) ON DELETE CASCADE
    )");

    // =========================================================
    // 4. TABLE peak_age_rules
    // =========================================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS peak_age_rules (
        id_rule INTEGER PRIMARY KEY AUTOINCREMENT,
        category_name TEXT,
        peak_age_val INTEGER,
        ui_color_hex TEXT
    )");
    
    // Isi Default Peak Rules
    $chk = $pdo->query("SELECT COUNT(*) FROM peak_age_rules")->fetchColumn();
    if($chk == 0) {
        $sql = "INSERT INTO peak_age_rules (category_name, peak_age_val, ui_color_hex) VALUES 
        ('GK', 32, '#9b59b6'), ('CB', 30, '#3498db'), ('FULL BACK', 29, '#85c1e9'),
        ('WING BACK', 29, '#85c1e9'), ('CDM', 30, '#e67e22'), ('CM', 28, '#2ecc71'),
        ('WIDE MID', 28, '#f1c40f'), ('AM', 28, '#2ecc71'), ('WINGER', 28, '#f1c40f'),
        ('ST', 27, '#e74c3c')";
        $pdo->exec($sql);

    }

    // =========================================================
    // 5. TABLE positions
    // =========================================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS positions (
        id_position INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT NOT NULL,
        full_name TEXT,
        color_category TEXT,
        id_peak_rule INTEGER,
        FOREIGN KEY(id_peak_rule) REFERENCES peak_age_rules(id_rule)
    )");

    // Isi Default Positions
    $chk = $pdo->query("SELECT COUNT(*) FROM positions")->fetchColumn();
    if($chk == 0) {
        $sql = "INSERT INTO positions (code, full_name, color_category, id_peak_rule) VALUES 
        ('GK', 'Goalkeeper', '#9b59b6', 1), ('RB', 'Right Back', '#85c1e9', 3), ('CB', 'Center Back', '#3498db', 2),
        ('LB', 'Left Back', '#85c1e9', 3), ('WBR', 'Wing Back Right', '#85c1e9', 4), ('DM', 'Defensive Mid', '#e67e22', 5),
        ('WBL', 'Wing Back Left', '#85c1e9', 4), ('MR', 'Midfielder Right', '#f1c40f', 7), ('CM', 'Central Mid', '#2ecc71', 6),
        ('ML', 'Midfielder Left', '#f1c40f', 7), ('RW', 'Right Wing', '#f1c40f', 9), ('AMC', 'Attacking Mid', '#2ecc71', 8),
        ('LW', 'Left Wing', '#f1c40f', 9), ('ST', 'Striker', '#e74c3c', 10)";
        $pdo->exec($sql);

    }

    // =========================================================
    // 6. TABLE players
    // =========================================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS players (
        id_player INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT,
        nationality TEXT DEFAULT 'Unknown',
        primary_position_id INTEGER
    )");

    // =========================================================
    // 7. TABLE squad_entries (UPDATE: registration_status)
    // =========================================================
    // Create tabel dasar (jika belum ada)
    $pdo->exec("CREATE TABLE IF NOT EXISTS squad_entries (
        id_entry INTEGER PRIMARY KEY AUTOINCREMENT,
        id_season INTEGER,
        id_player INTEGER,
        id_tactic_position INTEGER,
        current_age INTEGER,
        rating REAL,
        potential REAL,
        squad_status TEXT DEFAULT '1st Team', 
        t1_top REAL, t1_left REAL,
        t2_top REAL, t2_left REAL,
        t3_top REAL, t3_left REAL,
        price TEXT,
        current_club TEXT,
        sl_top REAL, sl_left REAL,
        registration_status TEXT DEFAULT 'None', -- Kolom baru langsung dimasukkan
        FOREIGN KEY(id_season) REFERENCES seasons(id_season) ON DELETE CASCADE,
        FOREIGN KEY(id_player) REFERENCES players(id_player),
        FOREIGN KEY(id_tactic_position) REFERENCES positions(id_position) ON DELETE CASCADE
    )");

    // CEK KOLOM registration_status (Untuk Update Database Lama)
    $cols = $pdo->query("PRAGMA table_info(squad_entries)")->fetchAll(PDO::FETCH_ASSOC);
    $hasRegStatus = false;
    foreach($cols as $col) {
        if ($col['name'] == 'registration_status') {
            $hasRegStatus = true;
            break;
        }
    }

    if (!$hasRegStatus) {
        $pdo->exec("ALTER TABLE squad_entries ADD COLUMN registration_status TEXT DEFAULT 'None'");
        echo "- Kolom 'registration_status' berhasil ditambahkan ke squad_entries.<br>";
    }

    // =========================================================
    // 8. TABLE global_favorites (BARU)
    // =========================================================
    $pdo->exec("CREATE TABLE IF NOT EXISTS global_favorites (
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
    )");

    $pdo->commit();

    echo "<a href='index.php' style='padding:10px 20px; background:#2ecc71; color:white; text-decoration:none; font-weight:bold; border-radius:5px;'>MASUK KE APLIKASI</a>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<h3>Gagal: " . $e->getMessage() . "</h3>";
}
?>