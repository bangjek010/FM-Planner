// ============================================
// 1. GLOBAL VARIABLES & SETUP
// ============================================
let currentTactic = 1; 
let draggedItem = null;
let dragSource = null; // 'pitch', 'subs', 'table'
let dragData = null;   
let dragOriginCoords = { top: null, left: null };

const dataWrap = document.querySelector('.data-wrap');

function switchPitchView(viewId, btn) {
    // Sembunyikan semua view
    document.getElementById('viewTeam1').style.display = 'none';
    document.getElementById('viewTeam2').style.display = 'none';
    
    // Tampilkan view yang dipilih
    document.getElementById(viewId).style.display = 'flex';
    
    // Update class active pada tombol
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

// ============================================
// 3. DROP ZONE: PITCH (OPTIMIZED / ANTI-LAG)
// ============================================

const allPitches = document.querySelectorAll('.pitch');
let isProcessingDrag = false;

allPitches.forEach(pitchContainer => {
    // A. DRAG OVER (Highlight Slot)
    pitchContainer.addEventListener('dragover', e => {
        e.preventDefault(); 
        e.dataTransfer.dropEffect = "move";
        
        if (!isProcessingDrag) {
            window.requestAnimationFrame(() => {
                const { x, y } = getRelCoords(e, pitchContainer);
                const activeHighlights = pitchContainer.querySelectorAll('.slot.drag-over');
                activeHighlights.forEach(s => s.classList.remove('drag-over'));
                
                const nearest = getNearestSlotInContainer(x, y, pitchContainer);
                if(nearest) nearest.classList.add('drag-over');
                isProcessingDrag = false; 
            });
            isProcessingDrag = true; 
        }
    });

    // B. DROP (Place Player)
    pitchContainer.addEventListener('drop', e => {
        e.preventDefault();
        isProcessingDrag = false;
        
        // Bersihkan Highlight
        pitchContainer.querySelectorAll('.slot').forEach(s => s.classList.remove('drag-over'));

        const { x, y } = getRelCoords(e, pitchContainer);
        const nearestEl = getNearestSlotInContainer(x, y, pitchContainer);
        if (!nearestEl) { if(draggedItem) draggedItem.style.opacity = '1'; return; }

        const finalTop = parseFloat(nearestEl.dataset.top);
        const finalLeft = parseFloat(nearestEl.dataset.left);

        // --- TENTUKAN TARGET SQUAD (1st vs 2nd) ---
        // Jika ID Pitch adalah 'pitchArea2', berarti ini 2nd Team
        let targetSquadStatus = '1st Team';
        if (pitchContainer.id === 'pitchArea2') {
            targetSquadStatus = '2nd Team';
        }

        // --- KASUS A: Pindah Posisi (Pitch -> Pitch) ---
        if (dragSource === 'pitch' && draggedItem) {
            draggedItem.style.opacity = '1';
            
            // Cek Tabrakan (Swap) di Container yang SAMA
            const targetCard = findCardInContainer(finalTop, finalLeft, draggedItem, pitchContainer);

            if (targetCard) {
                // SWAP
                updateCardPosition(draggedItem, finalTop, finalLeft);
                const oTop = parseFloat(dragOriginCoords.top);
                const oLeft = parseFloat(dragOriginCoords.left);
                
                // Pindahkan kartu target ke posisi asal kartu yang didrag
                if (!isNaN(oTop) && !isNaN(oLeft)) {
                    updateCardPosition(targetCard, oTop, oLeft);
                    savePosition(targetCard.dataset.id, oTop, oLeft);
                }
                savePosition(draggedItem.dataset.id, finalTop, finalLeft);
            } else {
                // MOVE (Slot Kosong)
                updateCardPosition(draggedItem, finalTop, finalLeft);
                savePosition(draggedItem.dataset.id, finalTop, finalLeft);
            }
        } 
        
        // --- KASUS B: Table/Subs -> Pitch ---
        else if ((dragSource === 'table' || dragSource === 'subs')) {
            // Cek Slot Terisi
            const existingCard = findCardInContainer(finalTop, finalLeft, null, pitchContainer);
            if (existingCard) {
                alert("Slot occupied! Move existing player first."); 
                if(draggedItem) draggedItem.style.opacity = '1';
                return;
            }

            // Ambil ID
            const idEntry = (dragSource === 'table') ? dragData.id_entry : draggedItem.dataset.id;
            
            // Khusus Favorit: Tidak ada 'move_to_pitch', melainkan update koordinat saja
            if (currentTactic === 'FAV') {
                savePosition(idEntry, finalTop, finalLeft); // Logic savePosition sudah handle FAV
                setTimeout(() => window.location.reload(), 100);
            } else {
                // SQUAD LOGIC
                const fd = new FormData();
                fd.append('action', 'move_to_pitch');
                fd.append('id_entry', idEntry);
                fd.append('tactic_id', currentTactic);
                fd.append('top', finalTop);
                fd.append('left', finalLeft);
                fd.append('target_status', targetSquadStatus); // Kirim Status Target!

                fetch('api.php', { method: 'POST', body: fd }).then(() => window.location.reload());
            }
        }
        draggedItem = null;
    });
});


function getNearestSlotInContainer(x, y, container) {
    let nearestEl = null;
    let minDist = Infinity;
    container.querySelectorAll('.slot').forEach(slot => {
        const sTop = parseFloat(slot.dataset.top);
        const sLeft = parseFloat(slot.dataset.left);
        const dist = Math.sqrt(Math.pow(sTop - y, 2) + Math.pow(sLeft - x, 2));
        if (dist < minDist) { minDist = dist; nearestEl = slot; }
    });
    return nearestEl;
}
function getNearestSlotDataInContainer(x, y, container) {
    let nearest = null;
    let minDist = Infinity;
    container.querySelectorAll('.slot').forEach(slot => {
        const sTop = parseFloat(slot.dataset.top);
        const sLeft = parseFloat(slot.dataset.left);
        const dist = Math.sqrt(Math.pow(sTop - y, 2) + Math.pow(sLeft - x, 2));
        if (dist < minDist) { minDist = dist; nearest = { top: sTop, left: sLeft }; }
    });
    return nearest;
}

function findCardInContainer(top, left, ignoreCard, container) {
    const cards = container.querySelectorAll('.card'); 
    for (let card of cards) {
        if (card === ignoreCard) continue;
        const cTop = parseFloat(card.style.top);
        const cLeft = parseFloat(card.style.left);
        if (Math.abs(cTop - top) < 0.5 && Math.abs(cLeft - left) < 0.5) {
            return card;
        }
    }
    return null;
}

const allSubs = document.querySelectorAll('.subs-grid');
allSubs.forEach(subsArea => {
    subsArea.addEventListener('dragover', e => {
        e.preventDefault(); 
        subsArea.parentElement.style.borderColor = "#f1c40f";
    });
    subsArea.addEventListener('dragleave', () => { 
        subsArea.parentElement.style.borderColor = "#95a5a6";
    });
    subsArea.addEventListener('drop', e => {
        e.preventDefault();
        subsArea.parentElement.style.borderColor = "#95a5a6";

        if (dragSource === 'pitch' && draggedItem) {
            const idEntry = draggedItem.dataset.id;
            const fd = new FormData();
            fd.append('action', 'move_to_subs');
            fd.append('id_entry', idEntry);
            fetch('api.php', { method: 'POST', body: fd }).then(() => window.location.reload());
        }
        draggedItem = null;
    });
});

if (dataWrap) {
    dataWrap.addEventListener('dragover', e => {
        e.preventDefault();
        if (dragSource === 'pitch') {
            e.dataTransfer.dropEffect = "move";
            // Efek visual: merah transparan
            dataWrap.style.boxShadow = "inset 0 0 20px rgba(192, 57, 43, 0.2)"; 
        }
    });

    dataWrap.addEventListener('dragleave', () => { dataWrap.style.boxShadow = "none"; });

    dataWrap.addEventListener('drop', e => {
        e.preventDefault();
        dataWrap.style.boxShadow = "none";

if (dragSource === 'pitch' && draggedItem) {
            const id = draggedItem.dataset.id; 

            // 1. FAVORITE MODE: HAPUS DARI PITCH (TANPA CONFIRM)
            if (currentTactic === 'FAV') {
                const fd = new FormData();
                fd.append('action', 'remove_from_pitch_fav');
                fd.append('id_fav', id);
                
                fetch('api.php', { method: 'POST', body: fd })
                .then(() => {
                    draggedItem.remove(); 
                    draggedItem = null;
                });
            } 
            // 2. SHORTLIST MODE: HAPUS KOORDINAT (TANPA CONFIRM)
            else if (currentTactic === 'SL') {
                const fd = new FormData();
                fd.append('action', 'remove_from_pitch_sl');
                fd.append('id_entry', id);
                fetch('api.php', { method: 'POST', body: fd }).then(() => draggedItem.remove());
            }
            // 3. SQUAD MODE (PINDAH KE SUBS TANPA CONFIRM)
            else {
                const fd = new FormData();
                fd.append('action', 'move_to_subs');
                fd.append('id_entry', id);
                fetch('api.php', { method: 'POST', body: fd }).then(() => window.location.reload());
            }
        }
        draggedItem = null;
    });
}


//=========================================
// 2. DRAG START HANDLERS
// ============================================

// A. Drag dari Kartu (Baik di Lapangan atau Sidebar)
function dragStartCard(e) {
    draggedItem = e.target.closest('.card');
    const origin = draggedItem.dataset.origin; 
    
    if (origin === 'subs') {
        dragSource = 'subs';
    } else {
        dragSource = 'pitch';
        dragOriginCoords.top = draggedItem.style.top;
        dragOriginCoords.left = draggedItem.style.left;
    }
    
    e.dataTransfer.effectAllowed = "move";
    // Gunakan ID entry (atau ID fav) sebagai data transfer
    e.dataTransfer.setData("text/plain", draggedItem.dataset.id);
    setTimeout(() => draggedItem.style.opacity = '0.5', 0);
}

// B. Drag dari Tabel
function dragStartTable(e, playerData) {
    dragSource = 'table';
    dragData = playerData; 
    e.dataTransfer.effectAllowed = "copyMove";
}
// ============================================
// 3. DROP ZONE: PITCH (LAPANGAN) - SWAP LOGIC ADDED
// ============================================

if (pitch) {
    pitch.addEventListener('dragover', e => {
        e.preventDefault();
        e.dataTransfer.dropEffect = "move";
        
        // Highlight Slot
        const { x, y } = getRelCoords(e, pitch);
        document.querySelectorAll('.slot').forEach(s => s.classList.remove('drag-over'));
        const nearest = getNearestSlotElement(x, y);
        if(nearest) nearest.classList.add('drag-over');
    });

    pitch.addEventListener('drop', e => {
        e.preventDefault();
        
        const { x, y } = getRelCoords(e, pitch);
        const snap = getNearestSlot(x, y); // Cari slot terdekat
        if (!snap) { resetHighlights(); return; }

        const finalTop = snap.top;
        const finalLeft = snap.left;

        // --- LOGIKA UTAMA (SWAP vs MOVE) ---

        // KASUS A: Pindah Posisi dalam Lapangan (Pitch -> Pitch)
        if (dragSource === 'pitch' && draggedItem) {
            draggedItem.style.opacity = '1';

            // 1. CEK APAKAH ADA PEMAIN LAIN DI SLOT TUJUAN?
            const targetCard = findCardAtPosition(finalTop, finalLeft, draggedItem);

            if (targetCard) {
                // === SWAPPING LOGIC ===
                
                // A. Pindahkan Dragged Item ke Slot Tujuan
                updateCardPosition(draggedItem, finalTop, finalLeft);
                
                // B. Pindahkan Target Card ke Posisi Asal Dragged Item
                // (Gunakan parseFloat agar formatnya sama, misal "90%" -> 90)
                const originTop = parseFloat(dragOriginCoords.top);
                const originLeft = parseFloat(dragOriginCoords.left);
                
                // Cek jika posisi asal valid (bukan dari bench/null)
                if (!isNaN(originTop) && !isNaN(originLeft)) {
                    updateCardPosition(targetCard, originTop, originLeft);
                    
                    // Simpan Perubahan Target Card ke DB
                    savePosition(targetCard.dataset.id, originTop, originLeft);
                } else {
                    // Jika error (misal drag dari tempat antah berantah), reset target
                    // (Opsional: biarkan target di tempat atau geser ke bench)
                }

                // C. Simpan Perubahan Dragged Item ke DB
                savePosition(draggedItem.dataset.id, finalTop, finalLeft);

            } else {
                // === NORMAL MOVE LOGIC (KOSONG) ===
                updateCardPosition(draggedItem, finalTop, finalLeft);
                savePosition(draggedItem.dataset.id, finalTop, finalLeft);
            }
        }

        // KASUS B: Dari Tabel/Subs -> Masuk Lapangan
        else if ((dragSource === 'table' || dragSource === 'subs')) {
            // Cek Limit Pemain (Max 11 untuk Squad, Unlimited untuk Shortlist)
            const cardsOnPitch = pitch.querySelectorAll('.card').length;
            if (currentTactic !== 'SL' && cardsOnPitch >= 11 && dragSource === 'table') {
                alert("Pitch Full! (Max 11 Players)");
                resetHighlights();
                return;
            }
            
            // Cek apakah slot sudah terisi? Jika ya, tolak (atau bisa dibuat swap dengan subs, tapi kompleks)
            const existingCard = findCardAtPosition(finalTop, finalLeft, null);
            if (existingCard) {
                alert("Slot occupied! Move the player on the pitch first.");
                resetHighlights();
                return;
            }

            const idEntry = (dragSource === 'table') ? dragData.id_entry : draggedItem.dataset.id;
            
            const fd = new FormData();
            fd.append('action', 'move_to_pitch');
            fd.append('id_entry', idEntry);
            fd.append('tactic_id', currentTactic);
            fd.append('top', finalTop);
            fd.append('left', finalLeft);

            fetch('api.php', { method: 'POST', body: fd })
            .then(res => res.text())
            .then(data => { window.location.reload(); });
        }
        
        resetHighlights();
        draggedItem = null;
    });
}

// ============================================
// 4. DROP ZONE: REMOVE FROM PITCH
//    (Subs Sidebar untuk Index & Table Area untuk Shortlist)
// ============================================

// A. INDEX PAGE: PITCH -> SUBS
if (subsArea) {
    subsArea.addEventListener('dragover', e => {
        e.preventDefault(); e.dataTransfer.dropEffect = "move";
        subsArea.style.borderColor = "#f1c40f";
    });
    subsArea.addEventListener('dragleave', () => { subsArea.style.borderColor = "#95a5a6"; });

subsArea.addEventListener('drop', e => {
        e.preventDefault();
        subsArea.parentElement.style.borderColor = "#95a5a6";

        if (dragSource === 'pitch' && draggedItem) {
            const idEntry = draggedItem.dataset.id;
            
            // LANGSUNG PINDAH KE SUBS TANPA CONFIRM
            const fd = new FormData();
            fd.append('action', 'move_to_subs');
            fd.append('id_entry', idEntry);
            fetch('api.php', { method: 'POST', body: fd }).then(() => window.location.reload());
        }
        draggedItem = null;
    });
}

// B. SHORTLIST PAGE: PITCH -> TABLE (REMOVE COORDINATES)
// Kita gunakan .data-wrap sebagai drop zone yang luas
if (dataWrap && !subsArea) { // !subsArea memastikan ini bukan di index page
    dataWrap.addEventListener('dragover', e => {
        e.preventDefault(); 
        if (dragSource === 'pitch') {
            e.dataTransfer.dropEffect = "move";
            dataWrap.style.boxShadow = "inset 0 0 20px rgba(192, 57, 43, 0.2)"; // Merah muda
        }
    });

    dataWrap.addEventListener('dragleave', () => { dataWrap.style.boxShadow = "none"; });

dataWrap.addEventListener('drop', e => {
        e.preventDefault();
        dataWrap.style.boxShadow = "none";

        if (dragSource === 'pitch' && draggedItem) {
            const id = draggedItem.dataset.id; // id_entry atau id_fav

            // --- SKENARIO 1: SHORTLIST MODE ---
            if (currentTactic === 'SL') {
                const fd = new FormData();
                fd.append('action', 'remove_from_pitch_sl');
                fd.append('id_entry', id);
                
                fetch('api.php', { method: 'POST', body: fd })
                .then(() => {
                    draggedItem.remove(); // Hapus visual saja, tidak perlu reload
                    draggedItem = null;
                });
            } 
            
            // --- SKENARIO 2: FAVORITES MODE ---
            else if (currentTactic === 'FAV') {
                const fd = new FormData();
                fd.append('action', 'remove_from_pitch_fav');
                fd.append('id_fav', id); // Perhatikan parameter di API adalah id_fav
                
                fetch('api.php', { method: 'POST', body: fd })
                .then(() => {
                    draggedItem.remove(); // Hapus visual saja
                    draggedItem = null;
                });
            }

            // --- SKENARIO 3: SQUAD MODE (INDEX) ---
            else {
                // Di Squad mode, drag ke tabel berarti "Kembalikan ke Bangku Cadangan (Subs)"
                if(confirm("Remove from lineup to Subs?")) {
                    const fd = new FormData();
                    fd.append('action', 'move_to_subs');
                    fd.append('id_entry', id);
                    
                    fetch('api.php', { method: 'POST', body: fd })
                    .then(() => window.location.reload()); // Reload agar tabel subs terupdate
                } else {
                    draggedItem.style.opacity = '1'; // Batal
                }
            }
        }
        draggedItem = null;
    });
}


// ============================================
// 5. HELPER FUNCTIONS
// ============================================

// Fungsi mencari kartu yang ada di koordinat tertentu (Collision Check)
function findCardAtPosition(top, left, ignoreCard) {
    const cards = document.querySelectorAll('.card');
    for (let card of cards) {
        if (card === ignoreCard) continue; // Jangan cek diri sendiri
        
        // Toleransi jarak (karena float bisa beda tipis)
        const cTop = parseFloat(card.style.top);
        const cLeft = parseFloat(card.style.left);
        
        // Cek apakah koordinatnya SAMA PERSIS dengan Slot (karena sistem snap)
        // Gunakan margin error kecil (misal 0.5)
        if (Math.abs(cTop - top) < 0.5 && Math.abs(cLeft - left) < 0.5) {
            return card;
        }
    }
    return null;
}

// Fungsi Update Posisi Kartu secara Visual & Dataset
function updateCardPosition(card, top, left) {
    card.style.top = top + '%';
    card.style.left = left + '%';
    card.classList.add('custom-pos');
    
    if(currentTactic === 'SL') {
        card.dataset.slTop = top; card.dataset.slLeft = left;
    } 
    else if (currentTactic === 'FAV') { 
        card.dataset.favTop = top; card.dataset.favLeft = left;
    }
    else {
        card.dataset[`t${currentTactic}Top`] = top;
        card.dataset[`t${currentTactic}Left`] = left;
    }
}

function resetHighlights() {
    document.querySelectorAll('.slot').forEach(s => s.classList.remove('drag-over'));
    if(draggedItem) draggedItem.style.opacity = '1';
}

function getRelCoords(e, container) {
    const rect = container.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    return { x, y };
}

function getNearestSlot(x, y) {
    let nearest = null;
    let minDist = Infinity;
    document.querySelectorAll('.slot').forEach(slot => {
        const sTop = parseFloat(slot.dataset.top);
        const sLeft = parseFloat(slot.dataset.left);
        const dist = Math.sqrt(Math.pow(sTop - y, 2) + Math.pow(sLeft - x, 2));
        if (dist < minDist) {
            minDist = dist;
            nearest = { top: sTop, left: sLeft };
        }
    });
    return nearest;
}

function getNearestSlotElement(x, y) {
    let nearestEl = null;
    let minDist = Infinity;
    document.querySelectorAll('.slot').forEach(slot => {
        const sTop = parseFloat(slot.dataset.top);
        const sLeft = parseFloat(slot.dataset.left);
        const dist = Math.sqrt(Math.pow(sTop - y, 2) + Math.pow(sLeft - x, 2));
        if (dist < minDist) { minDist = dist; nearestEl = slot; }
    });
    return nearestEl;
}

function savePosition(id, top, left) {
    const fd = new FormData();
    
    if (currentTactic === 'FAV') {
        fd.append('action', 'save_position_fav');
        fd.append('id_fav', id); 
    } else {
        fd.append('action', 'save_position');
        fd.append('id_entry', id);
    }
    
    fd.append('tactic_id', currentTactic);
    fd.append('top', top);
    fd.append('left', left);
    fetch('api.php', { method: 'POST', body: fd });
}

// ============================================
// 6. UI FUNCTIONS (Modal, Theme, Tactic)
// ============================================

function switchTactic(id) {
    currentTactic = id;
    
    // 1. UPDATE LABEL JUDUL (Untuk Pitch 1 dan Pitch 2)

    const lbl1_new = document.getElementById('tacticLabel1'); // Jaga-jaga jika ID diupdate
    const lbl1 = document.getElementById('tacticLabel1'); 
    const lbl2 = document.getElementById('tacticLabel2'); 
    if (lbl1) lbl1.innerText = "TACTIC " + id + " (1ST TEAM)";
    if (lbl2) lbl2.innerText = "TACTIC " + id + " (2ND TEAM)";
    if (lbl1_new) lbl1_new.innerText = "TACTIC " + id + " (1ST TEAM)";


    // 2. UPDATE WARNA DOTS (DI SEMUA HEADER)
    // Kita looping setiap container 'dots-container' agar Pitch 1 & 2 terupdate barengan
    document.querySelectorAll('.dots-container').forEach(container => {
        const dots = container.querySelectorAll('.dot');
        dots.forEach((dot, index) => {
            if (index + 1 === id) dot.classList.add('active');
            else dot.classList.remove('active');
        });
    });

// Update Player Positions (Pitch 1 & 2)
    document.querySelectorAll('.card').forEach(card => {
        const top = card.dataset[`t${id}Top`];
        const left = card.dataset[`t${id}Left`];

        if (top && left && top != 0) {
            card.style.transition = "all 0.3s ease";
            card.style.top = top + '%';
            card.style.left = left + '%';
            card.classList.add('custom-pos');
            card.style.display = 'flex';
        }
    });
}

function modalPlayer(data, defaultStatus = '1st Team') {
    const modal = document.getElementById('mPlayer');
    if(!modal) return; 

    const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
    const setDisplay = (id, val) => { const el = document.getElementById(id); if (el) el.style.display = val; };

    if (data === 0) { 
        // ADD NEW
        document.getElementById('mpTitle').innerText = "Add New Player";
        setVal('mpId', 0); setVal('mpName', ""); setVal('mpAge', 18);
        setVal('mpRat', 100); setVal('mpPot', 150);
        setVal('mpPrice', ""); setVal('mpClub', "");
        setVal('mpStatus', defaultStatus);
        setVal('mpReg', 'None'); // Default Reg
        setDisplay('btnDel', 'none'); setDisplay('btnReturn', 'none');
    } else { 
        // EDIT
        document.getElementById('mpTitle').innerText = "Edit Player";
        setVal('mpId', data.id_entry); setVal('mpName', data.full_name);
        setVal('mpAge', data.current_age); setVal('mpPos', data.id_tactic_position);
        setVal('mpRat', data.rating); setVal('mpPot', data.potential);
        setVal('mpStatus', data.squad_status);
        setVal('mpPrice', data.price || ""); setVal('mpClub', data.current_club || "");
        setVal('mpReg', data.registration_status || 'None'); // Isi Reg

        // Button Logic (Sama seperti sebelumnya)
        // ...
    }
    modal.style.display = 'flex';
}


function openModal(id) { const m = document.getElementById(id); if(m) m.style.display = 'flex'; }
function closeM(id) { const m = document.getElementById(id); if(m) m.style.display = 'none'; }
window.onclick = e => { if(e.target.classList.contains('modal')) e.target.style.display = 'none'; };
// Theme Setup
const themeVars = ['--bg-main', '--pitch-dark', '--pitch-light', '--tbl-header', '--tbl-row-odd', '--col-gk', '--col-def', '--col-mid', '--col-wing', '--col-att'];
const varToId = { '--bg-main': 'cp_bg_main', '--pitch-dark': 'cp_pitch_dark', '--pitch-light': 'cp_pitch_light', '--tbl-header': 'cp_tbl_header', '--tbl-row-odd': 'cp_tbl_row', '--col-gk': 'cp_col_gk', '--col-def': 'cp_col_def', '--col-mid': 'cp_col_mid', '--col-wing': 'cp_col_wing', '--col-att': 'cp_col_att' };

function updateTheme(varName, colorVal) {
    document.documentElement.style.setProperty(varName, colorVal);
    localStorage.setItem('fm_theme_' + varName, colorVal);
}
function loadTheme() {
    ['--bg-main', '--pitch-dark', '--pitch-light'].forEach(v => {
        const saved = localStorage.getItem('fm_theme_' + v);
        if (saved) document.documentElement.style.setProperty(v, saved);
    });
}

function resetTheme() {
    if(('Reset all colors?')) {
        themeVars.forEach(v => localStorage.removeItem('fm_theme_' + v));
        window.location.reload();
    }
}
document.addEventListener('DOMContentLoaded', loadTheme);

function updateCompareList(side) {
    const filterVal = document.getElementById('filter' + side).value;
    const select = document.getElementById('comp' + side);
    
    // Kosongkan Dropdown
    select.innerHTML = '<option value="">-- Select Player --</option>';

    // Filter Data berdasarkan Kategori
    let filtered = ALL_PLAYERS_DATA.filter(p => {
        const s = p.squad_status;
        
        if (filterVal === 'all') return true;
        
        // --- LOGIC FILTER ---
        if (filterVal === 'fav') {
            return s === 'Favorite'; // Cek status Favorite
        }
        if (filterVal === 'squad') {
            return ['1st Team', '2nd Team', 'Subs', '2nd Team Subs'].includes(s);
        }
        if (filterVal === 'shortlist') return s === 'Shortlist';
        if (filterVal === 'out') return s === 'Players Out';
        
        return false;
    });

    // Sort Data: Posisi -> Rating Tinggi -> Nama
    filtered.sort((a, b) => {
        // Fallback jika id_tactic_position null (untuk jaga-jaga)
        const posA = a.id_tactic_position ? parseInt(a.id_tactic_position) : 99;
        const posB = b.id_tactic_position ? parseInt(b.id_tactic_position) : 99;
        
        if (posA !== posB) return posA - posB; // Urut Posisi (GK, CB...)
        return parseFloat(b.rating) - parseFloat(a.rating); // Urut Rating
    });

    // Populate Dropdown
    if (filtered.length === 0) {
        const opt = document.createElement('option');
        opt.text = "(No players found)";
        select.add(opt);
        return;
    }

    if (filterVal === 'squad') {
        // ... (Logika Optgroup Squad tetap sama) ...
        const groups = {
            '1st Team': document.createElement('optgroup'),
            'Subs': document.createElement('optgroup'),
            '2nd Team': document.createElement('optgroup'),
            'Other': document.createElement('optgroup')
        };
        groups['1st Team'].label = "1st Team";
        groups['Subs'].label = "Substitutes";
        groups['2nd Team'].label = "2nd Team / Reserves";
        groups['Other'].label = "Others";

        filtered.forEach(p => {
            const opt = createOption(p);
            if(p.squad_status === '1st Team') groups['1st Team'].appendChild(opt);
            else if(p.squad_status === 'Subs') groups['Subs'].appendChild(opt);
            else if(p.squad_status === '2nd Team' || p.squad_status === '2nd Team Subs') groups['2nd Team'].appendChild(opt);
            else groups['Other'].appendChild(opt);
        });

        for (let key in groups) {
            if (groups[key].children.length > 0) select.add(groups[key]);
        }

    } else {
        // Untuk Favorites, Shortlist, Out, All -> List biasa
        filtered.forEach(p => {
            select.add(createOption(p));
        });
    }
}

function createOption(p) {
    const opt = document.createElement('option');
    opt.value = p.id_entry;
    
    const ratDisp = parseFloat(p.rating).toFixed(1);
    const potDisp = parseFloat(p.potential).toFixed(1);
    
    // Tampilan Teks: "Pos Code - Nama (Rat | Pot)"
    opt.text = `${p.pos_code} - ${p.full_name} (${ratDisp} | ${potDisp})`;
    return opt;
}

// LOGIKA COMPARE
function initCompare() {
    // Pastikan data pemain sudah ada (dikirim dari PHP)
    if(typeof ALL_PLAYERS_DATA === 'undefined') return;
    

    updateCompareList('A');
    updateCompareList('B');


    const selA = document.getElementById('compA');
    const selB = document.getElementById('compB');
    if(!selA || !selB) return;

    // Reset dropdown (sisakan opsi pertama "Select...")
    selA.length = 1;
    selB.length = 1;

    // --- LOGIKA SORTING (Sama seperti Select Player) ---
    ALL_PLAYERS_DATA.sort((a, b) => {
        // 1. Urutkan berdasarkan Posisi (ID Tactic Position) - Ascending
        // GK (1) -> CB (2) -> dst
        const posA = parseInt(a.id_tactic_position);
        const posB = parseInt(b.id_tactic_position);
        
        if (posA !== posB) {
            return posA - posB;
        }

        // 2. Jika Posisi sama, urutkan Rating (Descending / Tertinggi ke Terendah)
        const ratA = parseFloat(a.rating);
        const ratB = parseFloat(b.rating);
        
        return ratB - ratA; // B - A menghasilkan urutan menurun
    });

    // --- ISI DROPDOWN ---
    ALL_PLAYERS_DATA.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id_entry;
        
        // Format angka desimal 2 digit
        const ratDisp = parseFloat(p.rating).toFixed(2);
        const potDisp = parseFloat(p.potential).toFixed(2);

        // Teks: "Pos - Nama (Rat: XX | Pot: XX)"
        opt.text = `${p.pos_code} - ${p.full_name} (Rat: ${ratDisp} | Pot: ${potDisp})`;
        
        // Tambahkan ke kedua dropdown
        selA.add(opt.cloneNode(true));
        selB.add(opt);
    });
}

