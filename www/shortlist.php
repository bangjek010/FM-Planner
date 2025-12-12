<?php
// 1. LOGIKA UTAMA & REDIRECT
if (!file_exists('database.sqlite')) {
    header("Location: install_db.php");
    exit;
}
session_start();
include 'db.php';

$currentTeamId = $_SESSION['curr_team'] ?? null;
$currentSeasonId = $_SESSION['curr_season'] ?? null;

if (!$currentTeamId) { 
    header("Location: index.php"); 
    exit; 
}

$teamData = $pdo->query("SELECT * FROM teams WHERE id_team = $currentTeamId")->fetch(PDO::FETCH_ASSOC);
$seasonData = $pdo->query("SELECT * FROM seasons WHERE id_season = $currentSeasonId")->fetch(PDO::FETCH_ASSOC);

if (!$teamData || !$seasonData) {
    unset($_SESSION['curr_team']);
    unset($_SESSION['curr_season']);
    header("Location: index.php");
    exit;
}

// 2. INCLUDE HEADER
include 'header.php';

// --- DATA ---
$positions = $pdo->query("SELECT * FROM positions ORDER BY id_position ASC")->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT p.full_name, p.nationality, pos.code AS pos_code, peak.ui_color_hex, peak.peak_age_val, sq.*
        FROM squad_entries sq
        JOIN players p ON sq.id_player = p.id_player
        JOIN positions pos ON sq.id_tactic_position = pos.id_position
        LEFT JOIN peak_age_rules peak ON pos.id_peak_rule = peak.id_rule
        WHERE sq.id_season = $currentSeasonId ORDER BY sq.id_tactic_position ASC";
$allPlayers = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Filter Players
$pitchPlayers = array_filter($allPlayers, fn($p) => $p['squad_status'] == 'Shortlist');
$playersOut   = array_filter($allPlayers, fn($p) => $p['squad_status'] == 'Players Out');
$shortlist    = array_filter($allPlayers, fn($p) => $p['squad_status'] == 'Shortlist');

$currentSquad = array_filter($allPlayers, function($p) {
    return in_array($p['squad_status'], ['1st Team', '2nd Team', 'Subs', '2nd Team Subs']);
});

// Helpers
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

