<?php
require_once 'auth.php';
checkAuth();

$sections = getSections();
$community = getCommunity();

// --- AJAX HANDLER (SEM RELOAD) ---
if (isset($_GET['ajax_action'])) {
    header('Content-Type: application/json');
    
    $action = $_GET['ajax_action'];
    $id = $_GET['id'];
    $newState = true;

    if ($action === 'toggle_section') {
        foreach ($sections as &$s) {
            if ($s['id'] === $id) {
                $current = isset($s['visible']) ? $s['visible'] : true;
                $s['visible'] = !$current;
                $newState = $s['visible'];
                break;
            }
        }
        saveSections($sections);
    }
    elseif ($action === 'toggle_community') {
        foreach ($community as &$c) {
            if ($c['id'] === $id) {
                $current = isset($c['visible']) ? $c['visible'] : true;
                $c['visible'] = !$current;
                $newState = $c['visible'];
                break;
            }
        }
        saveCommunity($community);
    }

    echo json_encode(['success' => true, 'visible' => $newState]);
    exit;
}

// --- PROCESSAR EXCLUSÕES ---
if (isset($_GET['delete_section'])) {
    $id = $_GET['delete_section'];
    $sections = array_filter($sections, function($s) use ($id) { return $s['id'] !== $id; });
    saveSections(array_values($sections));
    header('Location: dashboard.php');
    exit;
}

if (isset($_GET['delete_community'])) {
    $id = $_GET['delete_community'];
    $community = array_filter($community, function($c) use ($id) { return $c['id'] !== $id; });
    saveCommunity(array_values($community));
    header('Location: dashboard.php');
    exit;
}

