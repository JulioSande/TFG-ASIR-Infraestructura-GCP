<?php
// ============================================================================
// ARCHIVO: web_TFG.php
// DESCRIPCIÓN: Portal Seguro TFG ASIR - Control de Accesos y Auditoría.
// OBJETIVO: Actuar como Frontend de la arquitectura, gestionando el modelo 
// de seguridad AAA (Autenticación, Autorización y Auditoría) mediante sesiones 
// PHP nativas, mitigación de inyecciones SQL y un modelo RBAC estricto.
// ============================================================================
session_start(); // Inicialización del motor de sesiones seguras

// ------------------------------------------------------------------------------
// 1. CONEXIÓN AL BACKEND (ARQUITECTURA ZERO TRUST)
// ------------------------------------------------------------------------------
// Se define la IP Privada (10.0.2.2) asignada al servidor de base de datos.
// Al no usar un dominio público ni una IP externa, la conexión fluye exclusivamente
// por el enrutamiento interno de la VPC de Google Cloud, blindando la comunicación.
$servername = "10.0.2.2"; 
$username   = "user_tfg";
$password   = "Password123!";
$dbname     = "tfg_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// ------------------------------------------------------------------------------
// 2. CONTROL DEL CICLO DE VIDA DE LA SESIÓN (LOGOUT)
// ------------------------------------------------------------------------------
// Garantiza la destrucción absoluta de las variables de sesión del navegador 
// para prevenir ataques de secuestro de sesión (Session Hijacking).
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$error_msg = "";

