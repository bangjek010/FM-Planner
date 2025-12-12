<?php
session_start();
include 'db.php';
// 2. Cek apakah tabel 'teams' sudah ada di dalam database
try {
    $test = $pdo->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='teams'")->fetchColumn();
    if ($test == 0) {
        header("Location: install_db.php");
        exit;
    }
} catch (Exception $e) {
    header("Location: install_db.php");
    exit;
}
include 'header.php';


function getPosClass($code) {
    if ($code == 'GK') return 'pos-gk';
    if (in_array($code, ['CB', 'RB', 'LB'])) return 'pos-def';
    if (in_array($code, ['WB', 'WBR', 'WBL'])) return 'pos-wb';
    if (in_array($code, ['DM'])) return 'pos-dm';
    if (in_array($code, ['CM'])) return 'pos-mid';
    if (in_array($code, ['MR', 'ML'])) return 'pos-mid-wing';
    if (in_array($code, ['RW', 'LW'])) return 'pos-wing';
    if (in_array($code, ['AMC'])) return 'pos-amc';
    if (in_array($code, ['ST', 'CF', 'SS'])) return 'pos-att';
    return 'pos-mid';
}

$currentTeamId = $_SESSION['curr_team'] ?? null;
$currentSeasonId = $_SESSION['curr_season'] ?? null;

// INIT DATA
if (!$currentTeamId) {
    $allTeams = $pdo->query("SELECT * FROM teams WHERE id_user = 1")->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Ambil Data Tim & Season
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE id_team = ?");
    $stmt->execute([$currentTeamId]);
    $teamData = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM seasons WHERE id_season = ?");
    $stmt->execute([$currentSeasonId]);
    $seasonData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teamData || !$seasonData) {
        unset($_SESSION['curr_team']); unset($_SESSION['curr_season']);
        header("Location: index.php"); exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM seasons WHERE id_team = ? ORDER BY id_season DESC");
    $stmt->execute([$currentTeamId]);
    $seasonList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $allTeams = $pdo->query("SELECT * FROM teams WHERE id_user = 1")->fetchAll(PDO::FETCH_ASSOC);
    $positions = $pdo->query("SELECT * FROM positions ORDER BY id_position ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // FETCH ALL PLAYERS
    $sql = "SELECT p.full_name, pos.code AS pos_code, peak.ui_color_hex, peak.peak_age_val, sq.*
            FROM squad_entries sq
            JOIN players p ON sq.id_player = p.id_player
            JOIN positions pos ON sq.id_tactic_position = pos.id_position
            LEFT JOIN peak_age_rules peak ON pos.id_peak_rule = peak.id_rule
            WHERE sq.id_season = ? ORDER BY sq.id_tactic_position ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$currentSeasonId]);
    $allPlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $firstTeam = array_filter($allPlayers, fn($p) => $p['squad_status'] == '1st Team');
    $firstSubs = array_filter($allPlayers, fn($p) => $p['squad_status'] == 'Subs');
    $secondTeam = array_filter($allPlayers, fn($p) => $p['squad_status'] == '2nd Team');
    $secondSubs = array_filter($allPlayers, fn($p) => $p['squad_status'] == '2nd Team Subs');
    $pitchPlayers = array_values($firstTeam);
    $pitchPlayers2 = array_values($secondTeam);
}

