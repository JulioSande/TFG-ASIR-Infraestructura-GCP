<?php
/**
 * ============================================================================
 * DASHBOARD CORPORATIVO - TFG ASIR
 * Infraestructura Cloud GCP con Terraform
 * Autor: Julio Sande
 * ============================================================================
 * 
 * Este archivo contiene todo el código necesario para el dashboard:
 * - Conexión a Base de Datos MariaDB
 * - Frontend completo (HTML, CSS, JS)
 * - Integración con Chart.js para gráficas
 * - Sistema de navegación SPA con Bootstrap Tabs
 * - Modo Oscuro/Claro
 * - Reloj en tiempo real
 */

// ============================================================================
// CONEXIÓN A BASE DE DATOS
// ============================================================================
$servername = "10.0.2.2";
$username = "user_tfg";
$password = "Password123!";
$dbname = "tfg_db";
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión y preparar datos
$db_status = $conn->connect_error ? 'error' : 'success';
$db_error_msg = $conn->connect_error ?? '';
$invitados = [];

if ($db_status === 'success') {
    $result = $conn->query("SELECT id, nombre, fecha FROM invitados ORDER BY fecha DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $invitados[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud Dashboard | TFG ASIR - Julio Sande</title>
    
    <!-- CDN: Bootstrap 5 -->
    <link href="[cdn.jsdelivr.net](https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css)" rel="stylesheet">
    <!-- CDN: Bootstrap Icons -->
    <link href="[cdn.jsdelivr.net](https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css)" rel="stylesheet">
    <!-- CDN: Google Fonts -->
    <link href="[fonts.googleapis.com](https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap)" rel="stylesheet">
    
    <style>
        /* ====================================================================
           VARIABLES CSS Y TEMA BASE
           ==================================================================== */
        :root {
            --sidebar-width: 280px;
            --header-height: 70px;
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --danger-gradient: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            --info-gradient: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --card-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            --card-hover-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Tema Claro */
        [data-bs-theme="light"] {
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-sidebar: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --card-bg: #ffffff;
            --input-bg: #f1f5f9;
        }

        /* Tema Oscuro */
        [data-bs-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-sidebar: linear-gradient(180deg, #0f172a 0%, #020617 100%);
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --border-color: #334155;
            --card-bg: #1e293b;
            --input-bg: #334155;
        }

        /* ====================================================================
           ESTILOS BASE Y RESET
           ==================================================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            transition: var(--transition-smooth);
        }

        /* Scrollbar personalizada */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #667eea, #764ba2);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #764ba2, #667eea);
        }

        /* ====================================================================
           SIDEBAR
           ==================================================================== */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--bg-sidebar);
            padding: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.2);
        }

        .sidebar-brand {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-brand-icon {
            width: 45px;
            height: 45px;
            background: var(--primary-gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .sidebar-brand-text {
            color: white;
        }

        .sidebar-brand-text h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: -0.5px;
        }

        .sidebar-brand-text small {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.75rem;
            font-weight: 400;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 12px;
            overflow-y: auto;
        }

        .nav-section-title {
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 16px 16px 8px;
            margin-top: 8px;
        }

        .sidebar-nav .nav-link {
            color: rgba(255, 255, 255, 0.7);
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
        }

        .sidebar-nav .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: var(--primary-gradient);
            transform: scaleY(0);
            transition: var(--transition-smooth);
            border-radius: 0 4px 4px 0;
        }

        .sidebar-nav .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.08);
        }

        .sidebar-nav .nav-link.active {
            color: white;
            background: rgba(102, 126, 234, 0.2);
        }

        .sidebar-nav .nav-link.active::before {
            transform: scaleY(1);
        }

        .sidebar-nav .nav-link i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            background: var(--success-gradient);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 1rem;
        }

        .user-info {
            flex: 1;
        }

        .user-info h6 {
            color: white;
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .user-info small {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.75rem;
        }

        /* ====================================================================
           CONTENIDO PRINCIPAL
           ==================================================================== */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: var(--transition-smooth);
        }

        /* Header */
        .main-header {
            height: var(--header-height);
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .live-clock {
            background: var(--input-bg);
            padding: 10px 18px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .live-clock i {
            color: #667eea;
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-badge.online {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .status-badge .pulse {
            width: 8px;
            height: 8px;
            background: currentColor;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }

        /* Theme Toggle */
        .theme-toggle {
            width: 45px;
            height: 45px;
            border: none;
            background: var(--input-bg);
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--text-primary);
            transition: var(--transition-smooth);
        }

        .theme-toggle:hover {
            background: var(--primary-gradient);
            color: white;
            transform: rotate(15deg);
        }

        /* Main Container */
        .main-container {
            padding: 32px;
        }

        /* ====================================================================
           CARDS Y COMPONENTES
           ==================================================================== */
        .metric-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border-color);
            box-shadow: var(--card-shadow);
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            opacity: 0;
            transition: var(--transition-smooth);
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-hover-shadow);
        }

        .metric-card:hover::before {
            opacity: 1;
        }

        .metric-card.gradient-primary::before { background: var(--primary-gradient); opacity: 1; }
        .metric-card.gradient-success::before { background: var(--success-gradient); opacity: 1; }
        .metric-card.gradient-info::before { background: var(--info-gradient); opacity: 1; }
        .metric-card.gradient-warning::before { background: var(--warning-gradient); opacity: 1; }

        .metric-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .metric-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .metric-icon.bg-primary { background: var(--primary-gradient); }
        .metric-icon.bg-success { background: var(--success-gradient); }
        .metric-icon.bg-info { background: var(--info-gradient); }
        .metric-icon.bg-warning { background: var(--warning-gradient); }
        .metric-icon.bg-danger { background: var(--danger-gradient); }

        .metric-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-badge.success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .metric-badge.warning {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -1px;
            margin-bottom: 4px;
        }

        .metric-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .metric-footer {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .metric-footer i {
            color: #10b981;
        }

        /* Chart Cards */
        .chart-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .chart-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chart-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-title i {
            color: #667eea;
        }

        .chart-body {
            padding: 24px;
        }

        /* Table Styles */
        .data-table-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .table-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .table-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-title i {
            color: #667eea;
        }

        .table-responsive {
            padding: 0 24px 24px;
        }

        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .custom-table thead th {
            background: var(--input-bg);
            padding: 14px 16px;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-secondary);
            border: none;
        }

        .custom-table thead th:first-child {
            border-radius: 10px 0 0 10px;
        }

        .custom-table thead th:last-child {
            border-radius: 0 10px 10px 0;
        }

        .custom-table tbody td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .custom-table tbody tr:last-child td {
            border-bottom: none;
        }

        .custom-table tbody tr {
            transition: var(--transition-smooth);
        }

        .custom-table tbody tr:hover {
            background: var(--input-bg);
        }

        .id-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: var(--primary-gradient);
            color: white;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .name-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .name-avatar {
            width: 40px;
            height: 40px;
            background: var(--success-gradient);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .date-cell {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
        }

        .date-cell i {
            color: #667eea;
        }

        /* Technology Cards */
        .tech-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: var(--card-shadow);
            padding: 28px;
            text-align: center;
            transition: var(--transition-smooth);
            height: 100%;
        }

        .tech-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-hover-shadow);
        }

        .tech-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: white;
            position: relative;
        }

        .tech-icon::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 24px;
            background: inherit;
            opacity: 0.3;
            filter: blur(15px);
            z-index: -1;
        }

        .tech-card h5 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
        }

        .tech-card p {
            color: var(--text-secondary);
            font-size: 0.85rem;
            line-height: 1.6;
            margin: 0;
        }

        .tech-badge {
            display: inline-block;
            padding: 6px 14px;
            background: var(--input-bg);
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-top: 16px;
        }

        /* Alerts */
        .custom-alert {
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
            border: none;
        }

        .custom-alert.success {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .custom-alert.error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .custom-alert i {
            font-size: 1.4rem;
        }

        .custom-alert strong {
            display: block;
            margin-bottom: 2px;
        }

        .custom-alert span {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        /* Section Titles */
        .section-header {
            margin-bottom: 28px;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .section-subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Tab Content */
        .tab-pane {
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Architecture Diagram */
        .architecture-diagram {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            padding: 32px;
            margin-bottom: 32px;
        }

        .diagram-title {
            text-align: center;
            margin-bottom: 32px;
        }

        .diagram-title h4 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .diagram-title p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .diagram-flow {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .diagram-node {
            background: var(--input-bg);
            border: 2px solid var(--border-color);
            border-radius: 16px;
            padding: 20px 28px;
            text-align: center;
            min-width: 140px;
            transition: var(--transition-smooth);
        }

        .diagram-node:hover {
            border-color: #667eea;
            transform: scale(1.05);
        }

        .diagram-node i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }

        .diagram-node.internet i { color: #3b82f6; }
        .diagram-node.firewall i { color: #ef4444; }
        .diagram-node.web i { color: #10b981; }
        .diagram-node.database i { color: #f59e0b; }

        .diagram-node span {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-primary);
        }

        .diagram-node small {
            display: block;
            color: var(--text-muted);
            font-size: 0.75rem;
            margin-top: 4px;
        }

        .diagram-arrow {
            color: var(--text-muted);
            font-size: 1.5rem;
        }

        /* ====================================================================
           RESPONSIVE
           ==================================================================== */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-toggle {
                display: flex !important;
            }
        }

        @media (max-width: 768px) {
            .main-header {
                padding: 0 16px;
            }

            .main-container {
                padding: 20px 16px;
            }

            .metric-value {
                font-size: 1.6rem;
            }

            .diagram-flow {
                flex-direction: column;
            }

            .diagram-arrow {
                transform: rotate(90deg);
            }
        }

        /* Mobile Toggle */
        .mobile-toggle {
            display: none;
            width: 45px;
            height: 45px;
            border: none;
            background: var(--input-bg);
            border-radius: 12px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--text-primary);
        }

        /* Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .sidebar-overlay.show {
            display: block;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .empty-state h5 {
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ====================================================================
         SIDEBAR
         ==================================================================== -->
    <aside class="sidebar" id="sidebar">
        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <i class="bi bi-cloud-fill"></i>
            </div>
            <div class="sidebar-brand-text">
                <h5>Cloud Dashboard</h5>
                <small>TFG ASIR 2024</small>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <div class="nav-section-title">Principal</div>
            
            <a href="#monitoring" class="nav-link active" data-bs-toggle="tab" data-bs-target="#monitoring">
                <i class="bi bi-speedometer2"></i>
                <span>Monitorización</span>
            </a>
            
            <a href="#database" class="nav-link" data-bs-toggle="tab" data-bs-target="#database">
                <i class="bi bi-database-fill"></i>
                <span>Base de Datos</span>
            </a>
            
            <a href="#architecture" class="nav-link" data-bs-toggle="tab" data-bs-target="#architecture">
                <i class="bi bi-diagram-3-fill"></i>
                <span>Arquitectura</span>
            </a>

            <div class="nav-section-title">Sistema</div>
            
            <a href="#" class="nav-link" onclick="return false;">
                <i class="bi bi-shield-check"></i>
                <span>Seguridad Zero Trust</span>
            </a>
            
            <a href="#" class="nav-link" onclick="return false;">
                <i class="bi bi-terminal-fill"></i>
                <span>Terraform IaC</span>
            </a>
        </nav>

        <!-- Footer -->
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar">JS</div>
                <div class="user-info">
                    <h6>Julio Sande</h6>
                    <small>Administrador</small>
                </div>
            </div>
        </div>
    </aside>

    <!-- ====================================================================
         MAIN CONTENT
         ==================================================================== -->
    <main class="main-content">
        <!-- Header -->
        <header class="main-header">
            <div class="header-left">
                <button class="mobile-toggle" id="mobileToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title" id="pageTitle">Monitorización</h1>
            </div>
            <div class="header-right">
                <div class="status-badge online">
                    <span class="pulse"></span>
                    Sistema Operativo
                </div>
                <div class="live-clock">
                    <i class="bi bi-clock-fill"></i>
                    <span id="liveClock">00:00:00</span>
                </div>
                <button class="theme-toggle" id="themeToggle" title="Cambiar tema">
                    <i class="bi bi-moon-fill" id="themeIcon"></i>
                </button>
            </div>
        </header>

        <!-- Container -->
        <div class="main-container">
            <div class="tab-content">
                
                <!-- ============================================================
                     SECCIÓN 1: MONITORIZACIÓN
                     ============================================================ -->
                <div class="tab-pane fade show active" id="monitoring">
                    <div class="section-header">
                        <h2 class="section-title">Panel de Control</h2>
                        <p class="section-subtitle">Monitorización en tiempo real de la infraestructura Cloud GCP</p>
                    </div>

                    <!-- Métricas principales -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="metric-card gradient-success">
                                <div class="metric-header">
                                    <div class="metric-icon bg-success">
                                        <i class="bi bi-wifi"></i>
                                    </div>
                                    <span class="metric-badge success">Activo</span>
                                </div>
                                <div class="metric-value">Online</div>
                                <div class="metric-label">Estado de Red</div>
                                <div class="metric-footer">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Conexión VPC estable
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="metric-card gradient-primary">
                                <div class="metric-header">
                                    <div class="metric-icon bg-primary">
                                        <i class="bi bi-lightning-charge-fill"></i>
                                    </div>
                                    <span class="metric-badge success">Óptimo</span>
                                </div>
                                <div class="metric-value">&lt; 1ms</div>
                                <div class="metric-label">Latencia Interna</div>
                                <div class="metric-footer">
                                    <i class="bi bi-arrow-down"></i>
                                    -15% vs. media
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="metric-card gradient-info">
                                <div class="metric-header">
                                    <div class="metric-icon bg-info">
                                        <i class="bi bi-hdd-network-fill"></i>
                                    </div>
                                    <span class="metric-badge warning">Privada</span>
                                </div>
                                <div class="metric-value">10.0.2.2</div>
                                <div class="metric-label">IP Backend MariaDB</div>
                                <div class="metric-footer">
                                    <i class="bi bi-shield-fill-check"></i>
                                    Subred privada
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="metric-card gradient-warning">
                                <div class="metric-header">
                                    <div class="metric-icon bg-warning">
                                        <i class="bi bi-shield-lock-fill"></i>
                                    </div>
                                    <span class="metric-badge success">Activo</span>
                                </div>
                                <div class="metric-value">Zero Trust</div>
                                <div class="metric-label">Política de Seguridad</div>
                                <div class="metric-footer">
                                    <i class="bi bi-check-circle-fill"></i>
                                    Firewall configurado
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráficas -->
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-header">
                                    <div class="chart-title">
                                        <i class="bi bi-cpu-fill"></i>
                                        Uso de CPU - Servidor Web
                                    </div>
                                    <span class="metric-badge success">Normal</span>
                                </div>
                                <div class="chart-body">
                                    <canvas id="cpuChart" height="280"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-header">
                                    <div class="chart-title">
                                        <i class="bi bi-memory"></i>
                                        Consumo de RAM - Base de Datos
                                    </div>
                                    <span class="metric-badge warning">Moderado</span>
                                </div>
                                <div class="chart-body">
                                    <canvas id="ramChart" height="280"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================
                     SECCIÓN 2: BASE DE DATOS
                     ============================================================ -->
                <div class="tab-pane fade" id="database">
                    <div class="section-header">
                        <h2 class="section-title">Base de Datos en Tiempo Real</h2>
                        <p class="section-subtitle">Registros de la tabla <code>invitados</code> en MariaDB</p>
                    </div>

                    <!-- Estado de conexión -->
                    <?php if ($db_status === 'success'): ?>
                    <div class="custom-alert success">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>
                            <strong>Conexión establecida correctamente</strong>
                            <span>Base de datos: <?php echo htmlspecialchars($dbname); ?> @ <?php echo htmlspecialchars($servername); ?></span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="custom-alert error">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            <strong>Error de conexión a la base de datos</strong>
                            <span><?php echo htmlspecialchars($db_error_msg); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Tabla de datos -->
                    <div class="data-table-card">
                        <div class="table-header">
                            <div class="table-title">
                                <i class="bi bi-table"></i>
                                Tabla: invitados
                            </div>
                            <span class="metric-badge success">
                                <?php echo count($invitados); ?> registros
                            </span>
                        </div>
                        
                        <?php if (!empty($invitados)): ?>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">ID</th>
                                        <th>Nombre</th>
                                        <th>Fecha de Registro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invitados as $inv): ?>
                                    <tr>
                                        <td>
                                            <span class="id-badge"><?php echo htmlspecialchars($inv['id']); ?></span>
                                        </td>
                                        <td>
                                            <div class="name-cell">
                                                <div class="name-avatar">
                                                    <?php echo strtoupper(substr($inv['nombre'], 0, 2)); ?>
                                                </div>
                                                <span><?php echo htmlspecialchars($inv['nombre']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="date-cell">
                                                <i class="bi bi-calendar3"></i>
                                                <?php echo htmlspecialchars($inv['fecha']); ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h5>No hay registros</h5>
                            <p>La tabla de invitados está vacía o no se pudo establecer conexión.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ============================================================
                     SECCIÓN 3: ARQUITECTURA Y TECNOLOGÍAS
                     ============================================================ -->
                <div class="tab-pane fade" id="architecture">
                    <div class="section-header">
                        <h2 class="section-title">Arquitectura del Proyecto</h2>
                        <p class="section-subtitle">Infraestructura Cloud automatizada con Terraform en Google Cloud Platform</p>
                    </div>

                    <!-- Diagrama de arquitectura -->
                    <div class="architecture-diagram">
                        <div class="diagram-title">
                            <h4><i class="bi bi-diagram-3 me-2"></i>Flujo de Red - Zero Trust Architecture</h4>
                            <p>Segmentación de red con subredes públicas y privadas</p>
                        </div>
                        <div class="diagram-flow">
                            <div class="diagram-node internet">
                                <i class="bi bi-globe2"></i>
                                <span>Internet</span>
                                <small>Usuario Final</small>
                            </div>
                            <i class="bi bi-arrow-right diagram-arrow"></i>
                            <div class="diagram-node firewall">
                                <i class="bi bi-shield-fill-exclamation"></i>
                                <span>Cloud Firewall</span>
                                <small>Zero Trust</small>
                            </div>
                            <i class="bi bi-arrow-right diagram-arrow"></i>
                            <div class="diagram-node web">
                                <i class="bi bi-server"></i>
                                <span>Web Server</span>
                                <small>10.0.1.0/24</small>
                            </div>
                            <i class="bi bi-arrow-right diagram-arrow"></i>
                            <div class="diagram-node database">
                                <i class="bi bi-database-fill"></i>
                                <span>MariaDB</span>
                                <small>10.0.2.0/24</small>
                            </div>
                        </div>
                    </div>

                    <!-- Stack Tecnológico -->
                    <h4 class="mb-4" style="color: var(--text-primary); font-weight: 600;">
                        <i class="bi bi-stack me-2" style="color: #667eea;"></i>Stack Tecnológico
                    </h4>
                    
                    <div class="row g-4">
                        <!-- Terraform -->
                        <div class="col-xl-4 col-md-6">
                            <div class="tech-card">
                                <div class="tech-icon" style="background: linear-gradient(135deg, #7B42BC, #5C4EE5);">
                                    <i class="bi bi-code-square"></i>
                                </div>
                                <h5>Terraform</h5>
                                <p>Infraestructura como Código (IaC) para el aprovisionamiento automatizado de recursos en la nube.</p>
                                <span class="tech-badge">HashiCorp IaC</span>
                            </div>
                        </div>
                        
                        <!-- Google Cloud -->
                        <div class="col-xl-4 col-md-6">
                            <div class="tech-card">
                                <div class="tech-icon" style="background: linear-gradient(135deg, #4285F4, #34A853);">
                                    <i class="bi bi-cloud-fill"></i>
                                </div>
                                <h5>Google Cloud Platform</h5>
                                <p>Proveedor Cloud con VPC, Compute Engine, Cloud NAT y políticas de firewall avanzadas.</p>
                                <span class="tech-badge">Cloud Provider</span>
                            </div>
                        </div>
                        
                        <!-- Debian -->
                        <div class="col-xl-4 col-md-6">
                            <div class="tech-card">
                                <div class="tech-icon" style="background: linear-gradient(135deg, #A80030, #D70751);">
                                    <i class="bi bi-ubuntu"></i>
                                </div>
                                <h5>Debian 11</h5>
                                <p>Sistema operativo Linux estable y seguro para las instancias de Compute Engine.</p>
                                <span class="tech-badge">Linux OS</span>
                            </div>
                        </div>
                        
                        <!-- Apache -->
                        <div class="col-xl-4 col-md-6">
                            <div class="tech-card">
                                <div class="tech-icon" style="background: linear-gradient(135deg, #D22128, #F9A825);">
                                    <i class="bi bi-hdd-rack-fill"></i>
                                </div>
                                <h5>Apache HTTP Server</h5>
                                <p>Servidor web de alto rendimiento para servir contenido dinámico y estático.</p>
                                <span class="tech-badge">Web Server</span>
                            </div>
                        </div>
                        
                        <!-- MariaDB -->
                        <div class="col-xl-4 col-md-6">
                            <div class="tech-card">
                                <div class="tech-icon" style="background: linear-gradient(135deg, #003545, #C97539);">
                                    <i class="bi bi-database-fill-gear"></i>
                                </div>
                                <h5>MariaDB</h5>
                                <p>Motor de base de datos relacional alojado en subred privada sin acceso público.</p>
                                <span class="tech-badge">RDBMS</span>
                            </div>
                        </div>
                        
                        <!-- PHP -->
                        <div class="col-xl-4 col-md-6">
                            <div class="tech-card">
                                <div class="tech-icon" style="background: linear-gradient(135deg, #4F5B93, #8892BF);">
                                    <i class="bi bi-filetype-php"></i>
                                </div>
                                <h5>PHP 8</h5>
                                <p>Lenguaje de scripting del lado del servidor para la lógica de backend y conexión DB.</p>
                                <span class="tech-badge">Backend</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- ====================================================================
         SCRIPTS
         ==================================================================== -->
    <!-- CDN: Bootstrap JS -->
    <script src="[cdn.jsdelivr.net](https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js)"></script>
    <!-- CDN: Chart.js -->
    <script src="[cdn.jsdelivr.net](https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js)"></script>

    <script>
        // ====================================================================
        // RELOJ EN TIEMPO REAL
        // ====================================================================
        function updateClock() {
            const now = new Date();
            const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('es-ES', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // ====================================================================
        // MODO OSCURO / CLARO
        // ====================================================================
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const html = document.documentElement;

        // Cargar tema guardado
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-bs-theme', savedTheme);
        updateThemeIcon(savedTheme);

        themeToggle.addEventListener('click', () => {
            const current = html.getAttribute('data-bs-theme');
            const next = current === 'light' ? 'dark' : 'light';
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcon(next);
        });

        function updateThemeIcon(theme) {
            themeIcon.className = theme === 'light' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
        }

        // ====================================================================
        // NAVEGACIÓN - ACTUALIZAR TÍTULO
        // ====================================================================
        const navLinks = document.querySelectorAll('.sidebar-nav .nav-link[data-bs-toggle="tab"]');
        const pageTitle = document.getElementById('pageTitle');

        const titles = {
            'monitoring': 'Monitorización',
            'database': 'Base de Datos',
            'architecture': 'Arquitectura'
        };

        navLinks.forEach(link => {
            link.addEventListener('shown.bs.tab', (e) => {
                // Actualizar clase activa
                navLinks.forEach(l => l.classList.remove('active'));
                e.target.classList.add('active');
                
                // Actualizar título
                const target = e.target.getAttribute('data-bs-target').replace('#', '');
                pageTitle.textContent = titles[target] || 'Dashboard';

                // Cerrar sidebar en móvil
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('show');
                    sidebarOverlay.classList.remove('show');
                }
            });
        });

        // ====================================================================
        // SIDEBAR MÓVIL
        // ====================================================================
        const mobileToggle = document.getElementById('mobileToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        mobileToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });

        // ====================================================================
        // GRÁFICAS CON CHART.JS
        // ====================================================================
        
        // Configuración común
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(30, 41, 59, 0.95)',
                    titleColor: '#fff',
                    bodyColor: '#94a3b8',
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 11, family: 'Inter' }
                    }
                },
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: 'rgba(148, 163, 184, 0.1)'
                    },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 11, family: 'Inter' },
                        callback: value => value + '%'
                    }
                }
            },
            elements: {
                line: {
                    tension: 0.4
                },
                point: {
                    radius: 0,
                    hoverRadius: 6
                }
            }
        };

        // Datos simulados
        const timeLabels = ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '24:00'];

        // Gráfica CPU
        const cpuCtx = document.getElementById('cpuChart').getContext('2d');
        const cpuGradient = cpuCtx.createLinearGradient(0, 0, 0, 280);
        cpuGradient.addColorStop(0, 'rgba(102, 126, 234, 0.3)');
        cpuGradient.addColorStop(1, 'rgba(102, 126, 234, 0)');

        new Chart(cpuCtx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'CPU %',
                    data: [25, 32, 48, 72, 58, 45, 38],
                    borderColor: '#667eea',
                    backgroundColor: cpuGradient,
                    fill: true,
                    borderWidth: 3
                }]
            },
            options: chartOptions
        });

        // Gráfica RAM
        const ramCtx = document.getElementById('ramChart').getContext('2d');
        const ramGradient = ramCtx.createLinearGradient(0, 0, 0, 280);
        ramGradient.addColorStop(0, 'rgba(16, 185, 129, 0.3)');
        ramGradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

        new Chart(ramCtx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [{
                    label: 'RAM %',
                    data: [45, 52, 58, 65, 62, 55, 48],
                    borderColor: '#10b981',
                    backgroundColor: ramGradient,
                    fill: true,
                    borderWidth: 3
                }]
            },
            options: chartOptions
        });
    </script>
</body>
</html>
<?php
// Cerrar conexión si existe
if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
    $conn->close();
}
?>