// HELPER ICONS (SAMA SEPERTI INDEX.PHP)
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
    elseif ($age >= 40) { 
        $icons .= '<span title="Legend" style="background:#2d3436; color:#f1c40f; border:1px solid #f1c40f; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold;">LGD</span>';
    } 
    elseif ($age >= 32) { 
        $icons .= '<span title="Over 32" style="background:#d63031; color:white; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold;">32+</span>';
    }

    return $icons;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FM Planner - Shortlist</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>



    <div class="container">


        <div class="pitch-wrap">
            <div class="window-bar">
                <div class="tactic-title" style="margin-left: 10px;">DRAFT / SIMULATION VIEW</div>
            </div>
            
            <div class="pitch" id="pitchArea">
                <div class="lines outline"></div><div class="lines center-line"></div><div class="lines center-circle"></div><div class="lines box-top"></div><div class="lines box-bottom"></div>
                
                <?php 
                        $slots = [[90,50],[75,10],[75,32],[75,50],[75,68],[75,90],[60,10],[60,32],[60,50],[60,68],[60,90],[45,10],[45,32],[45,50],[45,68],[45,90],[30,10],[30,32],[30,50],[30,68],[30,90],[15,10],[15,32],[15,50],[15,68],[15,90]];
                        foreach($slots as $s) echo "<div class='slot' data-top='{$s[0]}' data-left='{$s[1]}' style='top:{$s[0]}%; left:{$s[1]}%;'></div>";               
                foreach($pitchPlayers as $p): 
                    if (empty($p['sl_top'])) continue;
                    $top = $p['sl_top'];
                    $left = $p['sl_left'];
                    $posColor = $p['ui_color_hex'] ?? '#333';
                    
                    $ratDisp = number_format((float)$p['rating'], 2);
                    $potDisp = number_format((float)$p['potential'], 2);
                ?>
                <div class="card custom-pos" 
                     style="top:<?php echo $top; ?>%; left:<?php echo $left; ?>%;" 
                     draggable="true" ondragstart="dragStartCard(event)"
                     data-id="<?php echo $p['id_entry']; ?>"
                     data-sl-top="<?php echo $top; ?>" 
                     data-sl-left="<?php echo $left; ?>">
                     
                    <div class="card-header" style="background: <?php echo $posColor; ?>;"><?php echo $p['pos_code']; ?></div>
                    <div class="c-name" title="<?php echo $p['full_name']; ?>"><?php echo $p['full_name']; ?></div>
                    <div class="c-stats-grid">
                        <div class="stat-col"><div class="stat-row"><span class="stat-label">Age</span><?php echo $p['current_age']; ?></div><div class="stat-row gold-text"><span class="stat-label">Peak</span><?php echo $p['peak_age_val']; ?></div></div>
                        <div class="stat-col"><div class="stat-row"><span class="stat-label">Rat</span><?php echo $ratDisp; ?></div><div class="stat-row gold-text"><span class="stat-label">Pot</span><?php echo $potDisp; ?></div></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="data-wrap">
            <div class="team-info">
                <h1><?php echo strtoupper($teamData['team_name']); ?></h1>
                <div class="season-tag">SEASON <?php echo $seasonData['year_label']; ?></div>
            </div>

            <!-- TABLE PLAYERS OUT -->
            <div class="tables-group">
                <button class="btn btn-green" onclick="openModal('mSelectOut')">+ SELECT PLAYER TO SELL</button>
                <button class="btn btn-green" onclick="modalPlayer(0, 'Players Out')">+ ADD PLAYER TO SELL</button>
                <div class="th-main sub-header" style="margin-top:5px">PLAYERS OUT</div>
                <table>
                    <thead><tr><th width="8%">Pos</th><th>Name</th><th width="8%">Age</th><th width="8%">Rat</th><th width="8%">Pot</th><th>Price</th></tr></thead>
                    <tbody>
                        <?php $data = array_values($playersOut); for($i=0; $i<6; $i++): if(isset($data[$i])): $p=$data[$i]; 
                            $json = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); 
                            $ratDisp = number_format((float)$p['rating'], 2);
                            $potDisp = number_format((float)$p['potential'], 2);
                            $playerIcons = getPlayerIcons($p['registration_status'] ?? 'None', $p['current_age'], $p['peak_age_val'], (float)$p['rating'], (float)$p['potential']);
                        ?>
                        <tr onclick='modalPlayer(<?php echo $json; ?>)'>
                            <td><span class='badge <?php echo getPosClass($p['pos_code']); ?>'><?php echo $p['pos_code']; ?></span></td>
                            <td class='t-left'>
                                <div style='display:flex; align-items:center; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;'>
                                    <span><?php echo $p['full_name']; ?></span>
                                    <?php echo $playerIcons; ?>
                                </div>
                            </td>
                            <td><?php echo $p['current_age']; ?></td>
                            <td><?php echo $ratDisp; ?></td>
                            <td><?php echo $potDisp; ?></td>
                            <td style="font-weight:bold; color:#c0392b"><?php echo $p['price']; ?></td>
                        </tr>
                        <?php else: echo "<tr class='empty'><td colspan='6'></td></tr>"; endif; endfor; ?>
                    </tbody>
                </table>
            </div>
            <br>
            <!-- TABLE SHORTLIST -->
            <div class="tables-group">
                <button class="btn btn-green" onclick="modalPlayer(0, 'Shortlist')">+ ADD PLAYER</button>
                <div class="th-main sub-header" style="margin-top:5px">SHORTLIST</div>
                <table>
                    <thead><tr><th width="8%">Pos</th><th>Name</th><th width="8%">Age</th><th width="8%">Rat</th><th width="8%">Pot</th><th>Club</th><th>Price</th><th>Nat</th></tr></thead>
                    <tbody>
                        <?php $data = array_values($shortlist); for($i=0; $i<10; $i++): if(isset($data[$i])): $p=$data[$i]; 
                            $json = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
                            $ratDisp = number_format((float)$p['rating'], 2);
                            $potDisp = number_format((float)$p['potential'], 2);
                            $playerIcons = getPlayerIcons($p['registration_status'] ?? 'None', $p['current_age'], $p['peak_age_val'], (float)$p['rating'], (float)$p['potential']);
                        ?>
                        <tr onclick='modalPlayer(<?php echo $json; ?>)' draggable="true" ondragstart="dragStartTable(event, <?php echo $json; ?>)">
                            <td><span class='badge <?php echo getPosClass($p['pos_code']); ?>'><?php echo $p['pos_code']; ?></span></td>
                            <td class='t-left'>
                                <div style='display:flex; align-items:center; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;'>
                                    <span><?php echo $p['full_name']; ?></span>
                                    <?php echo $playerIcons; ?>
                                </div>
                            </td>
                            <td><?php echo $p['current_age']; ?></td>
                            <td><?php echo $ratDisp; ?></td>
                            <td><?php echo $potDisp; ?></td>
                            <td><?php echo $p['current_club']; ?></td>
                            <td style="font-weight:bold; color:#27ae60"><?php echo $p['price']; ?></td>
                            <td><?php echo $p['nationality']; ?></td>
                        </tr>
                        <?php else: echo "<tr class='empty'><td colspan='8'></td></tr>"; endif; endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL SELECT PLAYER TO SELL -->