// ------------------------------------------------------------------------------
// 3. MÓDULO DE AUTENTICACIÓN Y AUDITORÍA FORENSE (MÉTODO POST)
// ------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_btn'])) {
    
    // MITIGACIÓN DE RIESGOS (Prevención SQLi):
    // La función 'real_escape_string' sanea el input del usuario eliminando 
    // caracteres especiales para evitar ataques de Inyección SQL.
    $user_input = $conn->real_escape_string($_POST['username']);
    $pass_input = $_POST['password'];
    
    // TRAZABILIDAD: Captura la IP pública real del cliente para el registro de logs.
    $ip_origen  = $_SERVER['REMOTE_ADDR'];

    if (!$conn->connect_error) {
        
        // CIFRADO EN CALIENTE: La contraseña NUNCA viaja ni se compara en texto plano.
        // Se delega al motor MariaDB la transformación de la entrada a SHA-256 
        // para compararla matemáticamente con el hash almacenado.
        $sql = "SELECT username, rol FROM usuarios 
                WHERE username='$user_input' AND password = SHA2('$pass_input', 256)";
        $result = $conn->query($sql);

        if ($result && $result->num_rows == 1) {
            // AUTENTICACIÓN EXITOSA: Se generan los tokens de sesión y rol (RBAC).
            $user_data = $result->fetch_assoc();
            $_SESSION['usuario'] = $user_data['username'];
            $_SESSION['rol']     = $user_data['rol'];

            // MÓDULO DE AUDITORÍA: Registro persistente de evento de seguridad positivo.
            $conn->query("INSERT INTO accesos (username, ip_origen, resultado) 
                          VALUES ('$user_input', '$ip_origen', 'EXITOSO')");
            
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            // AUTENTICACIÓN FALLIDA: Denegación por credenciales incorrectas.
            $error_msg = "Credenciales incorrectas. Acceso denegado.";
            
            // MÓDULO DE AUDITORÍA: Registro persistente de evento de seguridad crítico.
            $conn->query("INSERT INTO accesos (username, ip_origen, resultado) 
                          VALUES ('$user_input', '$ip_origen', 'FALLIDO')");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal TFG - Julio Sande</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; }
        .hero-section { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 3rem 0; border-bottom: 5px solid #ffc107; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card-preview:hover { transform: translateY(-5px); }
        .badge-role { font-size: 0.9rem; padding: 0.5rem 1rem; }
        .feature-icon { font-size: 2.5rem; color: #0d6efd; margin-bottom: 1rem; }
    </style>
</head>
<body>

    <header class="hero-section text-center shadow-sm mb-4">
        <div class="container">
            <h1 class="display-5 fw-bold"><i class="bi bi-shield-lock-fill"></i> Portal de Acceso Seguro (RBAC)</h1>
            <p class="lead mt-2">Infraestructura Cloud Automatizada con Terraform en Google Cloud Platform</p>
            <span class="badge bg-warning text-dark fs-6">Autor: Julio Sande</span>
        </div>
    </header>

    <main class="container">
        
        <div class="row mb-4">
            <div class="col-12 text-end">
                <?php if ($conn->connect_error): ?>
                    <span class="badge bg-danger p-2 fs-6"><i class="bi bi-x-circle"></i> Backend Desconectado</span>
                <?php else: ?>
                    <span class="badge bg-success p-2 fs-6"><i class="bi bi-check-circle"></i> Backend Conectado (MariaDB Privado)</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($conn->connect_error): ?>
            <div class="alert alert-danger shadow-sm">
                <strong>Error de infraestructura:</strong> El servidor Frontend no puede comunicarse con la base de datos aislada. <br>
                Detalle: <?php echo $conn->connect_error; ?>
            </div>
        <?php else: ?>

            <?php if (!isset($_SESSION['usuario'])): ?>
                <div class="row justify-content-center my-5">
                    <div class="col-md-5">
                        <div class="card p-4 shadow-sm border-top border-primary border-4">
                            <div class="text-center mb-4">
                                <i class="bi bi-person-circle text-primary" style="font-size: 3.5rem;"></i>
                                <h4 class="fw-bold mt-2">Autenticación de Usuarios</h4>
                                <p class="text-muted small">Inicie sesión para acceder al contenido del proyecto</p>
                            </div>

                            <?php if ($error_msg != ""): ?>
                                <div class="alert alert-danger p-2 small text-center"><i class="bi bi-exclamation-triangle"></i> <?php echo $error_msg; ?></div>
                            <?php endif; ?>

                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Nombre de Usuario</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input type="text" name="username" class="form-control" placeholder="ej: usuario123" required>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold small">Contraseña</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                                    </div>
                                </div>
                                <button type="submit" name="login_btn" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">Iniciar Sesión</button>
                            </form>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="row g-4">
                    
                    <div class="col-12">
                        <div class="card p-4 bg-white d-flex flex-row justify-content-between align-items-center shadow-sm">
                            <div>
                                <h2 class="fw-bold m-0 text-dark">¡Bienvenido/a, <?php echo htmlspecialchars($_SESSION['usuario']); ?>!</h2>
                                <p class="text-muted m-0 mt-1">Has accedido correctamente al entorno del TFG.</p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary text-white badge-role mb-2 d-inline-block"><i class="bi bi-bookmark-star"></i> Rol: <?php echo $_SESSION['role'] ?? $_SESSION['rol']; ?></span>
                                <br>
                                <a href="?action=logout" class="btn btn-danger btn-sm fw-bold shadow-sm"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a>
                            </div>
                        </div>
                    </div>

                    <?php if ($_SESSION['rol'] === 'Administrador'): ?>
                        
                        <div class="col-12 animate__animated animate__fadeIn">
                            <div class="card p-4 border-top border-danger border-4 shadow-sm">
                                <h4 class="fw-bold mb-3 text-danger"><i class="bi bi-terminal-dark"></i> Panel de Control del Administrador (Logs de Acceso Globales)</h4>
                                <p class="text-muted small">Esta sección solo es visible para tu usuario. Muestra en tiempo real los intentos de acceso y las direcciones IP origen de todos los profesores y alumnos.</p>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped align-middle mt-2">
                                        <thead class="table-dark">
                                            <tr>
                                                <th width="10%">ID Log</th>
                                                <th width="30%">Usuario Consultado</th>
                                                <th width="25%">Fecha y Hora</th>
                                                <th width="20%">IP Pública de Origen</th>
                                                <th width="15%">Resultado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Extracción en tiempo real de los logs de auditoría
                                            $sql_logs = "SELECT id, username, fecha, ip_origen, resultado FROM accesos ORDER BY id DESC LIMIT 15";
                                            $result_logs = $conn->query($sql_logs);

                                            if ($result_logs && $result_logs->num_rows > 0) {
                                                while($row = $result_logs->fetch_assoc()) {
                                                    echo "<tr>";
                                                    echo "<td><span class='badge bg-secondary'>" . $row['id'] . "</span></td>";
                                                    echo "<td class='fw-bold text-primary'>" . htmlspecialchars($row['username']) . "</td>";
                                                    echo "<td><i class='bi bi-clock text-muted'></i> " . $row['fecha'] . "</td>";
                                                    echo "<td><code class='text-dark'>" . $row['ip_origen'] . "</code></td>";
                                                    
                                                    if ($row['resultado'] == 'EXITOSO') {
                                                        echo "<td><span class='badge bg-success'><i class='bi bi-check-circle-fill'></i> " . $row['resultado'] . "</span></td>";
                                                    } else {
                                                        echo "<td><span class='badge bg-danger'><i class='bi bi-x-octagon-fill'></i> " . $row['resultado'] . "</span></td>";
                                                    }
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='5' class='text-center py-4 text-muted'>No hay logs registrados.</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        
                        <div class="col-12 animate__animated animate__fadeIn">
                            <h3 class="fw-bold mb-4 border-bottom pb-2 text-secondary"><i class="bi bi-diagram-3"></i> Arquitectura del Sistema Desplegado</h3>
                            <div class="row g-4 text-center">
                                <div class="col-md-3">
                                    <div class="card card-preview h-100 p-4 bg-white">
                                        <i class="bi bi-code-square feature-icon"></i>
                                        <h5 class="fw-bold">Terraform (IaC)</h5>
                                        <p class="text-muted small">Infraestructura definida como código. Permite despliegues idénticos, rápidos y control de versiones en repositorios Git.</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card card-preview h-100 p-4 bg-white">
                                        <i class="bi bi-google feature-icon text-danger"></i>
                                        <h5 class="fw-bold">Google Cloud</h5>
                                        <p class="text-muted small">Red VPC personalizada con subredes orientadas a seguridad, Cloud NAT y reglas de firewall estrictas.</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card card-preview h-100 p-4 bg-white">
                                        <i class="bi bi-server feature-icon text-success"></i>
                                        <h5 class="fw-bold">Capa Frontend</h5>
                                        <p class="text-muted small">Servidor Debian 11 público ejecutando Apache y PHP. Procesa las solicitudes HTTP y gestiona las sesiones web.</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card card-preview h-100 p-4 bg-white">
                                        <i class="bi bi-database-fill feature-icon text-warning"></i>
                                        <h5 class="fw-bold">Capa Backend</h5>
                                        <p class="text-muted small">Base de datos MariaDB aislada en subred privada. Inaccesible desde el exterior para asegurar la persistencia.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endif; ?>

                </div>
            <?php endif; ?>

        <?php 
            $conn->close();
        endif; 
        ?>

    </main>

    <footer class="text-center py-4 text-muted bg-white border-top mt-5">
        <div class="container">
            <p class="mb-0">Proyecto de Fin de Grado - Administración de Sistemas Informáticos en Red</p>
            <small>Entorno dinámico securizado mediante políticas RBAC.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>