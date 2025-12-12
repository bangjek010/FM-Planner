<?php
if (!file_exists('database.sqlite')) {
    header("Location: install_db.php");
    exit;
}
session_start();
include 'db.php';

$action = $_POST['action'] ?? '';

// --- LIMIT PEMAIN CONFIG ---
$LIMITS = ['1st Team' => 11, '2nd Team' => 11, 'Subs' => 12, '2nd Team Subs' => 12];

// =======================================================
// 1. MANAJEMEN TIM (TEAM CRUD)
// =======================================================

// A. TAMBAH TIM BARU
if ($action == 'create_team') {
    $name = $_POST['team_name'];
    $manager = $_POST['manager_name'];

    try {
        $pdo->beginTransaction();
        // 1. Buat Tim
        $stmt = $pdo->prepare("INSERT INTO teams (id_user, team_name, manager_name) VALUES (1, ?, ?)");
        $stmt->execute([$name, $manager]);
        $newTeamId = $pdo->lastInsertId();

        // 2. Buat Season Default Otomatis (misal 2024/2025)
        $stmt = $pdo->prepare("INSERT INTO seasons (id_team, year_label) VALUES (?, '2024/2025')");
        $stmt->execute([$newTeamId]);
        $newSeasonId = $pdo->lastInsertId();

        $pdo->commit();

        // Set Session langsung masuk ke tim baru
        $_SESSION['curr_team'] = $newTeamId;
        $_SESSION['curr_season'] = $newSeasonId;
        
        header("Location: index.php");
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}

// B. GANTI TIM (SWITCH)
if ($action == 'select_team') {
    $id_team = $_POST['id_team'];
    
    // Cari season terakhir dari tim tersebut
    $stmt = $pdo->prepare("SELECT id_season FROM seasons WHERE id_team = ? ORDER BY id_season DESC LIMIT 1");
    $stmt->execute([$id_team]);
    $season = $stmt->fetchColumn();

    if ($season) {
        $_SESSION['curr_team'] = $id_team;
        $_SESSION['curr_season'] = $season;
    }
    header("Location: index.php");
}

// C. EDIT NAMA TIM
if ($action == 'update_team') {
    $name = $_POST['team_name'];
    $id_team = $_SESSION['curr_team'];
    $stmt = $pdo->prepare("UPDATE teams SET team_name = ? WHERE id_team = ?");
    $stmt->execute([$name, $id_team]);
    header("Location: index.php");
}

// D. HAPUS TIM
if ($action == 'delete_team') {
    $id_team = $_POST['id_team'];
    $stmt = $pdo->prepare("DELETE FROM teams WHERE id_team = ?");
    $stmt->execute([$id_team]);

    // Jika tim yang dihapus adalah tim yang sedang dibuka, logout session
    if (isset($_SESSION['curr_team']) && $_SESSION['curr_team'] == $id_team) {
        unset($_SESSION['curr_team']);
        unset($_SESSION['curr_season']);
    }
    header("Location: index.php");
}

// =======================================================
// 2. MANAJEMEN SEASON (SEASON CRUD)
// =======================================================

// A. TAMBAH SEASON BARU
if ($action == 'create_season') {
    $year = $_POST['year_label'];
    $id_team = $_SESSION['curr_team'];
    $prev_season_id = $_SESSION['curr_season']; // Season yang sedang dibuka saat ini
    
    try {
        $pdo->beginTransaction();

        // 1. Buat Season Baru
        $stmt = $pdo->prepare("INSERT INTO seasons (id_team, year_label) VALUES (?, ?)");
        $stmt->execute([$id_team, $year]);
        $new_season_id = $pdo->lastInsertId();
        
        // 2. DUPLIKASI SKUAD & TAKTIK DARI SEASON SEBELUMNYA
        if ($prev_season_id) {
            // Query sakti: Mengcopy data dari season lama ke season baru
            // Kita juga menambahkan +1 pada current_age agar realistis
            $sqlCopy = "INSERT INTO squad_entries (
                            id_season, 
                            id_player, 
                            id_tactic_position, 
                            current_age, 
                            rating, 
                            potential, 
                            squad_status,
                            t1_top, t1_left,
                            t2_top, t2_left,
                            t3_top, t3_left
                        )
                        SELECT 
                            :new_id,            -- Masukkan ID Season Baru
                            id_player, 
                            id_tactic_position, 
                            current_age + 1,    -- Otomatis Umur Tambah 1 Tahun
                            rating, 
                            potential, 
                            squad_status,
                            t1_top, t1_left,    -- Copy posisi taktik 1
                            t2_top, t2_left,    -- Copy posisi taktik 2
                            t3_top, t3_left     -- Copy posisi taktik 3
                        FROM squad_entries 
                        WHERE id_season = :old_id"; // Ambil dari Season Lama

            $stmtCopy = $pdo->prepare($sqlCopy);
            $stmtCopy->execute([
                ':new_id' => $new_season_id,
                ':old_id' => $prev_season_id
            ]);
        }
        
        $pdo->commit();

        // 3. Pindah otomatis ke season baru
        $_SESSION['curr_season'] = $new_season_id;
        header("Location: index.php");

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}

// B. GANTI SEASON
if ($action == 'select_season') {
    $_SESSION['curr_season'] = $_POST['id_season'];
    header("Location: index.php");
}

// C. EDIT NAMA SEASON
if ($action == 'update_season') {
    $year = $_POST['year_label'];
    $id_season = $_SESSION['curr_season']; // Edit season yg aktif
    $stmt = $pdo->prepare("UPDATE seasons SET year_label = ? WHERE id_season = ?");
    $stmt->execute([$year, $id_season]);
    header("Location: index.php");
}

// D. HAPUS SEASON
if ($action == 'delete_season') {
    $id_season = $_POST['id_season'];
    
    // Cek jangan hapus season satu-satunya
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM seasons WHERE id_team = ?");
    $stmt->execute([$_SESSION['curr_team']]);
    if ($stmt->fetchColumn() <= 1) {
        echo "<script>alert('Cannot delete the only season!'); window.location='index.php';</script>";
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM seasons WHERE id_season = ?");
    $stmt->execute([$id_season]);
    
    // Jika season aktif dihapus, pindah ke season lain
    if ($_SESSION['curr_season'] == $id_season) {
        $stmt = $pdo->prepare("SELECT id_season FROM seasons WHERE id_team = ? LIMIT 1");
        $stmt->execute([$_SESSION['curr_team']]);
        $_SESSION['curr_season'] = $stmt->fetchColumn();
    }
    header("Location: index.php");
}


// =======================================================
// 3. MANAJEMEN PEMAIN & POSISI (SAMA SEPERTI SEBELUMNYA)
// =======================================================

// UPDATE POSISI
if ($action == 'save_position') {
    $id = $_POST['id_entry'];
    $tactic = $_POST['tactic_id']; 
    $top = $_POST['top'];
    $left = $_POST['left'];

    // Jika Taktik adalah 'SL' (Shortlist Mode)
    if ($tactic == 'SL') {
        $sql = "UPDATE squad_entries SET sl_top = ?, sl_left = ? WHERE id_entry = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$top, $left, $id]);
        echo "Shortlist Position Saved";
    } 
    // Jika Taktik 1, 2, atau 3
    else if (in_array($tactic, ['1', '2', '3'])) {
        $col_top = "t{$tactic}_top";
        $col_left = "t{$tactic}_left";
        $sql = "UPDATE squad_entries SET $col_top = ?, $col_left = ? WHERE id_entry = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$top, $left, $id]);
        echo "Tactic $tactic Saved";
    }
    exit;
}

// PINDAHKAN PEMAIN DARI TABEL KE LAPANGAN
if ($action == 'move_to_pitch') {
    $id = $_POST['id_entry'];
    $tactic = $_POST['tactic_id'];
    $top = $_POST['top'];
    $left = $_POST['left'];
    // Tangkap parameter baru: target_status
    $target_status = $_POST['target_status'] ?? '1st Team'; 
    
    // --- SKENARIO 1: MODE SHORTLIST / SIMULASI ---
    if ($tactic == 'SL') {
        $sql = "UPDATE squad_entries SET sl_top = ?, sl_left = ? WHERE id_entry = ?";
        $pdo->prepare($sql)->execute([$top, $left, $id]);
        echo "Moved to Simulation";
    } 
    // --- SKENARIO 2: MODE SQUAD (REAL - 1st & 2nd Team) ---
    else {
        // Cek Limit Squad berdasarkan Target (1st Team atau 2nd Team)
        // Kita hitung berapa pemain di lapangan untuk tim tersebut
        // Catatan: Limit 11 pemain hanya validasi longgar, 
        // karena drag & drop bisa menimpa posisi.
        
        $col_top = "t{$tactic}_top";
        $col_left = "t{$tactic}_left";
        
        // Update Status & Koordinat
        // Jika target '2nd Team', maka status jadi '2nd Team'
        // Jika target '1st Team', maka status jadi '1st Team'
        $sql = "UPDATE squad_entries SET squad_status = ?, $col_top = ?, $col_left = ? WHERE id_entry = ?";
        $pdo->prepare($sql)->execute([$target_status, $top, $left, $id]);
        
        echo "Moved to $target_status";
    }
    exit;
}

// SAVE PLAYER (DENGAN VALIDASI LIMIT & SESSION SEASON)
if ($action == 'save_player') {
    $id_season = $_SESSION['curr_season'];
    $id_entry  = $_POST['id_entry'];
    $name      = $_POST['full_name'];
    $age       = $_POST['current_age'];
    $rat       = $_POST['rating'] ?? 0;
    $pot       = $_POST['potential'] ?? 0;
    $pos_id    = $_POST['pos_code'];
    $status    = $_POST['squad_status'];
        
        // Data Baru
    $reg_status = $_POST['registration_status'] ?? 'None'; // Default None
    $price      = !empty($_POST['price']) ? $_POST['price'] : null;
    $club       = !empty($_POST['current_club']) ? $_POST['current_club'] : null;

    // Validasi Limit (Kecuali untuk Players Out & Shortlist yang unlimited)
    if (!in_array($status, ['Players Out', 'Shortlist'])) {
        $sqlCount = "SELECT COUNT(*) FROM squad_entries WHERE id_season = ? AND squad_status = ?";
        if ($id_entry > 0) $sqlCount .= " AND id_entry != $id_entry";
        $stmt = $pdo->prepare($sqlCount);
        $stmt->execute([$id_season, $status]);
        
        $limit = $LIMITS[$status] ?? 99; // Default 99 jika tidak ada di config
        if ($stmt->fetchColumn() >= $limit) {
            echo "<script>alert('Full! Max $limit players.'); window.history.back();</script>";
            exit;
        }
    }

        if ($id_entry > 0) {
            // --- EDIT EXISTING ---
            $pdo->beginTransaction();
            
            $pid = $pdo->query("SELECT id_player FROM squad_entries WHERE id_entry=$id_entry")->fetchColumn();
            $pdo->prepare("UPDATE players SET full_name = ?, primary_position_id = ? WHERE id_player = ?")
                ->execute([$name, $pos_id, $pid]);
            
            // UPDATE QUERY (Tambah registration_status)
            $sql = "UPDATE squad_entries SET 
                    current_age = ?, rating = ?, potential = ?, id_tactic_position = ?, 
                    squad_status = ?, registration_status = ?, price = ?, current_club = ? 
                    WHERE id_entry = ?";
            $pdo->prepare($sql)->execute([$age, $rat, $pot, $pos_id, $status, $reg_status, $price, $club, $id_entry]);
            
            $pdo->commit();

        } else {
            // --- ADD NEW ---
            $pdo->beginTransaction();

            $pdo->prepare("INSERT INTO players (full_name, primary_position_id) VALUES (?, ?)")
                ->execute([$name, $pos_id]);
            $new_pid = $pdo->lastInsertId();
            
            // INSERT QUERY (Tambah registration_status)
            $sql = "INSERT INTO squad_entries (
                        id_season, id_player, id_tactic_position, current_age, 
                        rating, potential, squad_status, registration_status, price, current_club
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$id_season, $new_pid, $pos_id, $age, $rat, $pot, $status, $reg_status, $price, $club]);
            
            $pdo->commit();
        }
    
    // Redirect kembali ke halaman asal (Shortlist atau Index)
        $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header("Location: $referer");
        exit;
}

