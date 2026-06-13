<?php
require_once 'auth.php';
require_once '../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adloaf Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
            background-color: var(--bg-primary);
        }
        .admin-sidebar {
            width: 250px;
            background-color: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            padding: 2rem 1rem;
            display: flex;
            flex-direction: column;
        }
        .admin-main {
            flex: 1;
            padding: 2rem 4rem;
            overflow-y: auto;
        }
        .admin-nav {
            list-style: none;
            padding: 0;
            margin-top: 2rem;
            flex: 1;
        }
        .admin-nav li {
            margin-bottom: 0.5rem;
        }
        .admin-nav a {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .admin-nav a:hover, .admin-nav a.active {
            background-color: rgba(234, 88, 12, 0.1);
            color: var(--primary-color);
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .admin-page-title {
            font-size: 2rem;
            color: var(--text-primary);
        }
        .admin-card {
            background-color: var(--bg-secondary);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        .admin-table th, .admin-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .admin-table th {
            color: var(--text-secondary);
            font-weight: 600;
        }
        .admin-table td {
            color: var(--text-primary);
        }
        .action-links a {
            color: var(--primary-color);
            margin-right: 10px;
            text-decoration: none;
        }
        .action-links a:hover {
            text-decoration: underline;
        }
        .success-toast {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
    </style>
</head>
<body class="admin-body">
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <a href="index.php" class="logo" style="justify-content: center;">
                <span class="logo-text">Adloaf<span class="logo-dot">.</span> Admin</span>
            </a>
            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
            <ul class="admin-nav">
                <li><a href="index.php"         class="<?php echo $current_page == 'index.php'         ? 'active' : ''; ?>">📊 Dashboard</a></li>
                <li><a href="bake_requests.php"  class="<?php echo $current_page == 'bake_requests.php' ? 'active' : ''; ?>">🍞 Bake Requests</a></li>
                <li><a href="works.php"          class="<?php echo $current_page == 'works.php'         ? 'active' : ''; ?>">👥 Clients & Works</a></li>
                <li><a href="services.php"       class="<?php echo $current_page == 'services.php'      ? 'active' : ''; ?>">🛠 Services</a></li>
                <li><a href="portfolio.php"      class="<?php echo $current_page == 'portfolio.php'     ? 'active' : ''; ?>">🎨 Portfolio</a></li>
                <li><a href="process.php"        class="<?php echo $current_page == 'process.php'       ? 'active' : ''; ?>">⚙ Process</a></li>
                <li><a href="bento.php"          class="<?php echo $current_page == 'bento.php'         ? 'active' : ''; ?>">🧱 Bento Grid</a></li>
                <li><a href="clients_brand.php"  class="<?php echo $current_page == 'clients_brand.php' ? 'active' : ''; ?>">🏷 Client Logos</a></li>
                <li><a href="messages.php"       class="<?php echo $current_page == 'messages.php'      ? 'active' : ''; ?>">✉ Messages</a></li>
                <li><a href="settings.php"       class="<?php echo $current_page == 'settings.php'      ? 'active' : ''; ?>">⚙ Site Settings</a></li>
            </ul>
            <div style="margin-top: auto;">
                <a href="../index.php" target="_blank" class="btn btn-secondary" style="display: block; text-align: center; margin-bottom: 10px;">View Site</a>
                <a href="logout.php" class="btn btn-primary" style="display: block; text-align: center;">Logout</a>
            </div>
        </aside>
        <main class="admin-main">