// Fungsi Run Compare tetap sama seperti sebelumnya
function runCompare() {
    const idA = document.getElementById('compA').value;
    const idB = document.getElementById('compB').value;
    
    // Reset tampilan jika kosong
    if(!idA || !idB) return;

    const pA = ALL_PLAYERS_DATA.find(p => p.id_entry == idA);
    const pB = ALL_PLAYERS_DATA.find(p => p.id_entry == idB);

    // Set Names
    document.getElementById('nmA').innerText = pA.full_name;
    document.getElementById('nmB').innerText = pB.full_name;

    // Helper Compare
    const cmp = (valA, valB, elA, elB, highIsGood = true) => {
        const e1 = document.getElementById(elA);
        const e2 = document.getElementById(elB);
        
        e1.innerText = isNaN(valA) ? valA : parseFloat(valA).toFixed(2);
        e2.innerText = isNaN(valB) ? valB : parseFloat(valB).toFixed(2);
        
        e1.style.color = '#000'; e1.style.fontWeight = 'normal';
        e2.style.color = '#000'; e2.style.fontWeight = 'normal';

        if(valA == valB) return;
        const betterA = highIsGood ? (valA > valB) : (valA < valB);
        if(betterA) { e1.style.color = 'green'; e1.style.fontWeight = 'bold'; } 
        else { e2.style.color = 'green'; e2.style.fontWeight = 'bold'; }
    };

    cmp(parseFloat(pA.current_age), parseFloat(pB.current_age), 'ageA', 'ageB', false); // Muda = Hijau
    cmp(parseFloat(pA.rating), parseFloat(pB.rating), 'ratA', 'ratB', true);
    cmp(parseFloat(pA.potential), parseFloat(pB.potential), 'potA', 'potB', true);
    
    document.getElementById('priA').innerText = pA.price || '-';
    document.getElementById('priB').innerText = pB.price || '-';
    document.getElementById('staA').innerText = pA.squad_status;
    document.getElementById('staB').innerText = pB.squad_status;
}

