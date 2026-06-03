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
    access_config {}
  }

  metadata_startup_script = <<-EOF
    #!/bin/bash
    timedatectl set-timezone Europe/Madrid
    apt-get update
    apt-get install -y apache2 php libapache2-mod-php php-mysql
    rm -f /var/www/html/index.html

    cat << 'PHP_EOF' > /var/www/html/index.php
${file("web_TFG.php")}
PHP_EOF

    systemctl restart apache2
  EOF
}

# =========================================================================
# 2. SERVIDOR DE BASE DE DATOS PRIVADO (CAPA DE DATOS CON CONTROL AAA)
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

  service_account {
    scopes = ["cloud-platform"]
  }

  metadata_startup_script = <<-EOF
    #!/bin/bash
    timedatectl set-timezone Europe/Madrid
    apt-get update
    apt-get install -y mariadb-server cron

    sed -i 's/bind-address.*/bind-address = 0.0.0.0/' /etc/mysql/mariadb.conf.d/50-server.cnf
    systemctl restart mariadb
    systemctl enable mariadb

    # 1. Crear Base de Datos y Credenciales de Red
    mysql -e "CREATE DATABASE IF NOT EXISTS tfg_db;"
    mysql -e "CREATE USER IF NOT EXISTS 'user_tfg'@'%' IDENTIFIED BY 'Password123!';"
    mysql -e "GRANT ALL PRIVILEGES ON tfg_db.* TO 'user_tfg'@'%';"
    mysql -e "FLUSH PRIVILEGES;"
    
    # 2. Creación del esquema relacional de Seguridad (AAA)
    mysql -D tfg_db -e "CREATE TABLE IF NOT EXISTS usuarios (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE, password VARCHAR(64), rol VARCHAR(30));"
    mysql -D tfg_db -e "CREATE TABLE IF NOT EXISTS accesos (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50), fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP, ip_origen VARCHAR(45), resultado VARCHAR(20));"

    # 3. Poblado Masivo de la Base de Datos (Profesores y Alumnos - Hasheados en SHA-256)
    mysql -D tfg_db -e "INSERT INTO usuarios (username, password, rol) VALUES \
      ('julio.sande', SHA2('Julio2026!', 256), 'Administrador'), \
      ('sergio.admin', SHA2('Asir2026!', 256), 'Profesor'), \
      ('sergio.carracedo', SHA2('Asir2026!', 256), 'Profesor'), \
      ('brais.arias', SHA2('Asir2026!', 256), 'Profesor'), \
      ('manuel.rico', SHA2('Asir2026!', 256), 'Profesor'), \
      ('ignacio.gay', SHA2('Asir2026!', 256), 'Profesor'), \
      ('antonio.lopeznino', SHA2('Asir2026!', 256), 'Profesor'), \
      ('maria.suarez', SHA2('Asir2026!', 256), 'Profesor'), \
      ('francisco.carro', SHA2('Asir2026!', 256), 'Profesor'), \
      ('yolanda.barros', SHA2('Asir2026!', 256), 'Profesor'), \
      ('hugo.diaz', SHA2('Asir2026!', 256), 'Profesor'), \
      ('angel.ramos', SHA2('Asir2026!', 256), 'Profesor'), \
      ('anton.agra', SHA2('Asir2026!', 256), 'Alumno'), \
      ('julian.ameijenda', SHA2('Asir2026!', 256), 'Alumno'), \
      ('uxia.bello', SHA2('Asir2026!', 256), 'Alumno'), \
      ('dario.caridad', SHA2('Asir2026!', 256), 'Alumno'), \
      ('edgar.castro', SHA2('Asir2026!', 256), 'Alumno'), \
      ('sergio.cruz', SHA2('Asir2026!', 256), 'Alumno'), \
      ('miguel.darriba', SHA2('Asir2026!', 256), 'Alumno'), \
      ('xabier.garcia', SHA2('Asir2026!', 256), 'Alumno'), \
      ('hugo.lopez', SHA2('Asir2026!', 256), 'Alumno'), \
      ('luis.lopez', SHA2('Asir2026!', 256), 'Alumno'), \
      ('yago.losada', SHA2('Asir2026!', 256), 'Alumno'), \
      ('jesus.neira', SHA2('Asir2026!', 256), 'Alumno'), \
      ('christian.pena', SHA2('Asir2026!', 256), 'Alumno'), \
      ('laura.ramallal', SHA2('Asir2026!', 256), 'Alumno'), \
      ('yasmine.rial', SHA2('Asir2026!', 256), 'Alumno'), \
      ('bruno.sande', SHA2('Asir2026!', 256), 'Alumno'), \
      ('mateo.suena', SHA2('Asir2026!', 256), 'Alumno'), \
      ('irene.torrado', SHA2('Asir2026!', 256), 'Alumno'), \
      ('pablo.vazquez', SHA2('Asir2026!', 256), 'Alumno'), \
      ('aitor.vigo', SHA2('Asir2026!', 256), 'Alumno'), \
      ('teo.zamora', SHA2('Asir2026!', 256), 'Alumno');"

    # 4. Inyección del Script de Backup Diario
    cat << 'EOF_BACKUP' > /usr/local/bin/backup_diario.sh
#!/bin/bash
FECHA=$(date +"%Y%m%d_%H%M%S")
mysqldump tfg_db > /tmp/backup_tfg_db_$FECHA.sql
gsutil cp /tmp/backup_tfg_db_$FECHA.sql gs://${google_storage_bucket.bucket_backups.name}/
rm /tmp/backup_tfg_db_$FECHA.sql
EOF_BACKUP

    chmod +x /usr/local/bin/backup_diario.sh
    (crontab -l 2>/dev/null; echo "0 3 * * * /usr/local/bin/backup_diario.sh") | crontab -
  EOF
}

# =========================================================================
# 3. ALMACENAMIENTO EN LA NUBE (BUCKET PARA LOS COPIAS DE SEGURIDAD)
# =========================================================================
resource "google_storage_bucket" "bucket_backups" {
  name          = "bucket-tfg-juliosande-backups" 
  location      = "EUROPE-SOUTHWEST1"
  force_destroy = true 
}