// --- PROCESSAR POSTS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. SALVAR LINK (SECTION 05)
    if ($action === 'save_section') {
        $post_id = $_POST['id'] ?? '';
        $is_new = empty($post_id);
        $id = $is_new ? uniqid() : $post_id;
        
        $title = $_POST['title'] ?? '';
        $subtitle = $_POST['subtitle'] ?? '';
        $link = $_POST['link'] ?? '#';
        $visible = true;

        if (!$is_new) {
            foreach ($sections as $s) {
                if ($s['id'] === $id) {
                    $visible = isset($s['visible']) ? $s['visible'] : true;
                    break;
                }
            }
        }

        $imagePath = $_POST['existing_image'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $target = UPLOADS_DIR . '/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $imagePath = 'uploads/' . $filename;
            }
        }

        $newSection = [
            'id' => $id,
            'title' => $title,
            'subtitle' => $subtitle,
            'link' => $link,
            'image' => $imagePath,
            'visible' => $visible
        ];

        if ($is_new) {
            $sections[] = $newSection;
        } else {
            foreach ($sections as &$s) {
                if ($s['id'] === $id) {
                    $s = $newSection;
                    break;
                }
            }
        }
        saveSections($sections);
        header('Location: dashboard.php?status=success');
        exit;
    }

    // 2. SALVAR COMUNIDADE (SECTION 04)
    if ($action === 'save_community') {
        $post_id = $_POST['community_id'] ?? '';
        $is_new = empty($post_id);
        $id = $is_new ? uniqid() : $post_id;
        $embed_code = $_POST['embed_code'] ?? '';
        $visible = true;

        if (!$is_new) {
            foreach ($community as $c) {
                if ($c['id'] === $id) {
                    $visible = isset($c['visible']) ? $c['visible'] : true;
                    break;
                }
            }
        }

        $newItem = [
            'id' => $id,
            'embed_code' => $embed_code,
            'visible' => $visible
        ];

        if ($is_new) {
            $community[] = $newItem;
        } else {
            foreach ($community as &$c) {
                if ($c['id'] === $id) {
                    $c = $newItem;
                    break;
                }
            }
        }
        saveCommunity($community);
        header('Location: dashboard.php?status=success');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RISE RUNNING /// ADMIN</title>
    <link rel="icon" href="favicon_rise.png">
    <link href="https://fonts.googleapis.com/css2?family=Archivo:ital,wght@0,100..900;1,100..900&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff5e00; 
            --bg: #0a0a0a;
            --surface: #121212;
            --container: #2d2d2d;
            --border: #3e3e3e;
            --text: #e2e2e2;
            --font-display: 'Archivo', sans-serif;
            --font-tech: 'Space Mono', monospace;
        }

        [data-theme="light"] {
            --primary: #ff4800;
            --bg: #f0f0f0;
            --surface: #ffffff;
            --container: #e0e0e0;
            --border: #cccccc;
            --text: #1a1a1a;
        }

        * { box-sizing: border-box; }

        body {
            background-color: var(--bg);
            background-image: radial-gradient(circle at center, rgba(125,125,125,0.1) 0%, rgba(0,0,0,0.1) 100%);
            background-attachment: fixed;
            color: var(--text);
            font-family: var(--font-display);
            margin: 0;
            padding: 0; 
            min-height: 100vh;
            transition: background-color 0.5s ease, color 0.5s ease;
        }
        
        body::before {
            content: ""; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='1'/%3E%3C/svg%3E");
            opacity: 0.05; mix-blend-mode: overlay; pointer-events: none; z-index: 9999;
        }

        .container { max-width: 1000px; margin: 40px auto; position: relative; z-index: 1; padding: 0 20px; }
        
        /* --- HEADER (Barra Fixa) --- */
        header {
            display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; padding: 20px;
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 100;
            background: var(--bg);
            opacity: 0.95; backdrop-filter: blur(10px);
        }
        
        .brand { 
            font-size: 1.5rem; font-weight: 900; letter-spacing: -0.05em; text-transform: uppercase; 
            text-decoration: none; color: var(--text); justify-self: start;
        }
        .brand span { color: var(--primary); }
        .brand .admin-badge { 
            font-size: 1rem; color: var(--text); opacity: 0.5; margin-left: 10px; 
            font-family: var(--font-tech); letter-spacing: 1px; vertical-align: middle;
        }

        nav { display: flex; gap: 8px; justify-self: center; } /* Vazio mas mantido para layout */

        .header-actions { justify-self: end; display: flex; align-items: center; gap: 16px; }

        .pill { 
            border-radius: 999px; padding: 10px 24px; text-transform: uppercase; 
            font-weight: 700; text-decoration: none; display: inline-block; 
            transition: transform 0.2s; white-space: nowrap; 
            background: var(--primary); color: #000;
            font-size: 0.9rem; letter-spacing: 0.5px; border: none; cursor: pointer;
        }
        .pill:hover { transform: scale(1.05); color: #fff; }

        .theme-toggle { 
            background: transparent; border: 1px solid var(--border); color: var(--text); 
            width: 44px; height: 44px; border-radius: 50%; display: flex; 
            align-items: center; justify-content: center; cursor: pointer; 
            position: relative; overflow: hidden; 
        }
        .theme-icon { width: 20px; height: 20px; position: absolute; stroke: currentColor; stroke-width: 2; fill: none; stroke-linecap: round; stroke-linejoin: round; transition: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55); }
        
        [data-theme="dark"] .icon-sun { opacity: 1; transform: rotate(0deg) scale(1); }
        [data-theme="dark"] .icon-moon { opacity: 0; transform: rotate(90deg) scale(0); }
        [data-theme="light"] .icon-sun { opacity: 0; transform: rotate(-90deg) scale(0); }
        [data-theme="light"] .icon-moon { opacity: 1; transform: rotate(0deg) scale(1); }

        @media (max-width: 900px) {
            header { grid-template-columns: auto 1fr auto; gap: 10px; }
            nav { display: none; }
        }

        /* --- PAGE TITLE (O Texto Grande) --- */
        .page-header {
            margin-bottom: 60px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 20px;
        }
        
        .page-header h1 { 
            margin: 0; text-transform: uppercase; color: var(--text); 
            font-size: 2.5rem; font-weight: 900; line-height: 0.9; 
        }
        
        .page-header span { 
            display: block; color: var(--primary); font-size: 1rem; 
            font-family: var(--font-tech); margin-bottom: 5px; letter-spacing: 2px; 
        }

        /* --- ACCORDION & LAYOUT --- */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 0; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary); }

        .admin-section { margin-bottom: 30px; }

        .block-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: 20px; background: rgba(18, 18, 18, 0.2);
            border: 1px solid var(--border); border-left: 4px solid var(--primary);
            cursor: pointer; transition: all 0.3s; user-select: none;
            position: relative; z-index: 2;
        }
        [data-theme="dark"] .block-header { background: rgba(18, 18, 18, 0.8); }
        .block-header:hover { background: rgba(125,125,125,0.05); border-color: var(--primary); }

        .block-info { display: flex; flex-direction: column; }
        .block-title { font-family: var(--font-display); font-size: 1.2rem; font-weight: 900; text-transform: uppercase; margin: 0; display: flex; align-items: center; gap: 10px; color: var(--text); }
        .block-desc { font-family: var(--font-tech); font-size: 0.8rem; color: var(--text); opacity: 0.6; margin-top: 5px; }

        .arrow-indicator { 
            width: 24px; height: 24px; fill: var(--text); 
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .admin-section.collapsed .arrow-indicator { transform: rotate(-90deg); }

        /* Conteúdo Deslizante */
        .block-content {
            background: rgba(18, 18, 18, 0.05); 
            border: 1px solid var(--border); 
            border-top: none;
            overflow: hidden;
            
            /* Estado ABERTO */
            max-height: 3000px;
            opacity: 1;
            padding: 20px;
            transform: translateY(0);
            clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%);
            
            transition: 
                max-height 0.6s cubic-bezier(0.16, 1, 0.3, 1),
                padding 0.6s cubic-bezier(0.16, 1, 0.3, 1),
                opacity 0.4s ease-in-out,
                transform 0.6s cubic-bezier(0.16, 1, 0.3, 1),
                clip-path 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        [data-theme="dark"] .block-content { background: rgba(18, 18, 18, 0.5); }

        /* Estado FECHADO */
        .admin-section.collapsed .block-content {
            display: block !important;
            max-height: 0;
            opacity: 0;
            padding: 0 20px; 
            border-width: 0;
            transform: translateY(-20px);
            clip-path: polygon(0 0, 100% 0, 100% 0, 0 0);
            pointer-events: none;
        }

        .comm-card, .section-item {
            transition: transform 0.5s ease, opacity 0.5s ease;
            transform: translateY(0);
            opacity: 1;
        }
        .admin-section.collapsed .comm-card,
        .admin-section.collapsed .section-item {
            transform: translateY(-30px);
            opacity: 0;
        }

        /* BUTTONS */
        .btn {
            padding: 10px 20px; background: var(--primary); color: #000;
            text-decoration: none; border-radius: 0; font-weight: 800;
            font-family: var(--font-display); border: none; cursor: pointer; 
            text-transform: uppercase; font-size: 0.8rem; transition: all 0.3s;
            display: inline-flex; align-items: center; justify-content: center; gap: 10px;
            clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);
        }
        .btn:hover { background: var(--text); color: var(--bg); transform: translateY(-2px); }

        .btn-outline { 
            background: transparent; border: 1px solid var(--border); color: var(--text); 
            clip-path: none; border-radius: 4px; font-family: var(--font-tech); 
            font-weight: normal; padding: 8px 12px; cursor: pointer; transition: all 0.3s;
            text-transform: uppercase;
        }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); background: var(--surface); }

        .btn-danger { 
            background: transparent; border: 1px solid #ff4444; color: #ff4444; 
            font-family: var(--font-tech); padding: 8px 12px; border-radius: 4px;
            cursor: pointer; text-decoration: none; display: inline-flex; 
            align-items: center; justify-content: center;
        }
        .btn-danger:hover { background: #ff4444; color: #fff; }
        .btn-icon { padding: 8px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; }

        /* LIST GENERIC */
        .section-list { display: flex; flex-direction: column; gap: 8px; }
        .section-item {
            background: rgba(125,125,125,0.03); padding: 20px; 
            display: grid; grid-template-columns: auto 1fr auto; gap: 20px;
            align-items: center; transition: all 0.3s; 
            border: 1px solid var(--border); border-radius: 4px;
        }
        .section-item:hover { border-color: var(--primary); background: rgba(125,125,125,0.05); }
        .item-img { width: 48px; height: 48px; background: #000; border: 1px solid var(--border); object-fit: contain; padding: 5px; border-radius: 50%; }
        .item-content { display: flex; flex-direction: column; gap: 5px; overflow: hidden; }
        .item-title { font-weight: 800; font-size: 1rem; text-transform: uppercase; color: var(--text); font-family: var(--font-display); }
        .item-sub { font-family: var(--font-tech); font-size: 0.7rem; color: var(--primary); opacity: 0.8; text-transform: uppercase; }
        .item-link { font-family: var(--font-tech); font-size: 0.7rem; color: var(--text); text-decoration: none; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; opacity: 0.7;}
        .item-actions { display: flex; gap: 10px; align-items: center; }

        /* COMMUNITY CARDS */
        .community-card-list { display: flex; flex-direction: column; gap: 20px; }

.comm-card {
    display: grid;
    grid-template-columns: 350px 1fr; 
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--border);
    
    /* MUDANÇA: Reduzir altura mínima (antes era 280px ou mais) */
    min-height: 200px; 
    max-height: 500px; /* Limite máximo para não esticar demais */
    
    transition: all 0.3s;
    border-radius: 4px;
    overflow: hidden;
}
.comm-card:hover { border-color: var(--primary); }

/* Coluna Esquerda: Preview com Embed Real */
.comm-preview-area {
   /* background: #000;*/
    border-right: 1px solid var(--border);
    display: flex; flex-direction: column;
    align-items: center; justify-content: flex-start;
    padding: 10px 50px 40px 10px;
    position: relative;
    overflow: hidden; /* Corta o excesso se o embed for muito longo */
}

/* Container que segura o embed */
.comm-embed-wrapper {
    width: 100%;
    display: flex; justify-content: center;
    
    /* TRUQUE: Diminui visualmente o embed para caber melhor */
    transform: scale(0.85); 
    transform-origin: top center;
    margin-bottom: -50px; /* Compensa o espaço vazio deixado pelo scale */
}

/* Força o embed do Instagram a respeitar o container */
.instagram-media {
    margin: 0 !important;
    min-width: unset !important;
    max-width: 100% !important;
    border-radius: 4px !important;
    box-shadow: none !important;
}

/* Coluna Direita: Informações */
.comm-details {
    /* MUDANÇA: Aumentei o padding da direita (o segundo valor) para 50px */
    padding: 30px 50px 30px 30px; 
    
    display: flex; 
    flex-direction: column; 
    justify-content: center;
}
        .comm-id-label {
            font-family: var(--font-display); font-weight: 900; font-size: 1rem; color: var(--text);
            margin-bottom: 20px; text-transform: uppercase;
        }

        .comm-controls-row { display: flex; align-items: center; gap: 15px; }

        .comm-code-box {
            background: #000; border: 1px solid var(--border);
            color: #666; font-family: var(--font-tech); font-size: 0.75rem;
            padding: 12px 15px; border-radius: 4px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            flex-grow: 1; max-width: 300px;
        }

        .status-badge { font-family: var(--font-tech); font-size: 0.6rem; text-transform: uppercase; padding: 4px 8px; border-radius: 4px; display: inline-block; }
        .status-hidden { background: rgba(255, 68, 68, 0.1); color: #ff4444; border: 1px solid #ff4444; }
        .item-hidden { opacity: 0.6; filter: grayscale(100%); border-style: dashed; }

        @media (max-width: 900px) {
            .comm-card { grid-template-columns: 1fr; grid-template-rows: auto auto; max-height: none; }
            .comm-preview-area { border-right: none; border-bottom: 1px solid var(--border); min-height: 300px; }
            .comm-controls-row { flex-direction: column; align-items: stretch; }
            .comm-code-box { max-width: 100%; }
            .item-actions { justify-content: flex-end; }
        }

        /* MODAIS */
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9); backdrop-filter: blur(5px);
            align-items: center; justify-content: center; z-index: 1000;
            opacity: 0; transition: opacity 0.3s; pointer-events: none;
        }
        .modal.active { display: flex; opacity: 1; pointer-events: all; }
        .modal-content {
            background: var(--surface); padding: 40px; width: 90%; max-width: 600px; 
            border: 1px solid var(--border); position: relative; box-shadow: 0 0 50px rgba(0,0,0,0.5);
            max-height: 90vh; overflow-y: auto; overflow-x: hidden; border-radius: 8px;
        }
        .modal-content h2 { color: var(--primary); border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-family: var(--font-tech); font-size: 0.7rem; color: var(--text); opacity:0.7; text-transform: uppercase; }
        .form-group input, .form-group textarea {
            width: 100%; padding: 12px; background: #000; border: 1px solid var(--border);
            color: white; font-family: var(--font-tech); font-size: 0.9rem; border-radius: 4px;
        }
        .form-group textarea { min-height: 150px; resize: vertical; line-height: 1.4; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: var(--primary); }
        .form-group input[type="file"] { padding: 10px 0; background: transparent; border: none; }
        .preview-box { margin-bottom: 20px; padding: 20px; border: 1px dashed var(--border); border-radius: 8px; background: #0a0a0a; text-align: center; }
        .preview-label { display: block; font-family: var(--font-tech); font-size: 0.7rem; color: #888; text-transform: uppercase; margin-bottom: 15px; text-align: left; }

        .link-btn-preview {
            position: relative; display: flex; align-items: center; justify-content: center;
            width: 100%; padding: 16px 60px; background: var(--container);
            border: 1px solid transparent; border-radius: 50px;
            color: var(--text); text-decoration: none; min-height: 64px; pointer-events: none;
        }
        .link-btn-preview .icon-circle {
            position: absolute; left: 20px; top: 50%; transform: translateY(-50%);
            width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
            overflow: hidden; background: #333;
        }
        .link-btn-preview .icon-circle img { width: 100%; height: 100%; object-fit: cover; }
        .link-btn-preview .icon-circle svg { width: 18px; height: 18px; fill: #fff; }
        .link-btn-preview .btn-content { display: flex; flex-direction: column; align-items: center; text-align: center; width: 100%; }
        .link-btn-preview .btn-title { font-family: var(--font-display); font-weight: 700; font-size: 1rem; line-height: 1.2; color: var(--text); }
        .link-btn-preview .btn-sub { font-family: var(--font-tech); font-size: 0.75rem; opacity: 0.6; margin-top: 4px; color: var(--text); }
    </style>
</head>
<body>

    <header>
        <a href="http://localhost/riserunning%20-%20Copia/public/" class="brand">
            RISE<span>RUNNING</span> <span class="admin-badge">/// ADMIN</span>
        </a>
        
        <nav></nav>
        
        <div class="header-actions">
            <button id="theme-toggle" class="theme-toggle">
                <svg class="theme-icon icon-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                <svg class="theme-icon icon-moon" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            </button>

            <a href="logout.php" class="pill filled">SAIR</a>
        </div>
    </header>

<div class="container">

    <div class="page-header">
        <span>PAINEL DE CONTROLE</span>
        <h1>GERENCIAR<br>CONTEÚDO</h1>
    </div>

    <div class="admin-section collapsed" id="sec-04">
        <div class="block-header" onclick="toggleSection('sec-04')">
            <div class="block-info">
                <h3 class="block-title">04 // Community</h3>
                <span class="block-desc">Incorporações do Instagram (Embeds)</span>
            </div>
            <svg class="arrow-indicator" viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"></path></svg>
        </div>
        
        <div class="block-content">
            <div style="text-align: right; margin-bottom: 20px;">
                <button onclick="openCommunityModal()" class="btn"><span style="font-size:1.2rem; line-height:0;">+</span> NOVO POST</button>
            </div>

            <div class="community-card-list">
    <?php if (empty($community)): ?>
        <div style="text-align:center; padding: 20px; color: #666; font-family: var(--font-tech);">NENHUM POST CADASTRADO ///</div>
    <?php else: ?>
        <?php foreach ($community as $c): 
            $isVisible = !isset($c['visible']) || $c['visible'];
            $itemClass = $isVisible ? 'comm-card' : 'comm-card item-hidden';
        ?>
            <div class="<?php echo $itemClass; ?>" id="row-comm-<?php echo $c['id']; ?>">
                
                <div class="comm-preview-area">
                    <div class="comm-embed-wrapper">
                        <?php echo $c['embed_code']; ?>
                    </div>
                </div>

                <div class="comm-details">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <span class="comm-id-label">POST ID: <?php echo htmlspecialchars($c['id']); ?></span>
                        <?php if(!$isVisible): ?>
                            <span class="status-badge status-hidden">[OCULTO]</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="comm-controls-row">
                        <div class="comm-code-box">
                            <?php echo htmlspecialchars($c['embed_code']); ?>
                        </div>
                        
                        <div class="item-actions">
                            <button onclick="toggleStatus('<?php echo $c['id']; ?>', 'community', this)" class="btn btn-outline btn-icon" title="<?php echo $isVisible ? 'Ocultar' : 'Mostrar'; ?>">
                                <?php if($isVisible): ?>
                                    <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                <?php else: ?>
                                    <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 2.98-.33 4.28-.9l.46.46L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>
                                <?php endif; ?>
                            </button>
                            <button onclick='editCommunity(<?php echo json_encode($c); ?>)' class="btn btn-outline" style="padding: 8px 16px;">EDITAR</button>
                            <a href="#" onclick="confirmDelete('?delete_community=<?php echo $c['id']; ?>'); return false;" class="btn btn-danger btn-icon">X</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
        </div>
    </div>

    <div class="admin-section collapsed" id="sec-05">
        <div class="block-header" onclick="toggleSection('sec-05')">
            <div class="block-info">
                <h3 class="block-title">05 // Links Rápidos</h3>
                <span class="block-desc">Botões da seção de links</span>
            </div>
            <svg class="arrow-indicator" viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"></path></svg>
        </div>
        
        <div class="block-content">
             <div style="text-align: right; margin-bottom: 20px;">
                <button onclick="openLinkModal()" class="btn"><span style="font-size:1.2rem; line-height:0;">+</span> NOVO LINK</button>
            </div>

            <div class="section-list">
                <?php if (empty($sections)): ?>
                    <div style="text-align:center; padding: 20px; color: #666; font-family: var(--font-tech);">NENHUM LINK CADASTRADO ///</div>
                <?php else: ?>
                    <?php foreach ($sections as $s): 
                        $isVisible = !isset($s['visible']) || $s['visible'];
                        $itemClass = $isVisible ? 'section-item' : 'section-item item-hidden';
                    ?>
                        <div class="<?php echo $itemClass; ?>" id="row-sec-<?php echo $s['id']; ?>">
                            <?php if (!empty($s['image'])): ?>
                                <img src="../<?php echo htmlspecialchars($s['image']); ?>" class="item-img" alt="Icon">
                            <?php else: ?>
                                <div class="item-img" style="display:flex;align-items:center;justify-content:center;color:#666;font-family:var(--font-tech);">///</div>
                            <?php endif; ?>
                            
                            <div class="item-content">
                                <span class="item-title"><?php echo htmlspecialchars($s['title']); ?></span>
                                <span class="item-sub"><?php echo htmlspecialchars($s['subtitle']); ?></span>
                                <a href="<?php echo htmlspecialchars($s['link']); ?>" target="_blank" class="item-link"><?php echo htmlspecialchars($s['link']); ?></a>
                                <?php if(!$isVisible): ?>
                                    <span class="status-badge status-hidden">[OCULTO NO SITE]</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-actions">
                                <button onclick="toggleStatus('<?php echo $s['id']; ?>', 'section', this)" class="btn btn-outline btn-icon" title="<?php echo $isVisible ? 'Ocultar' : 'Mostrar'; ?>">
                                    <?php if($isVisible): ?>
                                        <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    <?php else: ?>
                                        <svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 2.98-.33 4.28-.9l.46.46L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>
                                    <?php endif; ?>
                                </button>

                                <button onclick='editLink(<?php echo json_encode($s); ?>)' class="btn btn-outline" style="padding: 8px 16px;">EDITAR</button>
                                <a href="#" onclick="confirmDelete('?delete_section=<?php echo $s['id']; ?>'); return false;" class="btn btn-danger btn-icon">X</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<div id="modalLink" class="modal">
    <div class="modal-content">
        <h2 id="modalLinkTitle">ADICIONAR LINK</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_section">
            <input type="hidden" name="id" id="inpLinkId">
            <input type="hidden" name="existing_image" id="inpLinkImage">
            
            <div class="preview-box">
                <span class="preview-label">/// PREVIEW DO CARD (COMO FICARÁ NO SITE)</span>
                <div class="link-btn-preview">
                    <div class="icon-circle" id="previewIcon">
                        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></svg>
                    </div>
                    <div class="btn-content">
                        <span class="btn-title" id="previewTitle">TÍTULO AQUI</span>
                        <span class="btn-sub" id="previewSubtitle"></span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>TÍTULO PRINCIPAL</label>
                <input type="text" name="title" id="inpLinkTitle" required placeholder="EX: INSTAGRAM">
            </div>
            <div class="form-group">
                <label>SUBTÍTULO (OPCIONAL)</label>
                <input type="text" name="subtitle" id="inpLinkSubtitle" placeholder="EX: FOLLOW US">
            </div>
            <div class="form-group">
                <label>URL DE DESTINO</label>
                <input type="url" name="link" id="inpLinkUrl" required placeholder="HTTPS://...">
            </div>
            <div class="form-group">
                <label>ÍCONE / IMAGEM</label>
                <input type="file" name="image" id="inpFileImage" accept="image/*">
                
                <div id="largePreviewFrame" style="margin-top:15px; display:none; border: 1px solid var(--border); padding: 10px; background: #000;">
                     <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
                        <span style="font-size:0.7rem; color:#666; font-family:var(--font-tech);">/// VISUALIZAÇÃO DA IMAGEM:</span>
                        <button type="button" id="btnRemoveImage" style="background:none; border:1px solid #ff4444; color:#ff4444; padding: 2px 8px; font-family:var(--font-tech); font-size:0.65rem; cursor:pointer; text-transform:uppercase;">X</button>
                     </div>
                     <img id="largePreviewImg" src="" style="width:100%; height:auto; display:block; border-radius: 4px;">
                </div>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:15px; margin-top:30px;">
                <button type="button" onclick="closeModals()" class="btn btn-outline">CANCELAR</button>
                <button type="submit" class="btn">SALVAR DADOS</button>
            </div>
        </form>
    </div>
</div>

<div id="modalCommunity" class="modal">
    <div class="modal-content">
        <h2 id="modalCommunityTitle">ADICIONAR POST INSTAGRAM</h2>
        <form method="POST" id="formCommunity">
            <input type="hidden" name="action" value="save_community">
            <input type="hidden" name="community_id" id="inpCommId">
            
            <div class="form-group">
                <label>CÓDIGO DE INCORPORAÇÃO (EMBED CODE)</label>
                <textarea name="embed_code" id="inpCommCode" required placeholder="<blockquote class='instagram-media' ...>"></textarea>
                <small style="display:block; margin-top:5px; color:#666; font-family: var(--font-tech); font-size: 0.7rem;">
                    /// VÁ NO INSTAGRAM > POST > ... > INCORPORAR (EMBED) > COPIAR CÓDIGO
                </small>
            </div>

            <div class="preview-box" id="communityPreviewBox" style="display: none; margin-top: 20px;">
                <span class="preview-label">/// PREVIEW DO POST</span>
                <div id="communityPreview" style="min-height: 100px; display: flex; justify-content: center; align-items: center; color: #444; font-family: var(--font-tech); font-size: 0.8rem;">
                </div>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:15px; margin-top:30px;">
                <button type="button" onclick="closeModals()" class="btn btn-outline">CANCELAR</button>
                <button type="submit" class="btn">SALVAR POST</button>
            </div>
        </form>
    </div>
</div>

<div id="modalConfirm" class="modal" style="z-index: 2001;">
    <div class="modal-content" style="max-width: 350px; text-align: center;">
        <h2 style="color: #ff4444; border:none;">/// EXCLUIR?</h2>
        <p style="margin-bottom: 30px;">Essa ação é irreversível.</p>
        <div style="display:flex; gap:10px; justify-content: center;">
            <button onclick="closeConfirm()" class="btn btn-outline" style="width:50%">CANCELAR</button>
            <button id="btnConfirmAction" class="btn btn-danger" style="width:50%">SIM, EXCLUIR</button>
        </div>
    </div>
</div>

<div id="modalSuccess" class="modal" style="z-index: 2002;">
    <div class="modal-content" style="max-width: 350px; text-align: center;">
        <h2 style="color: #00e676; border:none;">/// SUCESSO</h2>
        <p style="margin-bottom: 30px;">Dados salvos.</p>
        <button onclick="closeSuccess()" class="btn" style="width: 100%; background: #00e676; color: #000;">OK</button>
    </div>
</div>

<div id="modalAlert" class="modal" style="z-index: 2000;">
    <div class="modal-content" style="max-width: 350px; text-align: center;">
        <h2 style="color: var(--primary); border:none;">/// ALERTA</h2>
        <p id="modalAlertMsg" style="margin-bottom: 30px;"></p>
        <button onclick="closeAlert()" class="btn" style="width: 100%;">ENTENDIDO</button>
    </div>
</div>

<script async src="//www.instagram.com/embed.js"></script>
<script>
    // THEME TOGGLE LOGIC
    const toggleButton = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement;
    toggleButton.addEventListener('click', () => {
        const currentTheme = htmlElement.getAttribute('data-theme');
        htmlElement.setAttribute('data-theme', currentTheme === 'dark' ? 'light' : 'dark');
    });

    // ICONS for Toggle
    const svgEyeOpen = '<svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
    const svgEyeClosed = '<svg viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 2.98-.33 4.28-.9l.46.46L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';

    // AJAX TOGGLE (SEM REFRESH)
    function toggleStatus(id, type, btn) {
        fetch(`dashboard.php?ajax_action=toggle_${type}&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const rowId = (type === 'section') ? `row-sec-${id}` : `row-comm-${id}`;
                    const row = document.getElementById(rowId);
                    
                    if (data.visible) {
                        row.classList.remove('item-hidden');
                        const badge = row.querySelector('.status-badge');
                        if(badge) badge.remove();
                        btn.innerHTML = svgEyeOpen;
                        btn.title = 'Ocultar';
                    } else {
                        row.classList.add('item-hidden');
                        if (!row.querySelector('.status-badge')) {
                            if (type === 'community') {
                                const container = row.querySelector('.comm-details div');
                                const span = document.createElement('span');
                                span.className = 'status-badge status-hidden';
                                span.innerText = '[OCULTO]';
                                container.appendChild(span);
                            } else {
                                const container = row.querySelector('.item-content');
                                const span = document.createElement('span');
                                span.className = 'status-badge status-hidden';
                                span.innerText = '[OCULTO NO SITE]';
                                container.appendChild(span);
                            }
                        }
                        btn.innerHTML = svgEyeClosed;
                        btn.title = 'Mostrar';
                    }
                } else {
                    showCustomAlert('Erro ao atualizar.');
                }
            })
            .catch(error => showCustomAlert('Erro de conexão.'));
    }

    const modalSuccess = document.getElementById('modalSuccess');
    function closeSuccess() { modalSuccess.classList.remove('active'); }
    if (new URLSearchParams(window.location.search).get('status') === 'success') {
        modalSuccess.classList.add('active');
        window.history.replaceState(null, null, window.location.pathname);
    }

    const modalConfirm = document.getElementById('modalConfirm');
    const btnConfirmAction = document.getElementById('btnConfirmAction');
    let deleteTargetUrl = '';
    function confirmDelete(url) { deleteTargetUrl = url; modalConfirm.classList.add('active'); }
    function closeConfirm() { modalConfirm.classList.remove('active'); deleteTargetUrl = ''; }
    btnConfirmAction.addEventListener('click', function() { if(deleteTargetUrl) window.location.href = deleteTargetUrl; });

    const modalAlert = document.getElementById('modalAlert');
    const modalAlertMsg = document.getElementById('modalAlertMsg');
    function showCustomAlert(msg) { modalAlertMsg.innerText = msg; modalAlert.classList.add('active'); }
    function closeAlert() { modalAlert.classList.remove('active'); }

    function toggleSection(id) {
        document.getElementById(id).classList.toggle('collapsed');
    }

    const modalLink = document.getElementById('modalLink');
    const inpLinkId = document.getElementById('inpLinkId');
    const inpLinkTitle = document.getElementById('inpLinkTitle');
    const inpLinkSubtitle = document.getElementById('inpLinkSubtitle');
    const inpLinkUrl = document.getElementById('inpLinkUrl');
    const inpLinkImage = document.getElementById('inpLinkImage');
    const inpFileImage = document.getElementById('inpFileImage');
    const modalLinkTitle = document.getElementById('modalLinkTitle');
    const previewTitle = document.getElementById('previewTitle');
    const previewSubtitle = document.getElementById('previewSubtitle');
    const previewIcon = document.getElementById('previewIcon');
    const defaultIconSVG = '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></svg>';
    const largePreviewFrame = document.getElementById('largePreviewFrame');
    const largePreviewImg = document.getElementById('largePreviewImg');
    const btnRemoveImage = document.getElementById('btnRemoveImage');

    const modalCommunity = document.getElementById('modalCommunity');
    const inpCommId = document.getElementById('inpCommId');
    const inpCommCode = document.getElementById('inpCommCode');
    const modalCommunityTitle = document.getElementById('modalCommunityTitle');
    const communityPreviewBox = document.getElementById('communityPreviewBox');
    const communityPreview = document.getElementById('communityPreview');

    function closeModals() {
        modalLink.classList.remove('active');
        modalCommunity.classList.remove('active');
    }

    function updatePreview() {
        previewTitle.innerText = inpLinkTitle.value || 'TÍTULO AQUI';
        previewSubtitle.innerText = inpLinkSubtitle.value || '';
    }
    inpLinkTitle.addEventListener('input', updatePreview);
    inpLinkSubtitle.addEventListener('input', updatePreview);

    inpFileImage.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewIcon.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
                largePreviewImg.src = e.target.result;
                largePreviewFrame.style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else { checkExistingImage(); }
    });

    function checkExistingImage() {
        const existingPath = inpLinkImage.value;
        if (existingPath) {
             previewIcon.innerHTML = `<img src="../${existingPath}" style="width:100%;height:100%;object-fit:cover;">`;
             largePreviewImg.src = '../' + existingPath;
             largePreviewFrame.style.display = 'block';
        } else {
             previewIcon.innerHTML = defaultIconSVG;
             largePreviewFrame.style.display = 'none';
             largePreviewImg.src = '';
        }
    }

    btnRemoveImage.addEventListener('click', function() {
        inpFileImage.value = '';
        inpLinkImage.value = '';
        previewIcon.innerHTML = defaultIconSVG;
        largePreviewFrame.style.display = 'none';
        largePreviewImg.src = '';
    });

    function checkEmbedLock() {
        const code = inpCommCode.value.trim();
        const hasEmbed = code.includes('<blockquote') && code.includes('</blockquote>');
        if (hasEmbed) inpCommCode.dataset.validEmbed = code;
        else delete inpCommCode.dataset.validEmbed;
        updateCommunityPreview();
    }
    inpCommCode.addEventListener('input', checkEmbedLock);
    inpCommCode.addEventListener('paste', function(e) {
        setTimeout(() => {
            const matches = this.value.match(/<blockquote/g);
            if (matches && matches.length > 1) {
                showCustomAlert('/// APENAS UM EMBED PERMITIDO POR VEZ');
                this.value = ''; checkEmbedLock();
            }
        }, 0);
    });

    function updateCommunityPreview() {
        const code = inpCommCode.value.trim();
        if (code) {
            communityPreviewBox.style.display = 'block';
            communityPreview.innerHTML = code;
            if (window.instgrm) window.instgrm.Embeds.process();
        } else {
            communityPreviewBox.style.display = 'none';
            communityPreview.innerHTML = '';
        }
    }

    function openLinkModal() {
        modalLink.classList.add('active');
        inpLinkId.value = ''; inpLinkTitle.value = ''; inpLinkSubtitle.value = ''; inpLinkUrl.value = ''; inpLinkImage.value = ''; inpFileImage.value = '';
        modalLinkTitle.innerText = 'ADICIONAR LINK';
        previewTitle.innerText = 'TÍTULO AQUI'; previewSubtitle.innerText = ''; previewIcon.innerHTML = defaultIconSVG; largePreviewFrame.style.display = 'none'; largePreviewImg.src = '';
    }

    function editLink(item) {
        modalLink.classList.add('active');
        inpLinkId.value = item.id; inpLinkTitle.value = item.title; inpLinkSubtitle.value = item.subtitle; inpLinkUrl.value = item.link; inpLinkImage.value = item.image;
        modalLinkTitle.innerText = 'EDITAR LINK';
        previewTitle.innerText = item.title; previewSubtitle.innerText = item.subtitle;
        checkExistingImage();
    }

    function openCommunityModal() {
        modalCommunity.classList.add('active');
        inpCommId.value = ''; inpCommCode.value = '';
        modalCommunityTitle.innerText = 'ADICIONAR POST';
        updateCommunityPreview();
    }

    function editCommunity(item) {
        modalCommunity.classList.add('active');
        inpCommId.value = item.id; inpCommCode.value = item.embed_code;
        modalCommunityTitle.innerText = 'EDITAR POST';
        updateCommunityPreview();
    }

    document.querySelectorAll('.block-content button, .block-content a').forEach(el => {
        el.addEventListener('click', (e) => e.stopPropagation());
    });

    window.addEventListener('click', (e) => {
        if (e.target === modalLink || e.target === modalCommunity || e.target === modalAlert || e.target === modalConfirm || e.target === modalSuccess) {
            if (e.target === modalAlert) closeAlert();
            else if (e.target === modalConfirm) closeConfirm();
            else if (e.target === modalSuccess) closeSuccess();
            else closeModals();
        }
    });

    const formCommunity = document.getElementById('formCommunity');
    formCommunity.addEventListener('submit', function(e) {
        const code = inpCommCode.value.trim();
        if (!code.includes('<blockquote') || !code.includes('instagram-media')) {
            e.preventDefault();
            showCustomAlert('/// ERRO: CÓDIGO INVÁLIDO.\nÉ NECESSÁRIO O CÓDIGO DE EMBED COMPLETO DO INSTAGRAM.');
        }
    });

    if ('scrollRestoration' in history) { history.scrollRestoration = 'manual'; }
    window.addEventListener('load', function() {
        const scrollPos = sessionStorage.getItem('dashboardScrollPos');
        if (scrollPos) window.scrollTo(0, parseInt(scrollPos));
    });
    window.addEventListener('beforeunload', function() {
        sessionStorage.setItem('dashboardScrollPos', window.scrollY);
    });
</script>

</body>
</html>