// HELPER ICON REGISTRATION & AGE & PEAK & MAX (UPDATED)
// HELPER ICONS (UPDATED: NO OVERLAP, PRIORITY LOGIC)
// HELPER ICONS (UPDATED: CUMULATIVE / TAMPIL SEMUA)
function getPlayerIcons($status, $age, $peakVal, $rat, $pot) {
    $icons = '';

    // 1. REGISTRATION ICON (Paling Kiri)
    if ($status == 'HG Club') $icons .= '<i class="fas fa-home" title="HG Club" style="color:#5972cf; margin-left:4px; font-size:0.7rem;"></i>';
    elseif ($status == 'HG Nation') $icons .= '<i class="fas fa-flag" title="HG Nation" style="color:#5972cf; margin-left:4px; font-size:0.7rem;"></i>';
    elseif ($status == 'Non-EU') $icons .= '<i class="fas fa-globe" title="Non-EU" style="color:#d63031; margin-left:4px; font-size:0.7rem;"></i>';

    // 2. MAX POTENTIAL (Cek Independen)
    // Jika Rating sudah sama dengan Potential, tampilkan MAX (Hijau)
    if (abs($rat - $pot) < 0.01 && $rat > 0) {
        $icons .= '<span title="Max Potential Reached" style="background:#27ae60; color:white; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold; border:1px solid #2ecc71;">MAX</span>';
    }

    // 3. PEAK AGE (Cek Independen)
    // Jika Umur sama dengan Peak Age posisi tersebut, tampilkan PEAK (Emas)
    if ($age == $peakVal) {
        $icons .= '<span title="Peak Age" style="background:#f1c40f; color:black; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold; border:1px solid #d35400;">PEAK</span>';
    }

    // 4. KATEGORI UMUR (Cek Terpisah untuk Label Umur)
    // Logic: Cari yang paling muda/spesifik terlebih dahulu
    if ($age <= 18) {
        $icons .= '<span title="U18" style="background:#e17055; color:white; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold;">U18</span>';
    } 
    elseif ($age <= 20) {
        $icons .= '<span title="U20" style="background:#0984e3; color:white; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold;">U20</span>';
    } 
    elseif ($age <= 22) {
        $icons .= '<span title="U22" style="background:#6c5ce7; color:white; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold;">U22</span>';
    } 
    elseif ($age >= 36) { 
        $icons .= '<span title="Legend" style="background:#2d3436; color:#f1c40f; border:1px solid #f1c40f; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold;">LGD</span>';
    } 
    elseif ($age >= 32) { 
        $icons .= '<span title="Over 32" style="background:#d63031; color:white; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold;">32+</span>';
    }

    return $icons;
}