// DELETE PLAYER
if ($action == 'delete_player') {
    $pdo->prepare("DELETE FROM squad_entries WHERE id_entry = ?")->execute([$_POST['id_entry']]);
    header("Location: index.php");
}
// =======================================================
// PINDAHKAN PEMAIN SKUAD KE PLAYERS OUT (DAFTAR JUAL)
// =======================================================
if ($action == 'move_to_players_out') {
    $id_entry = $_POST['id_entry'];
    $price    = $_POST['price'];
    
    // Validasi input
    if (empty($id_entry)) {
        header("Location: shortlist.php"); exit;
    }

    // Update Status jadi 'Players Out' dan Set Harga
    // Kita juga bisa reset koordinat lapangan jika mau, tapi tidak wajib
    $sql = "UPDATE squad_entries SET squad_status = 'Players Out', price = ? WHERE id_entry = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$price, $id_entry]);
    
    // Kembali ke shortlist
    header("Location: shortlist.php");
    exit;
}

// =======================================================
// KEMBALIKAN PEMAIN DARI PLAYERS OUT KE SKUAD (CANCEL JUAL)
// =======================================================
    // --- RETURN TO SQUAD ---
    if ($action == 'return_to_squad') {
        $id = $_POST['id_entry'];
        $pdo->prepare("UPDATE squad_entries SET squad_status = '2nd Team', price = NULL WHERE id_entry = ?")
            ->execute([$id]);
        header("Location: shortlist.php");
        exit;
    }

    // [BARU] REMOVE FROM PITCH (SHORTLIST ONLY)
    if ($action == 'remove_from_pitch_sl') {
        $id = $_POST['id_entry'];
        // Kosongkan koordinat Shortlist (sl_top, sl_left)
        $sql = "UPDATE squad_entries SET sl_top = NULL, sl_left = NULL WHERE id_entry = ?";
        $pdo->prepare($sql)->execute([$id]);
        echo "Removed from Pitch";
        exit;
    }

