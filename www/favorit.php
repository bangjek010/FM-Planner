<?php
if (!file_exists('database.sqlite')) { header("Location: install_db.php"); exit; }
session_start();
include 'db.php';
include 'header.php';

// AMBIL DATA POSISI (Untuk Dropdown di Modal)
$positions = $pdo->query("SELECT * FROM positions ORDER BY id_position ASC")->fetchAll(PDO::FETCH_ASSOC);

// AMBIL SEMUA DATA FAVORIT
$allPlayers = $pdo->query("SELECT * FROM global_favorites ORDER BY rating DESC")->fetchAll(PDO::FETCH_ASSOC);

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

function getPlayerIcons($status, $age, $peakVal, $rat, $pot) {
    $icons = '';
    if ($status == 'HG Club') $icons .= '<i class="fas fa-home" title="HG Club" style="color:#5972cf; margin-left:4px; font-size:0.7rem;"></i>';
    elseif ($status == 'HG Nation') $icons .= '<i class="fas fa-flag" title="HG Nation" style="color:#5972cf; margin-left:4px; font-size:0.7rem;"></i>';
    elseif ($status == 'Non-EU') $icons .= '<i class="fas fa-globe" title="Non-EU" style="color:#d63031; margin-left:4px; font-size:0.7rem;"></i>';

    if (abs($rat - $pot) < 0.01 && $rat > 0) {
        $icons .= '<span title="Max Potential" style="background:#27ae60; color:white; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold;">MAX</span>';
    }
    if ($age == $peakVal) {
        $icons .= '<span title="Peak Age" style="background:#f1c40f; color:black; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold; border:1px solid #d35400;">PEAK</span>';
    }
    if ($age <= 18) $icons .= '<span title="U18" style="background:#e17055; color:white; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold;">U18</span>';
    elseif ($age <= 20) $icons .= '<span title="U20" style="background:#0984e3; color:white; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold;">U20</span>';
    elseif ($age <= 22) $icons .= '<span title="U22" style="background:#6c5ce7; color:white; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold;">U22</span>';
    elseif ($age >= 40) $icons .= '<span title="Legend" style="background:#2d3436; color:#f1c40f; border:1px solid #f1c40f; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold;">LGD</span>';
    elseif ($age >= 32) $icons .= '<span title="Over 32" style="background:#d63031; color:white; font-size:0.6rem; padding:1px 3px; border-radius:3px; margin-left:4px; font-weight:bold;">32+</span>';

    return $icons;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FM Planner - Favorites</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="container">

    <div class="pitch-wrap">
        <div class="window-bar">
            <div class="tactic-title" style="margin-left: 10px;">FAVORITES DREAM TEAM</div>
        </div>
        
        <div class="pitch" id="pitchArea">
            <div class="lines outline"></div><div class="lines center-line"></div><div class="lines center-circle"></div><div class="lines box-top"></div><div class="lines box-bottom"></div>
            
            <?php 
                        $slots = [[90,50],[75,10],[75,32],[75,50],[75,68],[75,90],[60,10],[60,32],[60,50],[60,68],[60,90],[45,10],[45,32],[45,50],[45,68],[45,90],[30,10],[30,32],[30,50],[30,68],[30,90],[15,10],[15,32],[15,50],[15,68],[15,90]];
                        foreach($slots as $s) echo "<div class='slot' data-top='{$s[0]}' data-left='{$s[1]}' style='top:{$s[0]}%; left:{$s[1]}%;'></div>";           
            foreach($allPlayers as $p): 
                if (empty($p['fav_top'])) continue;
                $top = $p['fav_top'];
                $left = $p['fav_left'];
                
                // Card Props
                $ratDisp = number_format((float)$p['rating'], 0);
                $cardIcons = getPlayerIcons($p['registration_status'], $p['current_age'], $p['peak_age_val'], $p['rating'], $p['potential']);
            ?>
            <div class="card custom-pos" 
                 style="top:<?php echo $top; ?>%; left:<?php echo $left; ?>%;" 
                 draggable="true" ondragstart="dragStartCard(event)"
                 data-origin="pitch"
                 data-id="<?php echo $p['id_fav']; ?>" 
                 data-tactic="FAV" 
                 data-fav-top="<?php echo $top; ?>" 
                 data-fav-left="<?php echo $left; ?>">
                 
                <div class="card-header <?php echo getPosClass($p['pos_code']); ?>"><?php echo $p['pos_code']; ?></div>
                <div class="c-name" title="<?php echo $p['full_name']; ?>">
                    <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; width:100%;"><?php echo $p['full_name']; ?></div>

                </div>
                        <div class="c-stats-grid">
                            <div class="stat-col"><div class="stat-row"><span class="stat-label">Age</span><?php echo $p['current_age']; ?></div><div class="stat-row gold-text"><span class="stat-label">Peak</span><?php echo $p['peak_age_val']; ?></div></div>
                            <div class="stat-col"><div class="stat-row"><span class="stat-label">Rat</span><?php echo $p['rating']; ?></div><div class="stat-row gold-text"><span class="stat-label">Pot</span><?php echo $p['potential']; ?></div></div>
                        </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="data-wrap">
        <div class="team-info">
            <h1>GLOBAL FAVORITES</h1>
            <div class="season-tag">ALL DATABASES</div>
        </div>

        <div class="tables-group">
            <button class="btn btn-green" onclick="modalPlayerFav(0)">+ ADD FAVORITE PLAYER</button>
            
            <div class="th-main sub-header" style="margin-top:5px">SAVED PLAYERS</div>
            <table>
                <thead><tr><th width="8%">Pos</th><th>Name</th><th width="8%">Age</th><th width="8%">Rat</th><th width="8%">Pot</th><th>Club</th><th>Price</th><th>Nat</th><th width="5%"></th></tr></thead>
                <tbody>
                    <?php foreach($allPlayers as $p): 
                        // JSON Data (Untuk Dragging & Editing)
                        // Hack: id_entry kita isi id_fav agar script.js bisa drag
                        $p['id_entry'] = $p['id_fav']; 
                        $json = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
                        
                        $ratDisp = number_format((float)$p['rating'], 2);
                        $potDisp = number_format((float)$p['potential'], 2);
                        $icons = getPlayerIcons($p['registration_status'], $p['current_age'], $p['peak_age_val'], $p['rating'], $p['potential']);
                        $posColor = getPosClass($p['pos_code']);
                    ?>
                    <tr draggable="true" ondragstart="dragStartTable(event, <?php echo $json; ?>)" onclick='modalPlayerFav(<?php echo $json; ?>)'>
                        <td><span class='badge <?php echo $posColor; ?>'><?php echo $p['pos_code']; ?></span></td>
                        <td class='t-left'>
                            <div style='display:flex; align-items:center; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;'>
                                <span><?php echo $p['full_name']; ?></span>
                                <?php echo $icons; ?>
                            </div>
                        </td>
                        <td><?php echo $p['current_age']; ?></td>
                        <td><?php echo $ratDisp; ?></td>
                        <td><?php echo $potDisp; ?></td>
                        <td><?php echo $p['current_club']; ?></td>
                        <td style="font-weight:bold; color:#27ae60"><?php echo $p['price']; ?></td>
                        <td><?php echo $p['nationality']; ?></td>
                        <td>
                            <form action="api.php" method="POST" onsubmit="return" onclick="event.stopPropagation();">
                                <input type="hidden" name="action" value="delete_player_fav">
                                <input type="hidden" name="id_fav" value="<?php echo $p['id_fav']; ?>">
                                <button type="submit" style="background:none;border:none;cursor:pointer;color:#e74c3c;"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($allPlayers)) echo "<tr><td colspan='9' style='text-align:center; padding:20px;'>No favorites saved yet. Add manually or from Squad/Shortlist.</td></tr>"; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="mFavPlayer" class="modal">
    <div class="m-content">
        <h3 id="mfTitle">Edit Favorite</h3>
        <form action="api.php" method="POST">
            <input type="hidden" name="action" value="save_player_fav">
            <input type="hidden" name="id_fav" id="mfId">
            
            <label>Name</label>
            <input type="text" name="full_name" id="mfName" required>
            
            <div class="row">
                <div><label>Age</label><input type="number" name="current_age" id="mfAge" required></div>
                <div>
                    <label>Pos</label>
                    <select name="pos_code" id="mfPos">
                        <?php foreach($positions as $pos) echo "<option value='{$pos['code']}'>{$pos['code']}</option>"; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div><label>Rat</label><input type="number" step="0.01" name="rating" id="mfRat"></div>
                <div><label>Pot</label><input type="number" step="0.01" name="potential" id="mfPot"></div>
            </div>

            <div class="row">
                <div><label>Price</label><input type="text" name="price" id="mfPrice" placeholder="e.g. £50M"></div>
                <div><label>Club</label><input type="text" name="current_club" id="mfClub" placeholder="Current Club"></div>
            </div>
            
            <div class="row">
                <div><label>Nation</label><input type="text" name="nationality" id="mfNat" placeholder="Nationality"></div>
                <div>
                    <label>Reg</label>
                    <select name="registration_status" id="mfReg">
                        <option value="None">None</option>
                        <option value="HG Club">HG Club</option>
                        <option value="HG Nation">HG Nation</option>
                        <option value="Non-EU">Non-EU</option>
                    </select>
                </div>
            </div>

            <div class="m-footer">
                <button type="submit" name="action" value="delete_player_fav" class="btn btn-red" id="btnDelFav" onclick="return">Delete</button>
                <button type="button" class="btn btn-dark" onclick="closeM('mFavPlayer')">Cancel</button>
                <button type="submit" class="btn btn-green">Save</button>
            </div>
        </form>
    </div>
