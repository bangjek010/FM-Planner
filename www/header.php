<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FM Planner</title>
    
    <!-- Import Font agar mirip Football Manager -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@700&family=Roboto+Condensed:wght@400;700&display=swap" rel="stylesheet">

    <style>
        /* RESET & BASIC */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Roboto Condensed', sans-serif;
            background-color: #333; /* Sekedar background body */
        }

        /* HEADER CONTAINER */
        .fm-header {
            background-color: #045e35; /* Warna Hijau Khas FM */
            padding: 15px 0;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            min-height: 80px;
        }

        /* BAGIAN TENGAH (JUDUL & MENU) */
        .center-content {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* LOGO "FM PLANNER" */
        .logo {
            font-family: 'Oswald', sans-serif; /* Font Tebal */
            font-size: 48px;
            font-weight: 900;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: -1px;
            line-height: 1;
            margin-bottom: 5px;
            text-shadow: 2px 2px 0px rgba(0,0,0,0.2); /* Sedikit bayangan */
        }

        /* NAVIGASI (Squad, Shortlist, Transfers) */
        .nav-menu {
            display: flex;
            gap: 20px;
        }

        .nav-menu a {
            text-decoration: none;
            color: #ffffff;
            font-size: 22px;
            font-weight: 400; /* Font agak ramping (condensed) */
            text-transform: capitalize; /* Huruf depan besar */
            font-family: 'Roboto Condensed', sans-serif;
            transition: color 0.2s;
        }

        /* Warna Kuning untuk menu aktif (Squad) */
        .nav-menu a.active {
            color: #ffcc00; /* Kuning emas */
            font-weight: 700;
        }

        .nav-menu a:hover {
            color: #ffcc00;
        }

        /* BAGIAN KANAN (TOMBOL LOGIN) */
        .auth-wrapper {
            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
        }

        /* Kapsul Putih */
        .pill-button {
            background-color: #ffffff;
            border-radius: 20px;
            display: flex;
            overflow: hidden;
            border: 1px solid #000;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.2);
        }

        .pill-button a {
            display: inline-block;
            padding: 5px 12px;
            font-family: 'Oswald', sans-serif; /* Font tebal untuk tombol */
            font-weight: 700;
            font-size: 14px;
            color: #000000;
            text-decoration: none;
            text-transform: uppercase;
            background: transparent;
            border: none;
            cursor: pointer;
        }

        .pill-button a:hover {
            background-color: #e0e0e0;
        }

        /* Garis Pemisah di tengah tombol */
        .separator {
            width: 1px;
            background-color: #000;
        }
    </style>
</head>
<body>

<header class="fm-header">


    <div class="center-content">
        <div class="logo">FM PLANNER</div>
        <nav class="nav-menu">
            <!-- Tambahkan class 'active' menggunakan logika PHP sederhana atau JS nanti -->
            <a href="index.php" class="<?= basename($_SERVER['PHP_SELF'])=='index.php'?'active':'' ?>">Squad</a>
            <a href="shortlist.php" class="<?= basename($_SERVER['PHP_SELF'])=='shortlist.php'?'active':'' ?>">Shortlist</a>
            <!-- MENU BARU -->
            <a href="favorit.php" class="<?= basename($_SERVER['PHP_SELF'])=='favorit.php'?'active':'' ?>">Favorites</a> 
        </nav>
    </div>

</header>

</body>
</html>