// ============================================
// TABLE DRAG & DROP LOGIC (SWAP / MOVE)
// ============================================

function allowDropTable(e) {
    e.preventDefault();
    // Efek visual sederhana saat hover di tabel
    e.currentTarget.style.backgroundColor = "#e8f6f3"; // Hijau muda tipis
}

// Reset background saat mouse keluar atau drop selesai
document.addEventListener('dragleave', function(e) {
    if (e.target.tagName === 'TR') {
        e.target.style.backgroundColor = ""; 
    }
});

function dropTable(e, targetStatus, targetRowData) {
    e.preventDefault();
    e.currentTarget.style.backgroundColor = ""; // Reset warna

    // Validasi: Hanya menerima drag dari Tabel (dragData harus ada)
    if (!dragData) {
        // Opsional: Jika mau support drag dari Pitch ke Tabel Kosong juga bisa
        // Tapi logic utamanya di sini untuk Tabel ke Tabel
        return; 
    }

    const draggedId = dragData.id_entry;
    
if (targetRowData) {
        const targetId = targetRowData.id_entry;

        // Jangan swap jika ID sama (drop di diri sendiri)
        if (draggedId == targetId) return;

        // LANGSUNG SWAP TANPA CONFIRM
        const fd = new FormData();
        fd.append('action', 'swap_players_general'); 
        fd.append('id1', draggedId);
        fd.append('id2', targetId);
        
        fetch('api.php', { method: 'POST', body: fd })
            .then(() => window.location.reload());
    }
    // SKENARIO 2: MOVE (Jika dijatuhkan di baris kosong)
    else {
        // Cek jika status sama, tidak perlu reload
        if (dragData.squad_status === targetStatus) return;

        const fd = new FormData();
        fd.append('action', 'move_player_status'); // Action API Baru
        fd.append('id_entry', draggedId);
        fd.append('target_status', targetStatus);
        
        fetch('api.php', { method: 'POST', body: fd })
            .then(() => window.location.reload());
    }
    
    // Reset Data
    dragData = null;
    dragSource = null;
}