</div>

<script src="script.js?v=<?php echo time(); ?>"></script>
<script>
    // MODE FAVORIT
    currentTactic = 'FAV'; 
    function switchTactic(id) {}

    // FUNGSI MODAL UNTUK FAVORIT
    function modalPlayerFav(data) {
        const modal = document.getElementById('mFavPlayer');
        const btnDel = document.getElementById('btnDelFav');

        if(data === 0) { 
            // ADD NEW
            document.getElementById('mfTitle').innerText = "Add New Favorite";
            document.getElementById('mfId').value = 0;
            document.getElementById('mfName').value = "";
            document.getElementById('mfAge').value = 18;
            document.getElementById('mfRat').value = 140;
            document.getElementById('mfPot').value = 170;
            document.getElementById('mfPrice').value = "";
            document.getElementById('mfClub').value = "";
            document.getElementById('mfNat').value = "";
            document.getElementById('mfPos').value = "GK"; // Default
            document.getElementById('mfReg').value = "None";
            
            btnDel.style.display = 'none'; // Sembunyikan tombol delete saat Add New
        } else { 
            // EDIT EXISTING
            document.getElementById('mfTitle').innerText = "Edit Favorite";
            document.getElementById('mfId').value = data.id_fav;
            document.getElementById('mfName').value = data.full_name;
            document.getElementById('mfAge').value = data.current_age;
            document.getElementById('mfPos').value = data.pos_code; // Select berdasarkan string Code (misal 'ST')
            document.getElementById('mfRat').value = data.rating;
            document.getElementById('mfPot').value = data.potential;
            document.getElementById('mfPrice').value = data.price || "";
            document.getElementById('mfClub').value = data.current_club || "";
            document.getElementById('mfNat').value = data.nationality || "";
            document.getElementById('mfReg').value = data.registration_status || 'None';

            btnDel.style.display = 'block';
        }
        modal.style.display = 'flex';
    }
</script>
</body>
</html>