// =======================================================
// SWAP PLAYERS (Tukar Posisi / Tukar Status)
// =======================================================
if ($action == 'swap_players') {
    $id1 = $_POST['id1'];
    $id2 = $_POST['id2'];
    $tactic = $_POST['tactic_id'];

    // Ambil data kedua pemain
    $stmt = $pdo->prepare("SELECT * FROM squad_entries WHERE id_entry IN (?, ?)");
    $stmt->execute([$id1, $id2]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pastikan dapat 2 data
    if (count($players) < 2) exit;

    $p1 = ($players[0]['id_entry'] == $id1) ? $players[0] : $players[1];
    $p2 = ($players[0]['id_entry'] == $id2) ? $players[0] : $players[1];

    $col_top = "t{$tactic}_top";
    $col_left = "t{$tactic}_left";

    // 1. TUKAR STATUS (Squad Status)
    $status1 = $p1['squad_status'];
    $status2 = $p2['squad_status'];

    // 2. TUKAR KOORDINAT (Hanya jika di lapangan)
    $top1 = $p1[$col_top]; $left1 = $p1[$col_left];
    $top2 = $p2[$col_top]; $left2 = $p2[$col_left];

    // EKSEKUSI UPDATE
    $pdo->beginTransaction();
    
    // Update P1 dengan data P2
    $sql1 = "UPDATE squad_entries SET squad_status = ?, $col_top = ?, $col_left = ? WHERE id_entry = ?";
    $pdo->prepare($sql1)->execute([$status2, $top2, $left2, $id1]);

    // Update P2 dengan data P1
    $sql2 = "UPDATE squad_entries SET squad_status = ?, $col_top = ?, $col_left = ? WHERE id_entry = ?";
    $pdo->prepare($sql2)->execute([$status1, $top1, $left1, $id2]);

    $pdo->commit();
    echo "Swapped";
    exit;
}

// =======================================================
// MOVE TO SUBS (Kembalikan ke Subs Sidebar)
// =======================================================
if ($action == 'move_to_subs') {
    $id = $_POST['id_entry'];
    // Ubah jadi Subs, Hapus Koordinat Taktik (Opsional, atau biarkan tersimpan)
    $sql = "UPDATE squad_entries SET squad_status = 'Subs' WHERE id_entry = ?";
    $pdo->prepare($sql)->execute([$id]);
    echo "Moved to Subs";
    exit;
}

// =======================================================
// 4. GLOBAL FAVORITES MANAGEMENT
// =======================================================

// A. COPY TO FAVORITES (Dari Squad/Shortlist ke Global Fav)
if ($action == 'add_to_favorites') {
    $id_entry = $_POST['id_entry'];

    // Ambil data lengkap pemain dari squad_entries
    $sql = "SELECT p.full_name, p.nationality, pos.code, peak.peak_age_val, sq.* FROM squad_entries sq
            JOIN players p ON sq.id_player = p.id_player
            JOIN positions pos ON sq.id_tactic_position = pos.id_position
            LEFT JOIN peak_age_rules peak ON pos.id_peak_rule = peak.id_rule
            WHERE sq.id_entry = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_entry]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($player) {
        // Masukkan ke tabel global_favorites
        $sqlIns = "INSERT INTO global_favorites (
            full_name, pos_code, current_age, rating, potential, 
            price, current_club, nationality, registration_status, peak_age_val
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $pdo->prepare($sqlIns)->execute([
            $player['full_name'], $player['code'], $player['current_age'], 
            $player['rating'], $player['potential'], $player['price'], 
            $player['current_club'], $player['nationality'], 
            $player['registration_status'], $player['peak_age_val']
        ]);
    }
    // KEMBALI KE HALAMAN ASAL (AMAN)
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: $referer");
    exit;
}

