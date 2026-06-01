# =========================================================================
# 1. SERVIDOR WEB PÚBLICO (CAPA DE PRESENTACIÓN)
# =========================================================================
resource "google_compute_instance" "servidor_web" {
  name         = "servidor-web-asir"
  machine_type = "e2-micro"
  zone         = "europe-southwest1-a"

  tags = ["servidor-web"]

  boot_disk {
    initialize_params {
      image = "debian-cloud/debian-11"
    }
  }

  network_interface {
    network    = google_compute_network.vpc_asir.id
    subnetwork = google_compute_subnetwork.subred_publica.id

    access_config {
      # Asigna una IP pública dinámica para acceso desde Internet
    }
  }

  # Script para instalar Apache, PHP y programar la conexión
  metadata_startup_script = <<-EOF
    #!/bin/bash

    # Ajustar la zona horaria del servidor a España
    timedatectl set-timezone Europe/Madrid

    apt-get update
    apt-get install -y apache2 php libapache2-mod-php php-mysql
    rm -f /var/www/html/index.html

    cat << 'PHP_EOF' > /var/www/html/index.php
    <?php
    $servername = "10.0.2.4";
    $username = "user_tfg";
    $password = "Password123!";
    $dbname = "tfg_db";

    // Crear la conexión con la Base de Datos privada
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Comprobar si la conexión tiene éxito
    if ($conn->connect_error) {
        die("<html><body><h1>Error de conexión con el backend:</h1>" . $conn->connect_error . "</body></html>");
    }

    echo "<html><head><meta charset='utf-8'><title>TFG Julio Sande</title></head><body style='font-family:Arial,sans-serif; margin:40px;'>";
    echo "<h1 style='color:#1a73e8;'>¡Aplicación Multi-Capa Desplegada con Éxito!</h1>";
    echo "<h3>Infraestructura automatizada con Terraform por Julio Sande</h3>";
    echo "<p><strong>Estado de la red:</strong> Conectado correctamente al servidor de datos privado en la IP interna: <code style='background:#f1f3f4; padding:4px;'> " . $servername . "</code></p>";
    echo "<h4>Datos extraídos de la base de datos MariaDB en tiempo real:</h4>";
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
    echo "<tr style='background:#f1f3f4;'><th>ID</th><th>Nombre del Invitado</th><th>Fecha de Registro</th></tr>";

    $sql = "SELECT id, nombre, fecha FROM invitados";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row["id"] . "</td><td>" . $row["nombre"] . "</td><td>" . $row["fecha"] . "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='3'>0 resultados encontrados</td></tr>";
    }
    echo "</table></body></html>";
    $conn->close();
    ?>
    PHP_EOF

    systemctl restart apache2
  EOF
}

# =========================================================================
# 2. SERVIDOR DE BASE DE DATOS PRIVADO (CAPA DE DATOS)
# =========================================================================
resource "google_compute_instance" "base_datos" {
  name         = "servidor-db-asir"
  machine_type = "e2-micro"
  zone         = "europe-southwest1-a"

  tags = ["base-datos"]

  boot_disk {
    initialize_params {
      image = "debian-cloud/debian-11"
    }
  }

  network_interface {
    network    = google_compute_network.vpc_asir.id
    subnetwork = google_compute_subnetwork.subred_privada.id
    # Sin access_config = No tiene IP pública (Aislamiento total)
  }

  # Script para instalar MariaDB, permitir acceso interno y crear datos de prueba
  metadata_startup_script = <<-EOF
    #!/bin/bash

    # Ajustar la zona horaria del servidor a España
    timedatectl set-timezone Europe/Madrid

    apt-get update
    apt-get install -y mariadb-server
    
    # Permitir que MariaDB escuche en la red interna
    sed -i 's/bind-address.*/bind-address = 0.0.0.0/' /etc/mysql/mariadb.conf.d/50-server.cnf
    systemctl restart mariadb
    systemctl enable mariadb

    # Configuración del motor de base de datos y privilegios
    mysql -e "CREATE DATABASE IF NOT EXISTS tfg_db;"
    mysql -e "CREATE USER IF NOT EXISTS 'user_tfg'@'%' IDENTIFIED BY 'Password123!';"
    mysql -e "GRANT ALL PRIVILEGES ON tfg_db.* TO 'user_tfg'@'%';"
    mysql -e "FLUSH PRIVILEGES;"
    
    # Estructura de tablas y carga de datos iniciales para la demostración
    mysql -D tfg_db -e "CREATE TABLE IF NOT EXISTS invitados (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(50), fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP);"
    mysql -D tfg_db -e "INSERT INTO invitados (nombre) VALUES ('Julio Sande (Administrador)'), ('Profesor del Tribunal'), ('Inversor Google Cloud');"
  EOF
}