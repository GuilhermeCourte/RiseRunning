<?php
// Inicia sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$login_error = '';
$show_login_modal = false;

// Processa o Login se houver POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'do_login') {
    
    // --- CORREÇÃO DO CAMINHO AQUI ---
    // O index.php está em 'public', então entramos na pasta 'admin' para achar o arquivo
    if (file_exists('admin/auth.php')) {
        require_once 'admin/auth.php';
    } elseif (file_exists('../admin/auth.php')) {
        // Fallback caso a estrutura seja diferente
        require_once '../admin/auth.php';
    } else {
        die("Erro: Não foi possível encontrar o arquivo auth.php. Verifique se ele está na pasta 'public/admin/'.");
    }
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (function_exists('getUsers')) {
        $users = getUsers();

        if (isset($users[$username]) && password_verify($password, $users[$username])) {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['username'] = $username;
            
            // Redireciona para o dashboard (que também deve estar em admin ou na raiz?)
            // Se o dashboard.php estiver solto na public:
            header('Location: admin/dashboard.php');
            
            // Se o dashboard.php estiver DENTRO da pasta admin, use:
            // header('Location: admin/dashboard.php');
            
            exit;
        } else {
            $login_error = 'ACESSO NEGADO /// VERIFIQUE CREDENCIAIS';
            $show_login_modal = true;
        }
    } else {
        $login_error = 'ERRO SISTEMA /// FUNÇÃO GETUSERS NÃO ENCONTRADA';
        $show_login_modal = true;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RISE RUNNING</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:ital,wght@0,100..900;1,100..900&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="icon" href="favicon_rise.png">

    <style>
        :root {
            /* --- VARIAVEIS GLOBAIS --- */
            --md-sys-color-primary: #ff5e00; 
            --md-sys-color-on-primary: #000000;
            --md-sys-color-tertiary: #d4ff00; 
            
            --md-sys-color-background: #0a0a0a;
            --md-sys-color-surface: #121212;
            --md-sys-color-container: #2d2d2d;
            --md-sys-color-on-surface: #e2e2e2;
            --md-sys-color-outline: #3e3e3e;
            
            --hero-gradient: radial-gradient(circle at center, #333 0%, #000 100%);
            --noise-opacity: 0.05;
            
            --font-display: 'Archivo', sans-serif;
            --font-tech: 'Space Mono', monospace;
            
            --grid-gap: 1px;
            --border-width: 1px;
            
            --ease-out-expo: cubic-bezier(0.19, 1, 0.22, 1);
        }

        [data-theme="light"] {
            --md-sys-color-primary: #ff4800;
            --md-sys-color-on-primary: #ffffff;
            --md-sys-color-tertiary: #6b8000;
            
            --md-sys-color-background: #f0f0f0;
            --md-sys-color-surface: #ffffff;
            --md-sys-color-container: #e0e0e0;
            --md-sys-color-on-surface: #1a1a1a;
            --md-sys-color-outline: #cccccc;
            --hero-gradient: radial-gradient(circle at center, #ffffff 0%, #dcdcdc 100%);
            --noise-opacity: 0.03;
        }

        /* Cursor: none global (será anulado no mobile no fim do CSS) */
        * { margin: 0; padding: 0; box-sizing: border-box; cursor: none; }

        body {
            background-color: var(--md-sys-color-background);
            color: var(--md-sys-color-on-surface);
            font-family: var(--font-display);
            overflow-x: hidden;
            transition: background-color 0.5s ease, color 0.5s ease;
        }

        /* TEXTURA */
        .noise-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='1'/%3E%3C/svg%3E");
            pointer-events: none; z-index: 9000; mix-blend-mode: overlay; opacity: var(--noise-opacity);
        }

        /* --- PRELOADER --- */
        .loader-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #000; z-index: 10000;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            transition: opacity 0.6s ease, visibility 0.6s;
        }
        .heartbeat-wrapper { position: relative; display: flex; justify-content: center; align-items: center; width: 100%; height: 200px; }
        .loader-text {
            font-family: var(--font-display); font-size: 7vw; font-weight: 900; letter-spacing: -0.05em; text-transform: uppercase;
            position: absolute; z-index: 1; white-space: nowrap; color: #111; 
            animation: textPulseWhite 2.5s ease-in-out forwards;
        }
        .loader-text span { display: inline-block; color: #111; animation: textPulseColor 2.5s ease-in-out forwards; }
        .ecg-svg { position: absolute; width: 80vw; max-width: 600px; height: 150px; z-index: 2; overflow: visible; }
        .heart-path {
            fill: none; stroke: var(--md-sys-color-primary); stroke-width: 4; stroke-linecap: round; stroke-linejoin: round;
            stroke-dasharray: 1000; stroke-dashoffset: 1000;
            animation: drawLine 2.5s ease-in-out forwards;
            filter: drop-shadow(0 0 10px var(--md-sys-color-primary));
        }
        .loader-overlay.hidden { opacity: 0; visibility: hidden; pointer-events: none; }

        @keyframes drawLine { 0% { stroke-dashoffset: 1000; } 30% { stroke-dashoffset: 600; } 40% { stroke-dashoffset: 600; } 100% { stroke-dashoffset: 0; } }
        @keyframes textPulseWhite { 0% { color: #111; text-shadow: none; } 36% { color: #fff; text-shadow: 0 0 30px #fff; } 100% { color: #fff; } }
        @keyframes textPulseColor { 0% { color: #111; text-shadow: none; } 36% { color: var(--md-sys-color-primary); text-shadow: 0 0 30px var(--md-sys-color-primary); } 100% { color: var(--md-sys-color-primary); } }

        /* CURSOR */
        .cursor-dot { width: 8px; height: 8px; background: var(--md-sys-color-on-surface); position: fixed; border-radius: 50%; pointer-events: none; z-index: 9999; mix-blend-mode: exclusion; }
        .cursor-outline { width: 40px; height: 40px; border: 1px solid var(--md-sys-color-on-surface); position: fixed; border-radius: 50%; pointer-events: none; z-index: 9999; mix-blend-mode: exclusion; transition: width 0.3s, height 0.3s; }
        body.hovering .cursor-outline { width: 80px; height: 80px; background: rgba(255,255,255,0.1); border-color: transparent; }

        /* HEADER */
        header {
            display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; padding: 20px;
            border-bottom: var(--border-width) solid var(--md-sys-color-outline);
            position: fixed; width: 100%; top: 0; z-index: 100;
            background: rgba(10, 10, 10, 0.05); backdrop-filter: blur(10px);
        }
        
        .brand { 
            font-size: 1.5rem; font-weight: 900; letter-spacing: -0.05em; text-transform: uppercase; 
            text-decoration: none; color: var(--md-sys-color-on-surface); justify-self: start;
        }
        .brand span { color: var(--md-sys-color-primary); }

        nav { display: flex; gap: 8px; justify-self: center; }
        .nav-item {
            font-family: var(--font-tech); font-size: 0.8rem; padding: 8px 16px; border: 1px solid var(--md-sys-color-outline); border-radius: 4px;
            color: var(--md-sys-color-on-surface); text-decoration: none; transition: all 0.3s;
        }
        .nav-item:hover { background: var(--md-sys-color-on-surface); color: var(--md-sys-color-background); }
        .header-actions { justify-self: end; display: flex; align-items: center; gap: 16px; }
        .pill { 
            border-radius: 999px; padding: 10px 24px; text-transform: uppercase; 
            font-weight: 700; text-decoration: none; display: inline-block; 
            transition: transform 0.2s; white-space: nowrap; 
            background: var(--md-sys-color-primary); color: var(--md-sys-color-on-primary);
            font-size: 0.9rem; letter-spacing: 0.5px;
        }
        .pill:hover { transform: scale(1.05); }
        .theme-toggle { background: transparent; border: 1px solid var(--md-sys-color-outline); color: var(--md-sys-color-on-surface); width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; overflow: hidden; }
        .theme-icon { width: 20px; height: 20px; position: absolute; stroke: currentColor; stroke-width: 2; fill: none; stroke-linecap: round; stroke-linejoin: round; transition: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55); }
        [data-theme="dark"] .icon-sun { opacity: 1; transform: rotate(0deg) scale(1); }
        [data-theme="dark"] .icon-moon { opacity: 0; transform: rotate(90deg) scale(0); }
        [data-theme="light"] .icon-sun { opacity: 0; transform: rotate(-90deg) scale(0); }
        [data-theme="light"] .icon-moon { opacity: 1; transform: rotate(0deg) scale(1); }

        /* HERO GRID */
        .hero-grid {
            margin-top: 80px; display: grid;
            grid-template-columns: 1.5fr 1fr 0.8fr; grid-template-rows: 60vh 30vh;
            gap: var(--grid-gap); background-color: var(--md-sys-color-outline);
            border-bottom: var(--border-width) solid var(--md-sys-color-outline);
        }
        .cell {
            background-color: var(--md-sys-color-background);
            position: relative; padding: 32px; overflow: hidden;
            display: flex; flex-direction: column; justify-content: space-between;
            transform-style: preserve-3d; perspective: 1000px;
        }

        /* === VIDEO MASK === */
        .hero-main {
            grid-column: 1 / 2; grid-row: 1 / 3; padding: 0; isolation: isolate;
            background-color: #000 !important; 
        }
        .cell-video {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover; z-index: 0;
            filter: grayscale(100%) contrast(1.2); opacity: 0.9; 
            transition: filter 0.3s;
        }
        [data-theme="light"] .cell-video { opacity: 0; }

        .mask-layer {
            position: relative; z-index: 1; width: 100%; height: 100%; padding: 32px;
            display: flex; flex-direction: column; justify-content: space-between;
            background-color: #000; 
            mix-blend-mode: multiply; 
        }

        [data-theme="light"] .hero-main { background-color: #fff !important; } 
        [data-theme="light"] .mask-layer { background-color: #fff; mix-blend-mode: normal; }
        
        .mask-content h1 {
            font-size: clamp(4rem, 9vw, 10rem); line-height: 0.85; font-weight: 800;
            text-transform: uppercase; letter-spacing: -0.04em; margin-bottom: 32px;
        }
        .mask-content h1 span { display: block; }
        .mask-content h1 span { color: #fff; -webkit-text-stroke: 0; }
        .reveal-text { color: #fff !important; }

        [data-theme="light"] .mask-content h1 span { color: transparent; -webkit-text-stroke: 2px #1a1a1a; }
        [data-theme="light"] .mask-content h1 span:nth-child(2) { color: #1a1a1a; -webkit-text-stroke: 0; }
        [data-theme="light"] .reveal-text { color: #1a1a1a !important; }

        .hero-visual { grid-column: 2 / 4; grid-row: 1 / 2; background: var(--hero-gradient); position: relative; }
        .path-graphic {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 80%; height: 80%; border: 2px solid var(--md-sys-color-primary);
            border-radius: 50% 30% 70% 40%; animation: morph 8s ease-in-out infinite;
            box-shadow: 0 0 50px rgba(255, 94, 0, 0.3); transform: translateZ(30px);
        }
        [data-theme="light"] .path-graphic { box-shadow: 0 0 30px rgba(255, 94, 0, 0.2); }
        @keyframes morph {
            0% { border-radius: 50% 30% 70% 40%; transform: translate(-50%, -50%) rotate(0deg); }
            50% { border-radius: 30% 60% 40% 60%; transform: translate(-50%, -50%) rotate(180deg); }
            100% { border-radius: 50% 30% 70% 40%; transform: translate(-50%, -50%) rotate(360deg); }
        }

        .hero-data { grid-column: 2 / 3; grid-row: 2 / 3; }
        .stat-huge { font-size: 4rem; font-weight: 300; color: var(--md-sys-color-primary); transform: translateZ(20px); }

        .hero-cta {
            grid-column: 3 / 4; grid-row: 2 / 3; background-color: var(--md-sys-color-surface);
            align-items: center; justify-content: center; transition: background 0.3s;
        }
        .hero-cta:hover { background-color: var(--md-sys-color-container); }
        .arrow-icon { font-size: 4rem; transform: rotate(-45deg); transition: transform 0.3s; }
        .hero-cta:hover .arrow-icon { transform: rotate(0deg); }

        /* Marquee */
        .marquee-container {
            position: relative; height: 150px; overflow: hidden;
            background: var(--md-sys-color-primary); display: flex; align-items: center;
            transform: skewY(-2deg); margin: 40px 0;
            border-top: 2px solid var(--md-sys-color-on-surface);
            border-bottom: 2px solid var(--md-sys-color-on-surface);
        }
        .marquee-content {
            white-space: nowrap; font-size: 3rem; font-weight: 900;
            font-family: var(--font-display); color: var(--md-sys-color-on-primary);
            animation: scroll 15s linear infinite;
        }
        @keyframes scroll { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

        /* --- SECTION 01: VERTICAL LENS --- */
        .content-section { padding: 80px 0; }
        .section-label { 
            font-family: var(--font-tech); color: var(--md-sys-color-tertiary); margin-bottom: 40px; 
            padding: 0 40px; display: block;
        }

        .lens-container {
            width: 100%; height: 90vh;
            display: flex; flex-direction: column;
            gap: 2px; border-top: 1px solid var(--md-sys-color-outline); border-bottom: 1px solid var(--md-sys-color-outline);
        }
        .lens-item {
            flex: 1; position: relative;
            background-size: cover; background-position: center;
            transition: flex 0.6s cubic-bezier(0.16, 1, 0.3, 1), filter 0.4s;
            filter: grayscale(100%) brightness(0.7);
            overflow: hidden;
            display: flex; align-items: center; justify-content: space-between; 
            padding: 0 50px;
            border-bottom: 1px solid var(--md-sys-color-outline);
            cursor: pointer;
        }
        .lens-item:last-child { border-bottom: none; }
        
        .lens-overlay {
            position: absolute; inset: 0; background: var(--md-sys-color-background); 
            opacity: 0.8; transition: 0.6s; z-index: 1;
        }
        [data-theme="light"] .lens-overlay { background-color: #000 !important; opacity: 0.5; }

        .lens-content {
            position: relative; z-index: 2; width: 100%;
            display: flex; align-items: center; justify-content: space-between;
        }
        .lens-title {
            font-family: var(--font-display); font-size: 3rem;
            color: transparent; -webkit-text-stroke: 1px var(--md-sys-color-on-surface);
            transition: 0.6s; transform-origin: left center;
        }
        .lens-meta {
            font-family: var(--font-tech); opacity: 0; transform: translateX(20px); 
            transition: 0.4s; text-align: right; color: var(--md-sys-color-on-primary);
        }
        .lens-item:hover { flex: 2; filter: grayscale(0%) brightness(1); }
        .lens-item:hover .lens-overlay { opacity: 0.2; background: #000; }
        .lens-item:hover .lens-title { 
            color: var(--md-sys-color-on-primary); -webkit-text-stroke: 0; 
            font-size: 4rem; transform: translateX(20px); 
            text-shadow: 0 0 20px rgba(0,0,0,0.5);
        }
        .lens-item:hover .lens-meta { opacity: 1; transform: translateX(0); }

        /* DIAGONAL (SECTION 02) */
        .diag-container {
            display: flex; width: 100%; height: 80vh; gap: 0;
            background: #000; overflow: hidden; border-top: 1px solid var(--md-sys-color-outline);
        }
        .diag-item {
            flex: 1; position: relative;
            background-size: cover; background-position: center;
            transition: all 0.5s cubic-bezier(0.25, 1, 0.5, 1);
            filter: grayscale(100%) brightness(0.5);
            clip-path: polygon(20% 0%, 100% 0%, 80% 100%, 0% 100%);
            margin: 0 -50px; cursor: pointer;
        }
        .diag-item:hover {
            flex: 3; filter: grayscale(0%) brightness(1); z-index: 10;
            clip-path: polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%); margin: 0 10px;
        }
        .diag-content {
            position: absolute; bottom: 40px; left: 40px; opacity: 0; transition: 0.3s;
        }
        .diag-item:hover .diag-content { opacity: 1; transition-delay: 0.2s; }
        .diag-content h2 { font-family: var(--font-display); font-size: 3rem; line-height: 0.9; color: var(--md-sys-color-primary); }

        /* BARCODE/SLICE (SECTION 03) */
        .slice-section {
            padding: 100px 0; display: flex; flex-direction: column; align-items: center;
            border-top: 1px solid var(--md-sys-color-outline);
        }
        .slice-wrapper {
            display: flex; width: 90vw; height: 70vh; align-items: center;
        }
        .slice-item {
            flex: 1; height: 100%; margin: 0 2px;
            background-size: cover; background-position: center;
            transition: flex 0.4s ease, margin 0.4s ease;
            position: relative; overflow: hidden;
            filter: grayscale(100%) contrast(1.2); cursor: crosshair;
        }
        .slice-overlay {
            position: absolute; inset: 0; background: rgba(0,0,0,0.7);
            display: flex; align-items: center; justify-content: center;
            transition: 0.4s;
        }
        .slice-overlay span { writing-mode: vertical-rl; transform: rotate(180deg); font-size: 1.5rem; letter-spacing: 5px; color: #555; font-family: var(--font-tech); }
        .slice-item:hover { flex: 5; margin: 0 10px; filter: grayscale(0%); }
        .slice-item:hover .slice-overlay { opacity: 0; }
        .slice-info {
            position: absolute; bottom: 30px; left: 30px; opacity: 0; transition: 0.3s 0.2s;
        }
        .slice-item:hover .slice-info { opacity: 1; }
        .slice-info h2 { font-family: var(--font-display); font-size: 3rem; color: var(--md-sys-color-primary); }

        /* SECTION 04: INSTAGRAM */
        .insta-section {
            padding: 100px 0; border-top: 1px solid var(--md-sys-color-outline);
            display: flex; flex-direction: column;
        }
        .insta-header {
            padding: 0 5vw 40px 5vw; display: flex; justify-content: space-between; align-items: flex-end;
        }
        .insta-grid {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px;
            background-color: var(--md-sys-color-outline); border-bottom: 1px solid var(--md-sys-color-outline);
        }
        .insta-item {
            position: relative; aspect-ratio: 1 / 1; background-color: var(--md-sys-color-background);
            overflow: hidden; display: block; cursor: pointer;
        }
        .insta-img {
            width: 100%; height: 100%; background-size: cover; background-position: center;
            filter: grayscale(100%); transition: transform 0.5s, filter 0.5s;
        }
        .insta-overlay {
            position: absolute; inset: 0; background: rgba(0,0,0,0.6);
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            opacity: 0; transition: opacity 0.3s; z-index: 2;
        }
        .insta-icon { font-family: var(--font-display); font-size: 1.5rem; color: #fff; transform: translateY(20px); transition: transform 0.4s; }
        .insta-caption { font-family: var(--font-tech); font-size: 0.8rem; color: var(--md-sys-color-primary); margin-top: 10px; text-transform: uppercase; transform: translateY(20px); transition: transform 0.4s 0.1s; }
        .insta-item:hover .insta-img { filter: grayscale(0%); transform: scale(1.1); }
        .insta-item:hover .insta-overlay { opacity: 1; }
        .insta-item:hover .insta-icon, .insta-item:hover .insta-caption { transform: translateY(0); }

        .native-section {
            padding: 100px 5vw;
            border-top: 1px solid var(--md-sys-color-outline);
            background-color: var(--md-sys-color-background); 
            display: flex; flex-direction: column; align-items: center; gap: 40px;
        }
        .native-wrapper {
            display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; width: 100%;
        }
        .instagram-media { margin: 0 auto !important; min-width: 320px !important; }

      /* --- SEÇÃO 05: LINKS RÁPIDOS (TEXTO CENTRALIZADO) --- */
.linktree-section {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 80px 20px; border-top: 1px solid var(--md-sys-color-outline);
    background: var(--md-sys-color-background);
}

.linktree-container { 
    max-width: 480px; width: 100%; text-align: center; 
}

.profile-img {
    width: 100px; height: 100px; border-radius: 50%; object-fit: cover;
    border: 2px solid var(--md-sys-color-primary); margin-bottom: 15px;
    filter: grayscale(100%); transition: 0.3s;
}
.linktree-container:hover .profile-img { filter: grayscale(0%); }

.profile-title {
    font-family: var(--font-display); font-weight: 900; font-size: 1.5rem; 
    letter-spacing: -0.02em; margin-bottom: 5px; color: var(--md-sys-color-on-surface);
}

.profile-slogan {
    font-family: var(--font-tech); font-size: 0.9rem; color: var(--md-sys-color-primary);
    margin-bottom: 30px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9;
}

/* --- BOTÕES --- */
.link-btn {
    position: relative; /* Necessário para o ícone absoluto funcionar */
    display: flex; 
    align-items: center; 
    justify-content: center; /* Centraliza o conteúdo horizontalmente */
    width: 100%; 
    padding: 16px 60px; /* Padding lateral maior para o texto não bater no ícone */
    margin-bottom: 12px;
    background: var(--md-sys-color-container);
    border: 1px solid transparent;
    border-radius: 50px;
    color: var(--md-sys-color-on-surface); text-decoration: none;
    transition: all 0.3s cubic-bezier(0.19, 1, 0.22, 1); 
    min-height: 64px; /* Garante altura uniforme */
}

.link-btn:hover {
    background: var(--md-sys-color-outline);
    border-color: var(--md-sys-color-outline);
    transform: scale(1.01);
}

/* --- ÍCONES (Fixos na esquerda) --- */
.btn-icon-svg, .icon-circle {
    position: absolute; /* Tira o ícone do fluxo e fixa ele */
    left: 20px; /* Distância fixa da esquerda */
    top: 50%;
    transform: translateY(-50%); /* Centraliza verticalmente */
}

.btn-icon-svg {
    width: 24px; height: 24px; fill: currentColor;
}

.icon-circle {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
}
.icon-circle svg { width: 18px; height: 18px; }

/* Cores dos Ícones Especiais */
.bg-white { background-color: #ffffff; color: #000000; }
.bg-pink { background-color: #ff4081; color: #ffffff; }

/* --- TEXTOS (Centralizados) --- */
.btn-content {
    display: flex; 
    flex-direction: column; 
    align-items: center; /* Centraliza itens flex (título e sub) */
    text-align: center; /* Centraliza o texto em si */
    width: 100%;
}

.btn-title {
    font-family: var(--font-display); font-weight: 700; font-size: 1rem; line-height: 1.2;
}
.btn-sub {
    font-family: var(--font-tech); font-size: 0.75rem; opacity: 0.6; margin-top: 4px;
}

.link-btn::after { display: none; }

        /* IMAGENS GERAIS */
        .li1 { background-image: url('https://images.pexels.com/photos/3764014/pexels-photo-3764014.jpeg?auto=compress&cs=tinysrgb&w=1260'); }
        .li2 { background-image: url('https://images.pexels.com/photos/235922/pexels-photo-235922.jpeg?auto=compress&cs=tinysrgb&w=1260'); }
        .li3 { background-image: url('https://images.pexels.com/photos/40751/running-runner-long-distance-fitness-40751.jpeg?auto=compress&cs=tinysrgb&w=1260'); }
        
        .di1 { background-image: url('https://images.pexels.com/photos/235922/pexels-photo-235922.jpeg?auto=compress&cs=tinysrgb&w=600'); }
        .di2 { background-image: url('https://images.pexels.com/photos/2526878/pexels-photo-2526878.jpeg?auto=compress&cs=tinysrgb&w=600'); }
        .di3 { background-image: url('https://images.pexels.com/photos/3764014/pexels-photo-3764014.jpeg?auto=compress&cs=tinysrgb&w=600'); }
        .di4 { background-image: url('https://images.pexels.com/photos/2402777/pexels-photo-2402777.jpeg?auto=compress&cs=tinysrgb&w=600'); }

        .si1 { background-image: url('https://images.pexels.com/photos/3763073/pexels-photo-3763073.jpeg?auto=compress&cs=tinysrgb&w=600'); }
        .si2 { background-image: url('https://images.pexels.com/photos/2526878/pexels-photo-2526878.jpeg?auto=compress&cs=tinysrgb&w=600'); }
        .si3 { background-image: url('https://images.pexels.com/photos/2402777/pexels-photo-2402777.jpeg?auto=compress&cs=tinysrgb&w=600'); }
        .si4 { background-image: url('https://images.pexels.com/photos/3764014/pexels-photo-3764014.jpeg?auto=compress&cs=tinysrgb&w=600'); }
        .si5 { background-image: url('https://images.pexels.com/photos/40751/running-runner-long-distance-fitness-40751.jpeg?auto=compress&cs=tinysrgb&w=600'); }

        /* Footer Centralizado */
footer {
    border-top: 1px solid var(--md-sys-color-outline); 
    padding: 80px 20px;
    margin-top: 0; 
    
    /* MUDANÇA: Flexbox para centralizar tudo */
    display: flex; 
    flex-wrap: wrap; /* Permite quebrar linha se a tela for pequena */
    justify-content: center; /* Centraliza horizontalmente */
    gap: 60px; /* Espaço entre as colunas */
    text-align: center; /* Centraliza o texto */
}

.footer-col {
    display: flex;
    flex-direction: column;
    align-items: center; /* Centraliza os links e ícones */
    min-width: 140px; /* Tamanho mínimo para não espremer */
}

.footer-col h4 { 
    font-family: var(--font-tech); 
    color: var(--md-sys-color-tertiary); 
    margin-bottom: 20px; 
    text-transform: uppercase; 
    font-size: 0.9rem;
    letter-spacing: 1px;
}

.footer-col a {
    display: flex; /* Para alinhar ícone e texto */
    align-items: center;
    justify-content: center;
    gap: 8px; /* Espaço entre ícone e texto */
    color: var(--md-sys-color-on-surface); 
    text-decoration: none;
    font-size: 1rem; 
    margin-bottom: 12px; 
    transition: color 0.2s;
    opacity: 0.8;
}

.footer-col a:hover { 
    color: var(--md-sys-color-primary); 
    opacity: 1;
}

/* Garante que o Logo Gigante fique na linha de baixo e centralizado */
.big-logo-footer {
    width: 100%; /* Força quebra de linha */
    font-size: 15vw; 
    line-height: 0.8; 
    font-weight: 900;
    text-align: center; 
    color: var(--md-sys-color-container); 
    margin-top: 40px; 
    user-select: none;
    order: 10; /* Garante que fique por último */
}

        /* MEDIA QUERIES GERAIS */
        @media (max-width: 900px) {
            header { grid-template-columns: auto 1fr auto; gap: 10px; }
            .brand { font-size: 1.2rem; }
            nav { display: none; }
            .hero-grid { grid-template-columns: 1fr; grid-template-rows: auto; }
            .hero-main { grid-column: 1; grid-row: 1; }
            .hero-visual { grid-column: 1; grid-row: 2; height: 300px; }
            .hero-data { grid-column: 1; grid-row: 3; }
            .hero-cta { grid-column: 1; grid-row: 4; padding: 40px;}
           /* Novo comportamento do Footer no Mobile */
    footer { 
        flex-direction: column; /* Empilha verticalmente */
        gap: 40px;
    }
            
            .lens-container { height: auto; }
            .lens-item { height: 150px; flex: none; }
            .lens-item:hover { height: 250px; }
            .lens-title { font-size: 2rem; }
            .lens-item:hover .lens-title { font-size: 3rem; }

            .insta-grid { grid-template-columns: repeat(2, 1fr); }
            .insta-header { flex-direction: column; align-items: flex-start; gap: 20px; }
            .native-wrapper { flex-direction: column; align-items: center; }
        }

        /* --- CORREÇÕES AGRESSIVAS PARA MOBILE / TOUCH --- */
        @media screen and (max-width: 1024px), (hover: none), (pointer: coarse) {
            /* 1. Mata o cursor customizado */
            .cursor-dot, .cursor-outline { display: none !important; visibility: hidden !important; pointer-events: none !important; }
            * { cursor: auto !important; }
            .hover-trigger:hover { transform: none !important; }

            /* 2. Força imagens da Seção 02 (Diagonal) a aparecerem empilhadas */
            .diag-container { flex-direction: column; height: auto !important; display: flex; }
            .diag-item { flex: none !important; width: 100% !important; height: 250px !important; margin: 0; clip-path: none !important; border-bottom: 1px solid var(--md-sys-color-outline); }
            
            /* 3. Força imagens da Seção 03 (Slice) a aparecerem empilhadas */
            .slice-wrapper { flex-direction: column; height: auto !important; width: 100%; display: flex; }
            .slice-item { width: 100% !important; height: 180px !important; margin: 1px 0 !important; flex: none !important; }
            .slice-overlay span { transform: rotate(0); writing-mode: horizontal-tb; }
        }

        /* --- MODAL LOGIN ESPECÍFICO --- */
.modal-login {
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.85); backdrop-filter: blur(5px);
    align-items: center; justify-content: center; z-index: 10000;
    opacity: 0; transition: opacity 0.3s; pointer-events: none;
}

/* --- CORREÇÃO DO CURSOR NO MODAL --- */
/* Força o aparecimento da setinha padrão dentro do modal */
.modal-login, .modal-login * {
    cursor: auto !important;
    }

.modal-login.active { display: flex; opacity: 1; pointer-events: all; }

.login-card {
    background: rgba(18, 18, 18, 0.95);
    padding: 60px 40px;
    border: 1px solid var(--md-sys-color-outline);
    width: 90%; max-width: 400px;
    text-align: center; position: relative;
    box-shadow: 0 0 50px rgba(0,0,0,0.8);
}

/* Cantos Tecnológicos (Do seu login.php) */
.login-card::before, .login-card::after {
    content: ""; position: absolute; width: 10px; height: 10px;
    border: 2px solid var(--md-sys-color-primary); transition: 0.3s;
}
.login-card::before { top: -1px; left: -1px; border-right: 0; border-bottom: 0; }
.login-card::after { bottom: -1px; right: -1px; border-left: 0; border-top: 0; }

.login-title { 
    font-family: var(--font-display); font-weight: 900; font-size: 2rem;
    color: var(--md-sys-color-on-surface); text-transform: uppercase; margin-bottom: 10px;
}
.login-title span { color: var(--md-sys-color-primary); }

.login-subtitle {
    font-family: var(--font-tech); font-size: 0.75rem; color: var(--md-sys-color-primary);
    margin-bottom: 40px; opacity: 0.8; letter-spacing: 1px;
}

.login-group { margin-bottom: 25px; text-align: left; }
.login-group label { 
    display: block; margin-bottom: 8px; font-family: var(--font-tech); 
    font-size: 0.7rem; text-transform: uppercase; color: #888;
}
.login-group input {
    width: 100%; padding: 15px; background: #0a0a0a;
    border: 1px solid var(--md-sys-color-outline); color: #fff;
    font-family: var(--font-tech); font-size: 0.9rem; transition: all 0.3s;
}
.login-group input:focus { outline: none; border-color: var(--md-sys-color-primary); }

.btn-login-submit {
    width: 100%; padding: 16px; background: var(--md-sys-color-primary);
    color: #000; border: none; font-family: var(--font-display);
    font-weight: 800; font-size: 1rem; cursor: pointer; text-transform: uppercase;
    margin-top: 10px; transition: transform 0.2s;
}
.btn-login-submit:hover { transform: scale(1.02); background: #fff; }

.login-error-msg {
    color: #ff4444; font-family: var(--font-tech); font-size: 0.75rem; 
    margin-bottom: 20px; padding: 10px; border: 1px solid #ff4444; background: rgba(255, 68, 68, 0.1);
}

/* Ícone de Segurança no Modal */
.login-icon-container {
    width: 80px; height: 80px;
    margin: 0 auto 25px auto; /* Centraliza e dá espaço embaixo */
    display: flex; align-items: center; justify-content: center;
    border: 2px solid var(--md-sys-color-primary);
    border-radius: 50%; /* Círculo perfeito */
    background: rgba(255, 94, 0, 0.05); /* Fundo sutil */
    color: var(--md-sys-color-primary);
    box-shadow: 0 0 20px rgba(255, 94, 0, 0.2); /* Brilho neon */
    transition: all 0.3s ease;
}

.login-icon-container:hover {
    box-shadow: 0 0 30px rgba(255, 94, 0, 0.4);
    transform: scale(1.05);
}

.login-icon-svg {
    width: 36px; height: 36px;
    fill: currentColor;
}

.close-modal-btn {
    position: absolute; top: 20px; right: 20px; background: none; border: none;
    color: #666; font-family: var(--font-tech); cursor: pointer; font-size: 1.2rem;
}
.close-modal-btn:hover { color: #fff; }

/* --- ESTILO BIOMETRIA / SCANNER --- */
/* --- ESTILO BIOMETRIA / SCANNER (CORRIGIDO) --- */
.login-icon-box {
    width: 80px; height: 80px; 
    margin: 0 auto 25px auto;
    display: flex; align-items: center; justify-content: center;
    position: relative; 
    transition: all 0.3s ease;
}

.fingerprint-style {
    border: 1px solid var(--md-sys-color-primary);
    border-radius: 12px;
    background: rgba(255, 94, 0, 0.05);
    box-shadow: 0 0 20px rgba(255, 94, 0, 0.1); /* Brilho ajustado */
    overflow: hidden; /* Importante para a linha não sair da caixa */
    position: relative;
}

.fingerprint-style:hover {
    box-shadow: 0 0 25px rgba(255, 94, 0, 0.3);
    transform: scale(1.05);
    border-color: #fff; /* Detalhe visual ao passar o mouse */
}

/* Ícone da Digital */
.fingerprint-style .icon-svg { 
    width: 45px; height: 45px; /* Tamanho ajustado conforme exemplo */
    fill: var(--md-sys-color-primary); 
    opacity: 0.9; 
}

/* Linha de Scan */
.scan-line {
    position: absolute; width: 100%; height: 2px;
    background: var(--md-sys-color-primary);
    box-shadow: 0 0 10px var(--md-sys-color-primary);
    top: 0; left: 0;
    /* Animação idêntica ao exemplo */
    animation: scanAnim 2.5s infinite ease-in-out; 
}

@keyframes scanAnim {
    0% { top: -10%; opacity: 0; }
    15% { opacity: 1; }
    85% { opacity: 1; }
    100% { top: 110%; opacity: 0; }
}

/* --- CAMPO DE SENHA COM OLHO --- */
.password-wrapper {
    position: relative;
    width: 100%;
}

.password-wrapper input {
    width: 100%;
    padding-right: 45px; /* Espaço para o ícone não ficar em cima do texto */
}

.toggle-password-btn {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    padding: 5px;
    cursor: pointer;
    color: #666; /* Cor padrão */
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.3s;
}

.toggle-password-btn:hover {
    color: var(--md-sys-color-primary); /* Fica laranja ao passar o mouse */
}

.toggle-password-btn svg {
    width: 20px;
    height: 20px;
    fill: currentColor;
}

    </style>
</head>
<body>

    <div class="noise-overlay"></div>
    <div class="cursor-dot"></div>
    <div class="cursor-outline"></div>
    
    <div class="loader-overlay">
        <div class="heartbeat-wrapper">
            <div class="loader-text">RISE<span>RUNNING</span></div>
            <svg class="ecg-svg" viewBox="0 0 600 100" preserveAspectRatio="none">
                <path class="heart-path" d="M0,50 L200,50 L220,20 L240,80 L260,50 L300,50 L310,10 L340,90 L360,50 L600,50" />
            </svg>
        </div>
    </div>
    
    <header>
        <a href="#" class="brand">
            RISE<span>RUNNING</span>
        </a>
        <nav>
            <a href="#" class="nav-item hover-trigger">INDEX</a>
            <a href="#" class="nav-item hover-trigger">PACE</a>
            <a href="#" class="nav-item hover-trigger">CREW</a>
        </nav>
        <div class="header-actions">
    <a href="https://chat.whatsapp.com/DAlEyNXs6ON2E91X0yulXd?mode=wwc" class="pill filled hover-trigger">JOIN CLUB</a>
    
    <button id="theme-toggle" class="theme-toggle hover-trigger">
        <svg class="theme-icon icon-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
        <svg class="theme-icon icon-moon" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
    </button>

    <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true): ?>
        
        <a href="admin/dashboard.php" class="theme-toggle hover-trigger" title="Ir para o Painel">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="4" y1="21" x2="4" y2="14"></line>
                <line x1="4" y1="10" x2="4" y2="3"></line>
                <line x1="12" y1="21" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12" y2="3"></line>
                <line x1="20" y1="21" x2="20" y2="16"></line>
                <line x1="20" y1="12" x2="20" y2="3"></line>
                <line x1="1" y1="14" x2="7" y2="14"></line>
                <line x1="9" y1="8" x2="15" y2="8"></line>
                <line x1="17" y1="16" x2="23" y2="16"></line>
            </svg>
        </a>

    <?php else: ?>

        <button onclick="openLoginModal()" class="theme-toggle hover-trigger" title="Acesso Admin">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </button>

    <?php endif; ?>
</div>
    </header>

    <main>
        <section class="hero-grid">
            
            <div class="cell hero-main tilt-card hover-trigger">
                <video class="cell-video" autoplay muted loop playsinline poster="https://images.pexels.com/photos/235922/pexels-photo-235922.jpeg">
                    <source src="https://videos.pexels.com/video-files/2359656/2359656-hd_1920_1080_30fps.mp4" type="video/mp4">
                </video>

                <div class="mask-layer">
                    <div class="mask-content" style="height: 100%; display: flex; flex-direction: column; justify-content: space-between;">
                        <div class="mono reveal-text">/// EST. 2025 - SÃO PAULO</div>
                        <h1>
                            <span>DESTRUA</span>
                            <span>SEUS</span>
                            <span>LIMITES</span>
                        </h1>
                        <p class="reveal-text" style="max-width: 400px; font-size: 1.1rem; line-height: 1.6;">
                            Não corremos para fugir. Corremos para encontrar nossa versão mais forte.
                        </p>
                    </div>
                </div>
            </div>

            <div class="cell hero-visual tilt-card hover-trigger">
                <div class="path-graphic"></div>
                <div class="mono" style="position: absolute; bottom: 20px; left: 20px; transform: translateZ(20px);">
                    ELEVATION: 840M<br>HR: 165 BPM
                </div>
            </div>

            <div class="cell hero-data tilt-card hover-trigger">
                <span class="mono">MÉDIA PACE</span>
                <div class="stat-huge">4'12"</div>
                <div style="height: 4px; background: var(--md-sys-color-outline); width: 100%; margin-top: 10px; border-radius: 2px;">
                    <div style="height: 100%; background: var(--md-sys-color-primary); width: 75%;"></div>
                </div>
            </div>

            <div class="cell hero-cta tilt-card hover-trigger">
                <span class="arrow-icon">→</span>
                <span class="mono" style="margin-top: 10px;">START NOW</span>
            </div>
        </section>

        <div class="marquee-container">
            <div class="marquee-content">
                CORRIDA DE RUA /// SEMPRE EM FRENTE /// NO PAIN NO GAIN /// RISE UP /// SÃO PAULO RUNNERS ///
            </div>
        </div>

        <section class="content-section">
            <span class="section-label">01 // PROTOCOLOS DE TREINO</span>
            <div class="lens-container">
                <div class="lens-item li1 hover-trigger">
                    <div class="lens-overlay"></div>
                    <div class="lens-content">
                        <div class="lens-title">BASE BUILDER</div>
                        <div class="lens-meta">
                            <div class="mono">8 SEMANAS</div>
                            <div class="pill" style="margin-top:10px;">INICIANTE</div>
                        </div>
                    </div>
                </div>
                <div class="lens-item li2 hover-trigger">
                    <div class="lens-overlay"></div>
                    <div class="lens-content">
                        <div class="lens-title">SPEED DEMON</div>
                        <div class="lens-meta">
                            <div class="mono">12 SEMANAS</div>
                            <div class="pill" style="margin-top:10px; background: var(--md-sys-color-tertiary); color:#000;">AVANÇADO</div>
                        </div>
                    </div>
                </div>
                <div class="lens-item li3 hover-trigger">
                    <div class="lens-overlay"></div>
                    <div class="lens-content">
                        <div class="lens-title">LONG RUN</div>
                        <div class="lens-meta">
                            <div class="mono">DOMINGOS</div>
                            <div class="pill" style="margin-top:10px; background: #fff; color:#000;">COMUNIDADE</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="content-section" style="padding-top: 0;">
            <span class="section-label">02 // COLEÇÃO 2025</span>
            <div class="diag-container">
                <div class="diag-item di1 hover-trigger"><div class="diag-content"><h2>URBAN<br>RUN</h2></div></div>
                <div class="diag-item di2 hover-trigger"><div class="diag-content"><h2>NIGHT<br>SQUAD</h2></div></div>
                <div class="diag-item di3 hover-trigger"><div class="diag-content"><h2>TRAIL<br>BLAZER</h2></div></div>
                <div class="diag-item di4 hover-trigger"><div class="diag-content"><h2>TRACK<br>STAR</h2></div></div>
            </div>
        </section>

        <section class="slice-section">
            <span class="section-label" style="margin-bottom: 20px;">03 // CREW & FEATURES</span>
            <div class="slice-wrapper">
                <div class="slice-item si1 hover-trigger"><div class="slice-overlay"><span>01</span></div><div class="slice-info"><h2>SPRINT</h2></div></div>
                <div class="slice-item si2 hover-trigger"><div class="slice-overlay"><span>02</span></div><div class="slice-info"><h2>JOG</h2></div></div>
                <div class="slice-item si3 hover-trigger"><div class="slice-overlay"><span>03</span></div><div class="slice-info"><h2>HIIT</h2></div></div>
                <div class="slice-item si4 hover-trigger"><div class="slice-overlay"><span>04</span></div><div class="slice-info"><h2>REST</h2></div></div>
                <div class="slice-item si5 hover-trigger"><div class="slice-overlay"><span>05</span></div><div class="slice-info"><h2>REPEAT</h2></div></div>
            </div>
        </section>

        <section class="insta-section">
            <div class="insta-header">
                <div>
                    <span class="section-label" style="padding:0; margin-bottom:10px;">04 // COMMUNITY</span>
                    <h2 style="font-family: var(--font-display); font-size: 3rem; line-height:1;">FOLLOW THE<br>MOVEMENT</h2>
                </div>
                <a href="https://www.instagram.com/_riserunning/" class="pill hover-trigger">@_RISERUNNING</a>
            </div>
           
            <div class="native-wrapper">
                <?php
                $communityFile = 'data/community.json';
                $communityPosts = [];
                if (file_exists($communityFile)) {
                    $communityPosts = json_decode(file_get_contents($communityFile), true);
                }

                if (!empty($communityPosts)) {
                    foreach ($communityPosts as $post) {
                        // Check visibility (default to true)
                        if (isset($post['visible']) && $post['visible'] === false) continue;
                        
                        echo $post['embed_code'];
                    }
                } else {
                   // Fallback visual se não tiver posts
                   echo '<div style="color:#666; font-family: var(--font-tech);">/// NENHUM POST RECENTE</div>';
                }
                ?>
                <script async src="//www.instagram.com/embed.js"></script>
            </div>
        </section>

        <section class="linktree-section">
    <span class="section-label" style="margin-bottom: 30px;">05 // LINKS RÁPIDOS</span>
    
    <div class="linktree-container">
        <img src="https://images.pexels.com/photos/235922/pexels-photo-235922.jpeg?auto=compress&cs=tinysrgb&w=300" alt="Rise Profile" class="profile-img hover-trigger">
        
        <h2 class="profile-title">RISE RUNNING CLUB</h2>
        <p class="profile-slogan">O CORRE NÃO PARA!</p>

        <?php
        $sectionsFile = 'data/sections.json';
        $sections = [];
        if (file_exists($sectionsFile)) {
            $sections = json_decode(file_get_contents($sectionsFile), true);
        }
        if (!empty($sections)):
            foreach ($sections as $s):
                // Check visibility (default to true if not set)
                if (isset($s['visible']) && $s['visible'] === false) continue;
        ?>
        <a href="<?php echo htmlspecialchars($s['link']); ?>" target="_blank" class="link-btn hover-trigger">
            <?php if (!empty($s['image'])): ?>
                <div class="icon-circle" style="overflow:hidden; background:transparent; left: 20px; position: absolute; width: 32px; height: 32px; display: flex; justify-content: center; align-items: center;">
                     <img src="<?php echo htmlspecialchars($s['image']); ?>" style="width:100%; height:100%; object-fit:contain;">
                </div>
            <?php else: ?>
                 <div class="icon-circle" style="background: var(--md-sys-color-on-surface); color: var(--md-sys-color-background);">
                    <svg viewBox="0 0 24 24" fill="currentColor" style="width:18px;height:18px;"><path d="M14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3m-2 16H5V5h7V3H5c-1.11 0-2 .89-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7z"/></svg>
                 </div>
            <?php endif; ?>
            <div class="btn-content">
                <span class="btn-title"><?php echo htmlspecialchars($s['title']); ?></span>
                <?php if (!empty($s['subtitle'])): ?>
                <span class="btn-sub"><?php echo htmlspecialchars($s['subtitle']); ?></span>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; endif; ?>
    </div>
        </section>

        <footer>
            
            <div class="footer-col">
                <h4>Social</h4>
                <a href="https://www.instagram.com/_riserunning/" class="hover-trigger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-instagram" viewBox="0 0 16 16">
  <path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.9 3.9 0 0 0-1.417.923A3.9 3.9 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.9 3.9 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.9 3.9 0 0 0-.923-1.417A3.9 3.9 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599s.453.546.598.92c.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.5 2.5 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.5 2.5 0 0 1-.92-.598 2.5 2.5 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233s.008-2.388.046-3.231c.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92s.546-.453.92-.598c.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92m-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217m0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334"/>
</svg> Instagram</a>
                <a href="https://www.tiktok.com/@riseclub_" class="hover-trigger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-tiktok" viewBox="0 0 16 16">
  <path d="M9 0h1.98c.144.715.54 1.617 1.235 2.512C12.895 3.389 13.797 4 15 4v2c-1.753 0-3.07-.814-4-1.829V11a5 5 0 1 1-5-5v2a3 3 0 1 0 3 3z"/>
</svg> TikTok</a>
                <a href="https://strava.app.link/7C00avDhoUb" class="hover-trigger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-strava" viewBox="0 0 16 16">
  <path d="M6.731 0 2 9.125h2.788L6.73 5.497l1.93 3.628h2.766zm4.694 9.125-1.372 2.756L8.66 9.125H6.547L10.053 16l3.484-6.875z"/>
</svg> Strava</a>
            </div>
            
            <div class="footer-col">
                <h4>Comunidade</h4>
                <a href="https://chat.whatsapp.com/DAlEyNXs6ON2E91X0yulXd?mode=wwc" class="hover-trigger">
                    
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
  <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/>
</svg>
Rise Club</a>
                <a href="https://chat.whatsapp.com/HVxghPEHXd5AG69VVdW7kw" class="hover-trigger">
                    
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
  <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/>
</svg>
Rise Girls</a>    
            </div>

            <div class="footer-col">
                <h4>Legal</h4>
                <a href="#" class="hover-trigger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list-task" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M2 2.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5V3a.5.5 0 0 0-.5-.5zM3 3H2v1h1z"/>
  <path d="M5 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M5.5 7a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1zm0 4a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1z"/>
  <path fill-rule="evenodd" d="M1.5 7a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H2a.5.5 0 0 1-.5-.5zM2 7h1v1H2zm0 3.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5zm1 .5H2v1h1z"/>
</svg> Termos</a>
                <a href="#" class="hover-trigger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-incognito" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="m4.736 1.968-.892 3.269-.014.058C2.113 5.568 1 6.006 1 6.5 1 7.328 4.134 8 8 8s7-.672 7-1.5c0-.494-1.113-.932-2.83-1.205l-.014-.058-.892-3.27c-.146-.533-.698-.849-1.239-.734C9.411 1.363 8.62 1.5 8 1.5s-1.411-.136-2.025-.267c-.541-.115-1.093.2-1.239.735m.015 3.867a.25.25 0 0 1 .274-.224c.9.092 1.91.143 2.975.143a30 30 0 0 0 2.975-.143.25.25 0 0 1 .05.498c-.918.093-1.944.145-3.025.145s-2.107-.052-3.025-.145a.25.25 0 0 1-.224-.274M3.5 10h2a.5.5 0 0 1 .5.5v1a1.5 1.5 0 0 1-3 0v-1a.5.5 0 0 1 .5-.5m-1.5.5q.001-.264.085-.5H2a.5.5 0 0 1 0-1h3.5a1.5 1.5 0 0 1 1.488 1.312 3.5 3.5 0 0 1 2.024 0A1.5 1.5 0 0 1 10.5 9H14a.5.5 0 0 1 0 1h-.085q.084.236.085.5v1a2.5 2.5 0 0 1-5 0v-.14l-.21-.07a2.5 2.5 0 0 0-1.58 0l-.21.07v.14a2.5 2.5 0 0 1-5 0zm8.5-.5h2a.5.5 0 0 1 .5.5v1a1.5 1.5 0 0 1-3 0v-1a.5.5 0 0 1 .5-.5"/>
</svg> Privacidade</a>
            </div>

            <div class="big-logo-footer">RISE RUNNING</div>
        </footer>

    </main>

<div id="modalLogin" class="modal-login">
    <div class="login-card">
        <button class="close-modal-btn" onclick="closeLoginModal()">X</button>
        
        <div class="login-icon-box fingerprint-style">
    <svg viewBox="0 0 24 24" class="icon-svg">
        <path d="M17.81 4.47c-.08 0-.16-.02-.23-.06C15.66 3.42 14 3 12.01 3c-1.98 0-3.86.47-5.57 1.41-.24.13-.54.04-.68-.2-.13-.24-.04-.55.2-.68C7.82 2.52 9.86 2 12.01 2c2.13 0 3.99.47 6.03 1.52.25.13.34.43.21.67-.09.18-.26.28-.44.28zM3.5 9.72c-.1 0-.2-.03-.29-.09-.23-.16-.28-.47-.12-.7.99-1.4 2.25-2.5 3.75-3.27C9.98 4.04 14 4.03 17.15 5.65c1.5.77 2.76 1.86 3.75 3.25.16.22.11.54-.12.7-.23.16-.54.11-.7-.12-.9-1.26-2.04-2.25-3.39-2.94-2.87-1.47-6.54-1.47-9.4.01-1.36.7-2.5 1.7-3.4 2.96-.08.14-.23.21-.39.21zm6.25 12.07c-.1 0-.26-.05-.35-.15-.87-.87-1.34-1.43-2.01-2.64-.69-1.23-1.05-2.73-1.05-4.34 0-2.97 2.54-5.39 5.66-5.39s5.66 2.42 5.66 5.39c0 .28-.22.5-.5.5s-.5-.22-.5-.5c0-2.42-2.09-4.39-4.66-4.39-2.57 0-4.66 1.97-4.66 4.39 0 1.44.32 2.77.93 3.85.64 1.15 1.08 1.64 1.85 2.42.19.2.19.51 0 .71-.11.1-.24.15-.37.15zm7.17-1.85c-1.19 0-2.24-.3-3.1-.89-1.49-1.01-2.38-2.65-2.38-4.39 0-.28.22-.5.5-.5s.5.22.5.5c0 1.41.72 2.74 1.94 3.56.71.48 1.54.71 2.54.71.24 0 .64-.03 1.04-.1.27-.05.53.13.58.41.05.27-.13.53-.41.58-.57.11-1.07.12-1.21.12zM14.91 22c-.04 0-.09-.01-.13-.02-1.59-.44-2.63-1.03-3.72-2.1-1.4-1.39-2.17-3.24-2.17-5.22 0-1.62 1.38-2.94 3.08-2.94 1.7 0 3.08 1.32 3.08 2.94 0 1.07.93 1.94 2.08 1.94s2.08-.87 2.08-1.94c0-3.77-3.25-6.83-7.25-6.83-2.84 0-5.44 1.58-6.61 4.03-.39.81-.59 1.76-.59 2.8 0 .78.07 2.01.67 3.61.1.26-.03.55-.29.64-.26.1-.55-.03-.64-.29-.49-1.31-.73-2.61-.73-3.96 0-1.2.23-2.29.68-3.24 1.33-2.79 4.28-4.6 7.51-4.6 4.55 0 8.25 3.51 8.25 7.83 0 1.62-1.38 2.94-3.08 2.94s-3.08-1.32-3.08-2.94c0-1.07-.93-1.94-2.08-1.94s-2.08.87-2.08 1.94c0 1.71.66 3.31 1.87 4.51.95.94 1.86 1.46 3.27 1.85.27.07.42.35.35.61-.05.23-.26.38-.47.38z"/>
    </svg>
    <div class="scan-line"></div>
</div>

        <h2 class="login-title">Rise<span>Admin</span></h2>
        <div class="login-subtitle">/// SISTEMA DE CONTROLE</div>
        
        <?php if (!empty($login_error)): ?>
            <div class="login-error-msg"><?php echo htmlspecialchars($login_error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="do_login">
            
            <div class="login-group">
                <label>ID DE USUÁRIO</label>
                <input type="text" name="username" required autocomplete="off">
            </div>
            <div class="login-group">
                <label>CHAVE DE ACESSO</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="passInput" required>
                    
                    <button type="button" class="toggle-password-btn" id="toggleBtn" onclick="togglePassword()">
                        <svg viewBox="0 0 24 24" id="eyeIcon">
                            <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 2.98-.33 4.28-.9l.46.46L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                        </svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-login-submit">ENTRAR NO SISTEMA</button>
        </form>
    </div>
</div>

    <script>
        // 1. PRELOADER
        setTimeout(() => {
            document.querySelector('.loader-overlay').classList.add('hidden');
        }, 2600);

        // 2. CURSOR
        const dot = document.querySelector('.cursor-dot');
        const outline = document.querySelector('.cursor-outline');
        let mouseX = 0, mouseY = 0;
        let outlineX = 0, outlineY = 0;

        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX; mouseY = e.clientY;
            dot.style.left = mouseX + 'px'; dot.style.top = mouseY + 'px';
            dot.style.transform = `translate(-50%, -50%)`;
        });

        function animateCursor() {
            const dt = 0.15;
            outlineX += (mouseX - outlineX) * dt;
            outlineY += (mouseY - outlineY) * dt;
            outline.style.left = outlineX + 'px';
            outline.style.top = outlineY + 'px';
            outline.style.transform = `translate(-50%, -50%)`;
            requestAnimationFrame(animateCursor);
        }
        animateCursor();

        document.querySelectorAll('.hover-trigger').forEach(el => {
            el.addEventListener('mouseenter', () => document.body.classList.add('hovering'));
            el.addEventListener('mouseleave', () => document.body.classList.remove('hovering'));
        });

        // 3. THEME TOGGLE
        const toggleButton = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;
        toggleButton.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            htmlElement.setAttribute('data-theme', currentTheme === 'dark' ? 'light' : 'dark');
        });

        // 4. 3D TILT
        document.querySelectorAll('.tilt-card').forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const rotateX = ((y - rect.height/2) / (rect.height/2)) * -5;
                const rotateY = ((x - rect.width/2) / (rect.width/2)) * 5;
                card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
            });
            card.addEventListener('mouseleave', () => {
                card.style.transform = `perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)`;
            });
        });

        // --- LÓGICA DO MODAL DE LOGIN ---
const modalLogin = document.getElementById('modalLogin');

function openLoginModal() {
    modalLogin.classList.add('active');
}

function closeLoginModal() {
    modalLogin.classList.remove('active');
}

// Fechar ao clicar fora
modalLogin.addEventListener('click', (e) => {
    if (e.target === modalLogin) closeLoginModal();
});

// Reabrir automaticamente se houve erro de login (PHP)
<?php if ($show_login_modal): ?>
    openLoginModal();
<?php endif; ?>

// --- FUNÇÃO MOSTRAR/OCULTAR SENHA ---
    function togglePassword() {
        const passInput = document.getElementById('passInput');
        const eyeIcon = document.getElementById('eyeIcon');
        
        // Paths dos ícones (SVG)
        const pathEyeOpen = 'M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z';
        const pathEyeClosed = 'M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 2.98-.33 4.28-.9l.46.46L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z';

        if (passInput.type === 'password') {
            passInput.type = 'text';
            eyeIcon.querySelector('path').setAttribute('d', pathEyeOpen); // Muda para olho aberto
        } else {
            passInput.type = 'password';
            eyeIcon.querySelector('path').setAttribute('d', pathEyeClosed); // Muda para olho fechado
        }
    }

    </script>
</body>
</html>