// B. HAPUS DARI FAVORIT (Tombol Trash Kecil)
if ($action == 'delete_favorite') {
    $id_fav = $_POST['id_fav'];
    $pdo->prepare("DELETE FROM global_favorites WHERE id_fav = ?")->execute([$id_fav]);
    
    // PERBAIKAN: Gunakan Referer agar tidak 404
    $referer = $_SERVER['HTTP_REFERER'] ?? 'favorite.php';
    header("Location: $referer");
    exit;
}

// C. UPDATE POSISI FAVORIT (DRAG & DROP)
if ($action == 'save_position_fav') {
    $id = $_POST['id_fav']; // Ini ID FAV, bukan ID ENTRY
    $top = $_POST['top'];
    $left = $_POST['left'];
    
    $pdo->prepare("UPDATE global_favorites SET fav_top = ?, fav_left = ? WHERE id_fav = ?")
        ->execute([$top, $left, $id]);
    echo "Fav Position Saved";
    exit;
}

// D. REMOVE FROM PITCH (FAVORIT)
if ($action == 'remove_from_pitch_fav') {
    $id = $_POST['id_fav'];
    $pdo->prepare("UPDATE global_favorites SET fav_top = NULL, fav_left = NULL WHERE id_fav = ?")
        ->execute([$id]);
    exit;
}

