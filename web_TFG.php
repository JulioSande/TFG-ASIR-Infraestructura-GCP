<?php
// ============================================================================
// PRESENTACIÓN TFG ASIR - JULIO SANDE
// ============================================================================
$servername = "10.0.2.2"; // Cambia esta IP si tu BD tiene una distinta
$username = "user_tfg";
$password = "Password123!";
$dbname = "tfg_db";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TFG ASIR - Julio Sande</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
            border-bottom: 5px solid #ffc107;
        }
        .feature-icon {
            font-size: 2.5rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>

    <header class="hero-section text-center shadow">
        <div class="container">
            <h1 class="display-4 fw-bold"><i class="bi bi-cloud-check"></i> Proyecto TFG ASIR</h1>
            <p class="lead mt-3">Despliegue de Infraestructura Cloud Automatizada con Terraform en GCP</p>
            <span class="badge bg-warning text-dark mt-2 fs-6">Autor: Julio Sande</span>
        </div>
    </header>

    <main class="container">
        
        <section class="mb-5">
            <h3 class="fw-bold mb-4 border-bottom pb-2 text-secondary"><i class="bi bi-diagram-3"></i> Arquitectura del Sistema</h3>
            <div class="row g-4 text-center">
                <div class="col-md-3">
                    <div class="card h-100 p-4">
                        <i class="bi bi-code-square feature-icon"></i>
                        <h5 class="fw-bold">Terraform (IaC)</h5>
                        <p class="text-muted small">Infraestructura definida como código. Permite despliegues idénticos, rápidos y control de versiones.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 p-4">
                        <i class="bi bi-google feature-icon text-danger"></i>
                        <h5 class="fw-bold">Google Cloud</h5>
                        <p class="text-muted small">Red VPC personalizada con subredes separadas, Cloud NAT y reglas de firewall Zero Trust.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 p-4">
                        <i class="bi bi-server feature-icon text-success"></i>
                        <h5 class="fw-bold">Capa Frontend</h5>
                        <p class="text-muted small">Servidor Debian 11 público con Apache y PHP. Actúa como capa de presentación de este proyecto.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 p-4">
                        <i class="bi bi-database-fill feature-icon text-warning"></i>
                        <h5 class="fw-bold">Capa Backend</h5>
                        <p class="text-muted small">Base de datos MariaDB aislada en subred privada. Inaccesible desde el exterior por seguridad.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-5">
            <div class="card p-4 border-top border-primary border-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold m-0 text-secondary"><i class="bi bi-table"></i> Conexión a Base de Datos (Tiempo Real)</h3>
                    <?php if ($conn->connect_error): ?>
                        <span class="badge bg-danger fs-6"><i class="bi bi-x-circle"></i> Error de conexión</span>
                    <?php else: ?>
                        <span class="badge bg-success fs-6"><i class="bi bi-check-circle"></i> Backend Conectado (<?php echo $servername; ?>)</span>
                    <?php endif; ?>
                </div>

                <?php if ($conn->connect_error): ?>
                    <div class="alert alert-danger">
                        <strong>Error crítico:</strong> No se pudo conectar al backend. <br>
                        Detalle técnico: <?php echo $conn->connect_error; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col" width="10%">ID</th>
                                    <th scope="col" width="50%">Nombre del Registro</th>
                                    <th scope="col" width="40%">Fecha y Hora (Local)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT id, nombre, fecha FROM invitados ORDER BY id ASC";
                                $result = $conn->query($sql);

                                if ($result && $result->num_rows > 0) {
                                    while($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td><span class='badge bg-secondary rounded-pill'>" . $row["id"] . "</span></td>";
                                        echo "<td class='fw-semibold'>" . htmlspecialchars($row["nombre"]) . "</td>";
                                        echo "<td><i class='bi bi-clock text-muted'></i> " . $row["fecha"] . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3' class='text-center py-4'>La base de datos está vacía.</td></tr>";
                                }
                                $conn->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <footer class="text-center py-4 text-muted bg-white border-top mt-5">
        <div class="container">
            <p class="mb-0">Proyecto de Fin de Grado - Administración de Sistemas Informáticos en Red</p>
            <small>Desplegado dinámicamente el <?php echo date("d/m/Y H:i:s"); ?></small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>