<!-- MODAL SELECT PLAYER TO SELL (SORTED: POSISI -> RATING TINGGI) -->
    <div id="mSelectOut" class="modal">
        <div class="m-content">
            <h3>Move to Players Out</h3>
            <form action="api.php" method="POST">
                <input type="hidden" name="action" value="move_to_players_out">
                
                <label>Select Player from Squad</label>
                <select name="id_entry" required style="width: 100%; padding: 8px; margin-bottom: 15px; font-family: 'Roboto Condensed', sans-serif;">
                    <option value="" disabled selected>-- Choose Player --</option>
                    <?php 
                    // LOGIKA SORTING BARU
                    usort($currentSquad, function($a, $b) {
                        // 1. Cek Posisi (Ascending: ID 1, 2, 3...)
                        if ($a['id_tactic_position'] != $b['id_tactic_position']) {
                            return $a['id_tactic_position'] - $b['id_tactic_position'];
                        }
                        
                        // 2. Cek Rating (Descending: Tinggi ke Rendah)
                        // Jika Rating B lebih besar dari A, maka B didahulukan
                        if ($a['rating'] != $b['rating']) {
                            return ($b['rating'] > $a['rating']) ? 1 : -1;
                        }

                        // 3. Jika Rating sama, urutkan Nama (A-Z)
                        return strcmp($a['full_name'], $b['full_name']);
                    });
                    
                    // TAMPILKAN DATA
                    foreach($currentSquad as $p): 
                        $ratTxt = number_format((float)$p['rating'], 2);
                        $potTxt = number_format((float)$p['potential'], 2);
                        
                        // Teks Dropdown
                        $displayText = "{$p['pos_code']} - {$p['full_name']} (Rat: {$ratTxt} | Pot: {$potTxt})";
                    ?>
                        <option value="<?php echo $p['id_entry']; ?>">
                            <?php echo $displayText; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="row">
                    <div>
                        <label>Asking Price / Fee (£)</label>
                        <input type="text" name="price" placeholder="e.g. £45M" style="width: 100%; box-sizing: border-box;">
                    </div>
                </div>

                <div class="m-footer" style="margin-top: 20px;">
                    <button type="button" class="btn btn-dark" onclick="closeM('mSelectOut')">Cancel</button>
                    <button type="submit" class="btn btn-green">Move to Out</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT PLAYER -->
    <div id="mPlayer" class="modal">
        <div class="m-content">
            <h3 id="mpTitle">Edit Player</h3>
            <form action="api.php" method="POST">
                <input type="hidden" name="action" value="save_player">
                <input type="hidden" name="id_entry" id="mpId">
                
                <label>Name</label>
                <input type="text" name="full_name" id="mpName" required>
                
                <div class="row">
                    <div><label>Age</label><input type="number" name="current_age" id="mpAge" required></div>
                    <div>
                        <label>Pos</label>
                        <select name="pos_code" id="mpPos">
                            <?php foreach($positions as $pos) echo "<option value='{$pos['id_position']}'>{$pos['code']}</option>"; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div><label>Rat</label><input type="number" step="0.01" name="rating" id="mpRat"></div>
                    <div><label>Pot</label><input type="number" step="0.01" name="potential" id="mpPot"></div>
                </div>

                <div class="row">
                    <div><label>Price</label><input type="text" name="price" id="mpPrice" placeholder="e.g. £50M"></div>
                    <div><label>Club</label><input type="text" name="current_club" id="mpClub" placeholder="Current Club"></div>
                </div>

                <label>Registration</label>
                <select name="registration_status" id="mpReg">
                    <option value="None">None</option>
                    <option value="HG Club">HG Club</option>
                    <option value="HG Nation">HG Nation</option>
                    <option value="Non-EU">Non-EU</option>
                </select>

                <label>Status</label>
                <select name="squad_status" id="mpStatus">
                    <option value="Shortlist">Shortlist</option>
                    <option value="Players Out">Players Out</option>
                    <option value="1st Team">1st Team</option>
                    <option value="2nd Team">2nd Team</option>
                    <option value="Subs">Subs</option>
                </select>

                <div class="m-footer">
                    <button type="submit" name="action" value="delete_player" class="btn btn-red" id="btnDel" onclick="return">Delete</button>
                    <button type="submit" name="action" value="return_to_squad" class="btn btn-blue" id="btnReturn" style="display:none;">Return to Squad</button>
                    <button type="button" class="btn btn-dark" onclick="closeM('mPlayer')">Cancel</button>
                    <button type="submit" class="btn btn-green">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script loaded with version -->
    <script src="script.js?v=<?php echo time(); ?>"></script>
    <script>
        currentTactic = 'SL'; 
        function switchTactic(id) { console.log("Shortlist Mode Active"); }

        function modalPlayer(data, defaultStatus = 'Shortlist') {
            const modal = document.getElementById('mPlayer');
            const btnDel = document.getElementById('btnDel');
            const btnReturn = document.getElementById('btnReturn');

            if(data === 0) { 
                document.getElementById('mpTitle').innerText = "Add " + defaultStatus;
                document.getElementById('mpId').value = 0;
                document.getElementById('mpName').value = "";
                document.getElementById('mpAge').value = 18;
                document.getElementById('mpRat').value = 100;
                document.getElementById('mpPot').value = 150;
                document.getElementById('mpPrice').value = "";
                document.getElementById('mpClub').value = "";
                document.getElementById('mpStatus').value = defaultStatus;
                
                btnDel.style.display = 'none';
                btnReturn.style.display = 'none';
            } else { 
                document.getElementById('mpTitle').innerText = "Edit Player";
                document.getElementById('mpId').value = data.id_entry;
                document.getElementById('mpName').value = data.full_name;
                document.getElementById('mpAge').value = data.current_age;
                document.getElementById('mpPos').value = data.id_tactic_position;
                document.getElementById('mpRat').value = data.rating;
                document.getElementById('mpPot').value = data.potential;
                document.getElementById('mpPrice').value = data.price || "";
                document.getElementById('mpClub').value = data.current_club || "";
                document.getElementById('mpStatus').value = data.squad_status;
                if(document.getElementById('mpReg')) document.getElementById('mpReg').value = data.registration_status || 'None';

                if (data.squad_status === 'Players Out') {
                    btnDel.style.display = 'none';
                    btnReturn.style.display = 'block';
                } else {
                    btnDel.style.display = 'block';
                    btnReturn.style.display = 'none';
                }
            }
            modal.style.display = 'flex';
        }
    </script>
</body>
</html>