// =======================================================
// 5. MANAJEMEN FAVORIT (EDIT / ADD / DELETE VIA MODAL)
// =======================================================

if ($action == 'save_player_fav') {
    $id_fav   = $_POST['id_fav'];
    $name     = $_POST['full_name'];
    $age      = $_POST['current_age'];
    $pos_code = $_POST['pos_code']; // String 'ST', 'GK'
    $rat      = $_POST['rating'];
    $pot      = $_POST['potential'];
    $price    = $_POST['price'];
    $club     = $_POST['current_club'];
    $nat      = $_POST['nationality'];
    $reg      = $_POST['registration_status'];

    // 1. Cari Peak Age Value berdasarkan Pos Code
    $stmtPeak = $pdo->prepare("
        SELECT peak.peak_age_val 
        FROM positions p
        JOIN peak_age_rules peak ON p.id_peak_rule = peak.id_rule
        WHERE p.code = ? LIMIT 1
    ");
    $stmtPeak->execute([$pos_code]);
    $peakVal = $stmtPeak->fetchColumn();
    if(!$peakVal) $peakVal = 27; // Default fallback

    if ($id_fav == 0) {
        // --- INSERT BARU ---
        $sql = "INSERT INTO global_favorites (
                    full_name, pos_code, current_age, rating, potential, 
                    price, current_club, nationality, registration_status, peak_age_val
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([
            $name, $pos_code, $age, $rat, $pot, 
            $price, $club, $nat, $reg, $peakVal
        ]);
    } else {
        // --- UPDATE EXISTING ---
        $sql = "UPDATE global_favorites SET 
                    full_name = ?, pos_code = ?, current_age = ?, rating = ?, potential = ?, 
                    price = ?, current_club = ?, nationality = ?, registration_status = ?, peak_age_val = ?
                WHERE id_fav = ?";
        $pdo->prepare($sql)->execute([
            $name, $pos_code, $age, $rat, $pot, 
            $price, $club, $nat, $reg, $peakVal, $id_fav
        ]);
    }
    
    // PERBAIKAN: Gunakan Referer agar tidak 404
    $referer = $_SERVER['HTTP_REFERER'] ?? 'favorite.php';
    header("Location: $referer");
    exit;
}

if ($action == 'delete_player_fav') {
    $id_fav = $_POST['id_fav'];
    $pdo->prepare("DELETE FROM global_favorites WHERE id_fav = ?")->execute([$id_fav]);
    
    // PERBAIKAN: Gunakan Referer agar tidak 404
    $referer = $_SERVER['HTTP_REFERER'] ?? 'favorite.php';
    header("Location: $referer");
    exit;
}

// =======================================================
// TABLE DRAG ACTIONS (MOVE & SWAP)
// =======================================================

// 1. PINDAH STATUS (MOVE KE TEMPAT KOSONG)
if ($action == 'move_player_status') {
    $id = $_POST['id_entry'];
    $target = $_POST['target_status'];
    
    // Cek Limit
    $limit = $LIMITS[$target] ?? 99;
    $currCount = $pdo->query("SELECT COUNT(*) FROM squad_entries WHERE id_season = {$_SESSION['curr_season']} AND squad_status = '$target'")->fetchColumn();
    
    if ($currCount >= $limit) {
        // Sebenarnya di UI baris kosong tidak akan muncul jika penuh, 
        // tapi validasi backend tetap perlu.
        exit; 
    }

    // Jika pindah ke Subs, koordinat lapangan biasanya dihapus atau dibiarkan (opsional).
    // Di sini kita biarkan koordinatnya (siapa tau balik ke pitch nanti).
    // Hanya update status.
    $sql = "UPDATE squad_entries SET squad_status = ? WHERE id_entry = ?";
    $pdo->prepare($sql)->execute([$target, $id]);
    exit;
}

// 2. TUKAR PEMAIN (SWAP ANTAR TABEL)
if ($action == 'swap_players_general') {
    $id1 = $_POST['id1'];
    $id2 = $_POST['id2'];

    // Ambil data kedua pemain
    $stmt = $pdo->prepare("SELECT id_entry, squad_status, t1_top, t1_left, t2_top, t2_left, t3_top, t3_left FROM squad_entries WHERE id_entry IN (?, ?)");
    $stmt->execute([$id1, $id2]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($players) < 2) exit;

    // Pastikan urutan array sesuai ID
    $p1 = ($players[0]['id_entry'] == $id1) ? $players[0] : $players[1];
    $p2 = ($players[0]['id_entry'] == $id2) ? $players[0] : $players[1];

    $pdo->beginTransaction();

    // TUKAR SEMUANYA (Status + Semua Koordinat Taktik)
    // Kenapa koordinat ditukar? 
    // Karena jika Striker Utama (1st Team) ditukar dengan Cadangan (Subs),
    // maka si Cadangan harus menempati posisi Striker di lapangan.
    
    // Update P1 pakai data P2
    $sql1 = "UPDATE squad_entries SET 
                squad_status = ?, 
                t1_top = ?, t1_left = ?, 
                t2_top = ?, t2_left = ?, 
                t3_top = ?, t3_left = ? 
             WHERE id_entry = ?";
             
    $pdo->prepare($sql1)->execute([
        $p2['squad_status'], 
        $p2['t1_top'], $p2['t1_left'], 
        $p2['t2_top'], $p2['t2_left'], 
        $p2['t3_top'], $p2['t3_left'], 
        $id1
    ]);

    // Update P2 pakai data P1
    $sql2 = "UPDATE squad_entries SET 
                squad_status = ?, 
                t1_top = ?, t1_left = ?, 
                t2_top = ?, t2_left = ?, 
                t3_top = ?, t3_left = ? 
             WHERE id_entry = ?";
             
    $pdo->prepare($sql2)->execute([
        $p1['squad_status'], 
        $p1['t1_top'], $p1['t1_left'], 
        $p1['t2_top'], $p1['t2_left'], 
        $p1['t3_top'], $p1['t3_left'], 
        $id2
    ]);

    $pdo->commit();
    exit;
}
    
?>