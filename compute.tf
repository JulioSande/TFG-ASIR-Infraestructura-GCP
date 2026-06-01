# =========================================================================
# 1. SERVIDOR WEB PÚBLICO (CAPA DE PRESENTACIÓN PREMIUM)
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
    access_config {}
  }

  metadata_startup_script = <<-EOF
    #!/bin/bash
    timedatectl set-timezone Europe/Madrid
    apt-get update
    apt-get install -y apache2 php libapache2-mod-php php-mysql
    rm -f /var/www/html/index.html

    cat << 'PHP_EOF' > /var/www/html/index.php
${file("web_claude.php")}
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
  }

  metadata_startup_script = <<-EOF
    #!/bin/bash
    timedatectl set-timezone Europe/Madrid
    apt-get update
    apt-get install -y mariadb-server

    sed -i 's/bind-address.*/bind-address = 0.0.0.0/' /etc/mysql/mariadb.conf.d/50-server.cnf
    systemctl restart mariadb
    systemctl enable mariadb

    mysql -e "CREATE DATABASE IF NOT EXISTS tfg_db;"
    mysql -e "CREATE USER IF NOT EXISTS 'user_tfg'@'%' IDENTIFIED BY 'Password123!';"
    mysql -e "GRANT ALL PRIVILEGES ON tfg_db.* TO 'user_tfg'@'%';"
    mysql -e "FLUSH PRIVILEGES;"
    
    mysql -D tfg_db -e "CREATE TABLE IF NOT EXISTS invitados (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(50), fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP);"
    mysql -D tfg_db -e "INSERT INTO invitados (nombre) VALUES ('Julio Sande (Administrador)'), ('Profesor del Tribunal'), ('Inversor Google Cloud');"
  EOF
}