// RENDER TABLE WITH ICONS (UPDATED)
// RENDER TABLE WITH DRAG & DROP LOGIC (UPDATED)
function renderTableFixed($data, $limit, $statusGroup) { 
    // $statusGroup contoh: '1st Team', 'Subs', '2nd Team'
    
    $data = array_values($data);
    for ($i = 0; $i < $limit; $i++) {
        // Drop Handler Logic
        // dropTable(event, 'StatusTujuan', DataPemainTarget_atau_Null)
        
        if (isset($data[$i])) {
            $p = $data[$i];
            $jsonRaw = json_encode($p);
            if($jsonRaw === false) { $jsonRaw = '{}'; } 
            $json = htmlspecialchars($jsonRaw, ENT_QUOTES, 'UTF-8');
            
            $posColorClass = getPosClass($p['pos_code']);
            $ratDisp = number_format((float)$p['rating'], 2);
            $potDisp = number_format((float)$p['potential'], 2);
            
            $playerIcons = getPlayerIcons(
                $p['registration_status'] ?? 'None', 
                $p['current_age'], 
                $p['peak_age_val'], 
                (float)$p['rating'], 
                (float)$p['potential']
            );

            // Tambahkan ondragover & ondrop untuk SWAP
            echo "<tr draggable='true' 
                      ondragstart='dragStartTable(event, {$json})'
                      ondragover='allowDropTable(event)'
                      ondrop='dropTable(event, \"{$statusGroup}\", {$json})'
                      onclick='modalPlayer($json)' 
                      class='filled-row'>
                <td><span class='badge $posColorClass'>{$p['pos_code']}</span></td>
                <td class='t-left'>
                    <div style='display:flex; align-items:center; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;'>
                        <span>{$p['full_name']}</span>
                        {$playerIcons}
                    </div>
                </td>
                <td>{$p['current_age']}</td>
                <td>{$ratDisp}</td>
                <td>{$potDisp}</td>
            </tr>";
        } else {
            // Baris Kosong = Drop Zone untuk MOVE (Pindah saja)
            echo "<tr class='empty' 
                      ondragover='allowDropTable(event)'
                      ondrop='dropTable(event, \"{$statusGroup}\", null)'>
                    <td colspan='5'></td>
                  </tr>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FM Planner Pro</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>


<?php if (!$currentTeamId): ?>
    <div class="welcome-overlay">
        <div class="welcome-container">
            <!-- Header Section -->
            <div class="welcome-header">
                <h1><i class="fas fa-futbol"></i> FM PLANNER</h1>
                <p>Manage your squad, plan your glory.</p>
            </div>

            <!-- Content Grid: Split Select & Create -->
            <div class="welcome-grid">
                
                <!-- LEFT: SELECT TEAM -->
                <div class="panel-left">
                    <h2>Select Team</h2>
                    <div class="team-list-container">
                        <?php if (count($allTeams) > 0): ?>
                            <div class="team-list">
                                <?php foreach($allTeams as $t): ?>
                                    <form action="api.php" method="POST" class="team-item-form">
                                        <input type="hidden" name="action" value="select_team">
                                        <input type="hidden" name="id_team" value="<?php echo $t['id_team']; ?>">
                                        <button type="submit" class="team-btn">
                                            <span class="team-name"><?php echo $t['team_name']; ?></span>
                                            <i class="fas fa-chevron-right arrow-icon"></i>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>Please Create New Jurney</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- MIDDLE: DIVIDER -->
                <div class="panel-divider">
                    <span>OR</span>
                </div>

                <!-- RIGHT: CREATE TEAM -->
                <div class="panel-right">
                    <h2>Create New Journey</h2>
                    <form action="api.php" method="POST" class="create-form">
                        <input type="hidden" name="action" value="create_team">
                        
                        <div class="input-group">
                            <label><i class="fas fa-shield-alt"></i> Team Name</label>
                            <input type="text" name="team_name" placeholder="e.g. Manchester United" required autocomplete="off">
                        </div>

                        <div class="input-group">
                            <label><i class="fas fa-user-tie"></i> Manager Name</label>
                            <input type="text" name="manager_name" placeholder="e.g. Sir Alex" required autocomplete="off">
                        </div>

                        <button type="submit" class="btn-create">
                            START CAREER <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
    <?php else: ?>

    <div class="container">

    <div class="actions">
         <button class="btn-white" onclick="openModal('mCompare')"><i class="fas fa-balance-scale"></i> COMPARE</button>
         <button class="btn-white" onclick="openModal('mSettings')"><i class="fas fa-cog"></i> SETTINGS</button>
    </div>
        <!-- PITCH AREA -->
        <div class="pitch-wrap">
            <div class="pitch-tabs">
                <button class="tab-btn active" onclick="switchPitchView('viewTeam1', this)">1ST TEAM</button>
                <button class="tab-btn" onclick="switchPitchView('viewTeam2', this)">2ND TEAM</button>
            </div>

            <!-- VIEW 1 -->
            <div id="viewTeam1" class="pitch-container">
                <div class="window-bar">
                    <div class="dots-container">
                        <span class="dot active" onclick="switchTactic(1)"></span>
                        <span class="dot" onclick="switchTactic(2)"></span>
                        <span class="dot" onclick="switchTactic(3)"></span>
                    </div>
                    <div class="tactic-title-center"><span id="tacticLabel1">TACTIC 1 (1ST TEAM)</span></div>
                    <div style="width: 40px;"></div> 
                </div>
                <div class="pitch-row">
                    <div class="pitch" id="pitchArea1">
                        <div class="lines outline"></div><div class="lines center-line"></div><div class="lines center-circle"></div><div class="lines box-top"></div><div class="lines box-bottom"></div>
                        <?php 
                        $slots = [[90,50],[75,10],[75,32],[75,50],[75,68],[75,90],[60,10],[60,32],[60,50],[60,68],[60,90],[45,10],[45,32],[45,50],[45,68],[45,90],[30,10],[30,32],[30,50],[30,68],[30,90],[15,10],[15,32],[15,50],[15,68],[15,90]];
                        foreach($slots as $s) echo "<div class='slot' data-top='{$s[0]}' data-left='{$s[1]}' style='top:{$s[0]}%; left:{$s[1]}%;'></div>";
                        
                        foreach($pitchPlayers as $i => $p): 
                            if($i >= 11) break; 
                            $t1_style = ($p['t1_top']) ? "top:{$p['t1_top']}%; left:{$p['t1_left']}%;" : "display:none;";
                            $ratDisp = number_format((float)$p['rating'], 2);
                        ?>
                        <div class="card <?php echo $t1_style ? 'custom-pos' : ''; ?>" style="<?php echo $t1_style; ?>" draggable="true" ondragstart="dragStartCard(event)" data-origin="pitch" data-id="<?php echo $p['id_entry']; ?>" data-t1-top="<?php echo $p['t1_top']; ?>" data-t1-left="<?php echo $p['t1_left']; ?>" data-t2-top="<?php echo $p['t2_top']; ?>" data-t2-left="<?php echo $p['t2_left']; ?>" data-t3-top="<?php echo $p['t3_top']; ?>" data-t3-left="<?php echo $p['t3_left']; ?>">
                             <div class="card-header <?php echo getPosClass($p['pos_code']); ?>"><?php echo $p['pos_code']; ?></div>
                             <div class="c-name" title="<?php echo $p['full_name']; ?>"><?php echo $p['full_name']; ?></div>
                        <div class="c-stats-grid">
                            <div class="stat-col"><div class="stat-row"><span class="stat-label">Age</span><?php echo $p['current_age']; ?></div><div class="stat-row gold-text"><span class="stat-label">Peak</span><?php echo $p['peak_age_val']; ?></div></div>
                            <div class="stat-col"><div class="stat-row"><span class="stat-label">Rat</span><?php echo $p['rating']; ?></div><div class="stat-row gold-text"><span class="stat-label">Pot</span><?php echo $p['potential']; ?></div></div>
                        </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="subs-sidebar">
                        <div class="subs-header-title">SUBSTITUTES</div>
                        <div class="subs-grid" id="subsArea1">
                            <?php $subsList = array_values($firstSubs); for($i=0; $i<12; $i++): $p = $subsList[$i] ?? null; ?>
                                <div class="slot subs-slot">
                                    <?php if($p): $json = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); $ratDisp = number_format((float)$p['rating'], 2); ?>
                                        <div class="card" draggable="true" ondragstart="dragStartCard(event)" onclick='modalPlayer(<?php echo $json; ?>)' data-id="<?php echo $p['id_entry']; ?>" data-origin="subs">
                                            <div class="card-header <?php echo getPosClass($p['pos_code']); ?>"><?php echo $p['pos_code']; ?></div>
                                            <div class="c-name"><?php echo $p['full_name']; ?></div>
                                                                    <div class="c-stats-grid">
                            <div class="stat-col"><div class="stat-row"><span class="stat-label">Age</span><?php echo $p['current_age']; ?></div><div class="stat-row gold-text"><span class="stat-label">Peak</span><?php echo $p['peak_age_val']; ?></div></div>
                            <div class="stat-col"><div class="stat-row"><span class="stat-label">Rat</span><?php echo $p['rating']; ?></div><div class="stat-row gold-text"><span class="stat-label">Pot</span><?php echo $p['potential']; ?></div></div>
                        </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW 2 -->
            <div id="viewTeam2" class="pitch-container" style="display: none;">
                <div class="window-bar">
                    <div style="width: 40px;"></div> 
                    <div class="tactic-title-center"><span id="tacticLabel2">TACTIC 1 (2ND TEAM)</span></div>
                    <div class="dots-container">
                        <span class="dot active" onclick="switchTactic(1)"></span>
                        <span class="dot" onclick="switchTactic(2)"></span>
                        <span class="dot" onclick="switchTactic(3)"></span>
                    </div>
                </div>
                <div class="pitch-row">
                    <div class="pitch" id="pitchArea2"> 
                        <div class="lines outline"></div><div class="lines center-line"></div><div class="lines center-circle"></div><div class="lines box-top"></div><div class="lines box-bottom"></div>
                        <?php foreach($slots as $s) echo "<div class='slot' data-top='{$s[0]}' data-left='{$s[1]}' style='top:{$s[0]}%; left:{$s[1]}%;'></div>"; ?>
                        <?php foreach($pitchPlayers2 as $i => $p): 
                            if($i >= 11) break; 
                            $t1_style = ($p['t1_top']) ? "top:{$p['t1_top']}%; left:{$p['t1_left']}%;" : "display:none;";
                            $ratDisp = number_format((float)$p['rating'], 2);
                        ?>
                        <div class="card <?php echo $t1_style ? 'custom-pos' : ''; ?>" style="<?php echo $t1_style; ?>" draggable="true" ondragstart="dragStartCard(event)" data-origin="pitch" data-id="<?php echo $p['id_entry']; ?>" data-t1-top="<?php echo $p['t1_top']; ?>" data-t1-left="<?php echo $p['t1_left']; ?>" data-t2-top="<?php echo $p['t2_top']; ?>" data-t2-left="<?php echo $p['t2_left']; ?>" data-t3-top="<?php echo $p['t3_top']; ?>" data-t3-left="<?php echo $p['t3_left']; ?>">
                             <div class="card-header <?php echo getPosClass($p['pos_code']); ?>"><?php echo $p['pos_code']; ?></div>
                             <div class="c-name"><?php echo $p['full_name']; ?></div>
                                                     <div class="c-stats-grid">
                            <div class="stat-col"><div class="stat-row"><span class="stat-label">Age</span><?php echo $p['current_age']; ?></div><div class="stat-row gold-text"><span class="stat-label">Peak</span><?php echo $p['peak_age_val']; ?></div></div>
                            <div class="stat-col"><div class="stat-row"><span class="stat-label">Rat</span><?php echo $p['rating']; ?></div><div class="stat-row gold-text"><span class="stat-label">Pot</span><?php echo $p['potential']; ?></div></div>
                        </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="subs-sidebar">
                        <div class="subs-header-title">Subs (2nd)</div>
                        <div class="subs-grid" id="subsArea2">
                            <?php $subsList2 = array_values($secondSubs); for($i=0; $i<12; $i++): $p = $subsList2[$i] ?? null; ?>
                                <div class="slot subs-slot">
                                    <?php if($p): $json = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); $ratDisp = number_format((float)$p['rating'], 2); ?>
                                        <div class="card" draggable="true" ondragstart="dragStartCard(event)" onclick='modalPlayer(<?php echo $json; ?>)' data-id="<?php echo $p['id_entry']; ?>" data-origin="subs">
                                            <div class="card-header <?php echo getPosClass($p['pos_code']); ?>"><?php echo $p['pos_code']; ?></div>
                                            <div class="c-name"><?php echo $p['full_name']; ?></div>
                                                                    <div class="c-stats-grid">
                            <div class="stat-col"><div class="stat-row"><span class="stat-label">Age</span><?php echo $p['current_age']; ?></div><div class="stat-row gold-text"><span class="stat-label">Peak</span><?php echo $p['peak_age_val']; ?></div></div>
                            <div class="stat-col"><div class="stat-row"><span class="stat-label">Rat</span><?php echo $p['rating']; ?></div><div class="stat-row gold-text"><span class="stat-label">Pot</span><?php echo $p['potential']; ?></div></div>
                        </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DATA AREA -->
        <div class="data-wrap">
            <div class="team-info">
                <h1 onclick="openModal('mManageTeams')"><?php echo strtoupper($teamData['team_name']); ?> <i class="fas fa-caret-down"></i></h1>
                <div class="season-tag" onclick="openModal('mManageSeasons')">SEASON <?php echo $seasonData['year_label']; ?> <i class="fas fa-caret-down"></i></div>
            </div>
            <div class="toolbar">
                <button class="btn btn-green" onclick="modalPlayer(0)">+ ADD PLAYER</button>
            </div>
            <div class="tables">
                <div class="tbl-col">
                    <div class="th-main">1st Team (Max 11)</div>
                    <table>
                        <thead><tr><th width="10%">Pos</th><th>Name</th><th width="10%">Age</th><th width="10%">Rat</th><th width="10%">Pot</th></tr></thead>
                        <tbody><?php renderTableFixed($firstTeam, 11, '1st Team'); ?></tbody>
                    </table>
                    
                    <div class="th-main sub-header">2nd Team (Max 11)</div>
                    <table>
                        <thead><tr><th width="10%">Pos</th><th>Name</th><th width="10%">Age</th><th width="10%">Rat</th><th width="10%">Pot</th></tr></thead>
                        <tbody>
                            <?php renderTableFixed($secondTeam, 11, '2nd Team'); ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="tbl-col br-left">
                    <div class="th-main">1st Team Substitutes (Max 12)</div>
                    <table>
                        <thead><tr><th width="10%">Pos</th><th>Name</th><th width="10%">Age</th><th width="10%">Rat</th><th width="10%">Pot</th></tr></thead>
                        <tbody><?php renderTableFixed($firstSubs, 11, 'Subs'); ?></tbody>
                    </table>
                    
                    <div class="th-main sub-header">2nd Team Substitutes (Max 12)</div>
                    <table>
                        <thead><tr><th width="10%">Pos</th><th>Name</th><th width="10%">Age</th><th width="10%">Rat</th><th width="10%">Pot</th></tr></thead>
                        <tbody>
                            <?php renderTableFixed($secondSubs, 11, '2nd Team Subs'); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- MODAL ADD/EDIT PLAYER (UPDATED REGISTRATION) -->
    <div id="mPlayer" class="modal">
        <div class="m-content">
            <h3 id="mpTitle">Edit Player</h3>
            <form action="api.php" method="POST">
                <input type="hidden" name="action" value="save_player">
                <input type="hidden" name="id_entry" id="mpId">
                <input type="hidden" name="price" id="mpPrice">
                <input type="hidden" name="current_club" id="mpClub">

                <label>Name</label><input type="text" name="full_name" id="mpName" required>
                <div class="row">
                    <div><label>Age</label><input type="number" name="current_age" id="mpAge" required></div>
                    <div>
                        <label>Pos</label>
                        <select name="pos_code" id="mpPos">
                            <?php if(isset($positions)) foreach($positions as $pos) echo "<option value='{$pos['id_position']}'>{$pos['code']}</option>"; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div><label>Rat</label><input type="number" step="0.01" name="rating" id="mpRat"></div>
                    <div><label>Pot</label><input type="number" step="0.01" name="potential" id="mpPot"></div>
                </div>
                <!-- NEW REGISTRATION FIELD -->
                <label>Registration</label>
                <select name="registration_status" id="mpReg">
                    <option value="None">None</option>
                    <option value="HG Club">HG Club (3 Years in Club)</option>
                    <option value="HG Nation">HG Nation (3 Years in Nation)</option>
                    <option value="Non-EU">Non-EU (Foreign)</option>
                </select>

                <label>Status</label>
                <select name="squad_status" id="mpStatus">
                    <option value="1st Team">1st Team</option>
                    <option value="Subs">Subs</option>
                    <option value="2nd Team">2nd Team</option>
                    <option value="2nd Team Subs">2nd Team Subs</option>
                </select>
                <div class="m-footer">
                    <button type="submit" name="action" value="delete_player" class="btn btn-red" onclick="return" id="btnDel">Delete</button>
                    <button type="button" class="btn btn-dark" onclick="closeM('mPlayer')">Cancel</button>
                    <button type="submit" class="btn btn-green">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- NEW MODAL: COMPARISON TOOL -->
<div id="mCompare" class="modal">
        <div class="m-content m-large">
            <h3>PLAYER COMPARISON</h3>
            
            <div class="row" style="gap: 20px; margin-bottom:15px;">
                <div style="flex:1">
                    <label style="color:#7f8c8d; font-size:0.7rem; font-weight:bold;">SOURCE A</label>
                    <select id="filterA" onchange="updateCompareList('A')" style="width:100%; margin-bottom:5px; background:#f1f2f6;">
                        <option value="squad">Current Squad</option>
                        <option value="fav">Favorites</option>  <option value="shortlist">Shortlist</option>
                        <option value="out">Players Out</option>
                        <option value="all">All Players</option>
                    </select>
                    
                    <label>PLAYER A</label>
                    <select id="compA" onchange="runCompare()" style="width:100%">
                        <option value="">Select Player...</option>
                    </select>
                </div>
                
                <div style="flex:1">
                    <label style="color:#7f8c8d; font-size:0.7rem; font-weight:bold;">SOURCE B</label>
                    <select id="filterB" onchange="updateCompareList('B')" style="width:100%; margin-bottom:5px; background:#f1f2f6;">
                        <option value="fav" selected>Favorites</option> <option value="shortlist">Shortlist</option>
                        <option value="squad">Current Squad</option>
                        <option value="out">Players Out</option>
                        <option value="all">All Players</option>
                    </select>

                    <label>PLAYER B</label>
                    <select id="compB" onchange="runCompare()" style="width:100%">
                        <option value="">Select Player...</option>
                    </select>
                </div>
            </div>
            
            <table class="comp-table" style="width:100%; border-collapse:collapse; text-align:center;">
                <thead>
                    <tr style="background:#b2bec3;">
                        <th id="nmA" width="35%">-</th>
                        <th width="30%">Attribute</th>
                        <th id="nmB" width="35%">-</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td id="ageA">0</td><td><b>AGE</b></td><td id="ageB">0</td></tr>
                    <tr><td id="ratA">0</td><td><b>RATING</b></td><td id="ratB">0</td></tr>
                    <tr><td id="potA">0</td><td><b>POTENTIAL</b></td><td id="potB">0</td></tr>
                    <tr><td id="priA">-</td><td><b>PRICE</b></td><td id="priB">-</td></tr>
                    <tr><td id="staA">-</td><td><b>STATUS</b></td><td id="staB">-</td></tr>
                </tbody>
            </table>
            
            <button class="btn-dark" onclick="closeM('mCompare')" style="width:100%; margin-top:15px;">Close</button>
        </div>
    </div>

    <!-- MODALS LAINNYA -->
    <div id="mManageTeams" class="modal">
        <div class="m-content m-large">
            <h3>MANAGE TEAMS</h3>
            <div class="list-group">
                <?php foreach($allTeams as $t): ?>
                <div class="list-item <?php echo ($t['id_team'] == $currentTeamId) ? 'active-item' : ''; ?>">
                    <form action="api.php" method="POST" style="flex:1">
                        <input type="hidden" name="action" value="select_team">
                        <input type="hidden" name="id_team" value="<?php echo $t['id_team']; ?>">
                        <button type="submit" class="btn-text"><?php echo $t['team_name']; ?></button>
                    </form>
                    <form action="api.php" method="POST" onsubmit="return">
                        <input type="hidden" name="action" value="delete_team">
                        <input type="hidden" name="id_team" value="<?php echo $t['id_team']; ?>">
                        <button type="submit" class="btn-icon-del"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <hr><h4>Create New Team</h4>
            <form action="api.php" method="POST" class="row">
                <input type="hidden" name="action" value="create_team">
                <input type="text" name="team_name" placeholder="Name" required style="flex:2">
                <input type="text" name="manager_name" placeholder="Manager" required style="flex:1">
                <button type="submit" class="btn-green">Add</button>
            </form>
            <button class="btn-dark" onclick="closeM('mManageTeams')" style="margin-top:10px; width:100%">Close</button>
        </div>
    </div>

    <div id="mManageSeasons" class="modal">
        <div class="m-content m-large">
            <h3>MANAGE SEASONS</h3>
            <div class="list-group">
                <?php if(isset($seasonList)) foreach($seasonList as $s): ?>
                <div class="list-item <?php echo ($s['id_season'] == $currentSeasonId) ? 'active-item' : ''; ?>">
                    <form action="api.php" method="POST" style="flex:1">
                        <input type="hidden" name="action" value="select_season">
                        <input type="hidden" name="id_season" value="<?php echo $s['id_season']; ?>">
                        <button type="submit" class="btn-text">Season <?php echo $s['year_label']; ?></button>
                    </form>
                    <form action="api.php" method="POST" onsubmit="return">
                        <input type="hidden" name="action" value="delete_season">
                        <input type="hidden" name="id_season" value="<?php echo $s['id_season']; ?>">
                        <button type="submit" class="btn-icon-del"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <hr><h4>Add New Season</h4>
            <form action="api.php" method="POST" class="row">
                <input type="hidden" name="action" value="create_season">
                <input type="text" name="year_label" placeholder="e.g. 2025/2026" required style="flex:1">
                <button type="submit" class="btn-green">Add</button>
            </form>
            <button class="btn-dark" onclick="closeM('mManageSeasons')" style="margin-top:10px; width:100%">Close</button>
        </div>
    </div>

    <div id="mSettings" class="modal">
        <div class="m-content">
            <h3>THEME</h3>
            <div class="setting-group">
                <div class="row-center"><label>Main</label><input type="color" id="cp_bg_main" oninput="updateTheme('--bg-main', this.value)"></div>
                <div class="row-center"><label>Pitch 1</label><input type="color" id="cp_pitch_dark" oninput="updateTheme('--pitch-dark', this.value)"></div>
                <div class="row-center"><label>Pitch 2</label><input type="color" id="cp_pitch_light" oninput="updateTheme('--pitch-light', this.value)"></div>
            </div>
            <div class="m-footer">
                <button class="btn-red" onclick="resetTheme()">Reset</button>
                <button class="btn-green" onclick="closeM('mSettings')">Close</button>
            </div>
        </div>
    </div>

    <!-- DATA PEMAIN UNTUK JS (COMPARE) -->
<script>
        <?php 
        // 1. Ambil Data Squad (Sudah ada di $allPlayers dari logika di atas)
        // Pastikan $allPlayers sudah terisi dari logic PHP di bagian atas file.
        
        // 2. Ambil Data Favorites
        // Kita perlu JOIN ke tabel positions agar mendapatkan 'id_tactic_position' untuk sorting
        $sqlFav = "SELECT f.*, 
                          f.id_fav AS id_origin, 
                          p.id_position AS id_tactic_position 
                   FROM global_favorites f
                   LEFT JOIN positions p ON f.pos_code = p.code";
        $favPlayers = $pdo->query($sqlFav)->fetchAll(PDO::FETCH_ASSOC);

        // 3. Normalisasi Data Favorites agar strukturnya sama dengan Squad
        foreach($favPlayers as &$fp) {
            $fp['squad_status'] = 'Favorite'; // Set status manual
            // Beri prefix ID agar tidak bentrok dengan ID pemain Squad saat dipilih
            $fp['id_entry'] = 'fav_' . $fp['id_origin']; 
            // Pastikan field lain ada (untuk mencegah error undefined di JS)
            $fp['full_name'] = $fp['full_name']; 
            $fp['current_age'] = $fp['current_age'];
            // Rating & Potential sudah ada
        }

        // 4. Gabungkan Kedua Array
        // Jika $allPlayers kosong (misal baru buka app), inisialisasi array kosong
        $squadData = isset($allPlayers) ? $allPlayers : [];
        $mergedData = array_merge($squadData, $favPlayers);
        ?>

        // Kirim Data Gabungan ke JS
        const ALL_PLAYERS_DATA = <?php echo json_encode($mergedData); ?>;
    </script>
    
    <script src="script.js?v=<?php echo time(); ?>"></script>
    <script>
        currentTactic = 1;
        document.addEventListener("DOMContentLoaded", function() {
            if(typeof switchTactic === 'function') switchTactic(1); 
            // Init Compare Dropdowns
            initCompare();
        });
    </script>
</body>
</html>