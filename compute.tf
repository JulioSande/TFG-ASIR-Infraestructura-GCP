# ==============================================================================
# ARCHIVO: compute.tf
# DESCRIPCIÓN: Aprovisionamiento de la capa de cómputo (Máquinas Virtuales).
# OBJETIVO: Desplegar la arquitectura Multi-Capa (Frontend y Backend) inyectando
# la configuración del sistema operativo (Cloud-init) de forma automatizada.
# ==============================================================================

# ------------------------------------------------------------------------------
# 1. SERVIDOR WEB PÚBLICO (CAPA DE PRESENTACIÓN / FRONTEND)
# ------------------------------------------------------------------------------
resource "google_compute_instance" "servidor_web" {
  name         = "servidor-web-asir"
  # OPTIMIZACIÓN FINOPS: Se elige la familia e2-micro (2 vCPU, 1GB RAM) por su 
  # bajísimo coste y eficiencia para un servidor web ligero.
  machine_type = "e2-micro"
  zone         = "europe-southwest1-a" # Zona específica dentro de la región de Madrid

  # ETIQUETA DE RED: Crucial para aplicar la regla de firewall "allow_web".
  tags = ["servidor-web"]

  boot_disk {
    initialize_params {
      # SISTEMA OPERATIVO: Debian 11 nativo de Google. Se elige por su estabilidad 
      # y mínimo consumo de recursos RAM frente a otras alternativas.
      image = "debian-cloud/debian-11"
    }
  }

  network_interface {
    network    = google_compute_network.vpc_asir.id
    subnetwork = google_compute_subnetwork.subred_publica.id
    
    # EXPOSICIÓN A INTERNET: Al declarar el bloque vacío 'access_config {}', 
    # GCP le asigna automáticamente una IP Pública Efímera a la interfaz.
    access_config {}
  }

  # AUTOMATIZACIÓN (CLOUD-INIT): Script Bash que se ejecuta con privilegios root
  # exclusivamente en el primer arranque de la máquina.
  metadata_startup_script = <<-EOF
    #!/bin/bash
    # Sincronización horaria para que los logs (Auditoría) sean exactos
    timedatectl set-timezone Europe/Madrid
    
    # Instalación desatendida del Stack Web (Apache + extensiones PHP)
    apt-get update
    apt-get install -y apache2 php libapache2-mod-php php-mysql
    
    # Limpieza del index HTML por defecto de Apache
    rm -f /var/www/html/index.html

    # INYECCIÓN DE CÓDIGO (IaC avanzado): Terraform lee el archivo PHP local
    # y lo incrusta directamente dentro de la máquina en la nube.
    cat << 'PHP_EOF' > /var/www/html/index.php
${file("web_TFG.php")}
PHP_EOF

    # Reinicio del servicio para aplicar la carga del módulo PHP
    systemctl restart apache2
  EOF
}

# ------------------------------------------------------------------------------
# 2. SERVIDOR DE BASE DE DATOS PRIVADO (CAPA DE DATOS / BACKEND)
# ------------------------------------------------------------------------------
resource "google_compute_instance" "base_datos" {
  name         = "servidor-db-asir"
  machine_type = "e2-micro"
  zone         = "europe-southwest1-a"

  # ETIQUETA DE RED: Crucial para aplicar la regla de firewall interna (Puerto 3306).
  tags = ["base-datos"]

  boot_disk {
    initialize_params {
      image = "debian-cloud/debian-11"
    }
  }

  network_interface {
    network    = google_compute_network.vpc_asir.id
    subnetwork = google_compute_subnetwork.subred_privada.id
    # AISLAMIENTO TOTAL (Zero Trust): Se omite intencionadamente 'access_config'.
    # La máquina nace sin IP pública, blindándola contra ataques desde el exterior.
  }

  # PERMISOS IAM: Asignación de una cuenta de servicio para que esta máquina 
  # pueda subir los backups a Cloud Storage de forma nativa sin exponer contraseñas.
  service_account {
    scopes = ["cloud-platform"]
  }

  # AUTOMATIZACIÓN DEL BACKEND (MariaDB + Cron + AAA)
  metadata_startup_script = <<-EOF
    #!/bin/bash
    timedatectl set-timezone Europe/Madrid
    apt-get update
    apt-get install -y mariadb-server cron

    # APERTURA LOCAL: Se modifica el socket de MariaDB para que escuche en todas 
    # las interfaces (0.0.0.0) y no solo en localhost, permitiendo que el servidor 
    # web pueda conectarse a ella a través de la VPC.
    sed -i 's/bind-address.*/bind-address = 0.0.0.0/' /etc/mysql/mariadb.conf.d/50-server.cnf
    systemctl restart mariadb
    systemctl enable mariadb

    # --- FASE 1: DESPLIEGUE DEL ESQUEMA DE BASE DE DATOS (IDEMPOTENTE) ---
    mysql -e "CREATE DATABASE IF NOT EXISTS tfg_db;"
    mysql -e "CREATE USER IF NOT EXISTS 'user_tfg'@'%' IDENTIFIED BY 'Password123!';"
    mysql -e "GRANT ALL PRIVILEGES ON tfg_db.* TO 'user_tfg'@'%';"
    mysql -e "FLUSH PRIVILEGES;"
    
    # --- FASE 2: CREACIÓN DE LA ESTRUCTURA DE SEGURIDAD (AAA y RBAC) ---
    # Tabla de identidades y roles
    mysql -D tfg_db -e "CREATE TABLE IF NOT EXISTS usuarios (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) UNIQUE, password VARCHAR(64), rol VARCHAR(30));"
    # Tabla de auditoría activa (Trazabilidad)
    mysql -D tfg_db -e "CREATE TABLE IF NOT EXISTS accesos (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50), fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP, ip_origen VARCHAR(45), resultado VARCHAR(20));"
    
    # --- FASE 3: POBLADO CRIPTOGRÁFICO DE USUARIOS ---
    # Las contraseñas se protegen en su nacimiento. Nunca se escriben en texto plano 
    # en el disco, pasando directamente por la función criptográfica SHA-256 de MariaDB.
    mysql -D tfg_db -e "INSERT IGNORE INTO usuarios (username, password, rol) VALUES \
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

    # --- FASE 4: PLAN DE CONTINUIDAD (DISASTER RECOVERY) ---
    # Creación del script Bash para automatizar el volcado lógico de la DB.
    cat << 'EOF_BACKUP' > /usr/local/bin/backup_diario.sh
#!/bin/bash
FECHA=$(date +"%Y%m%d_%H%M%S")
mysqldump tfg_db > /tmp/backup_tfg_db_$FECHA.sql

# Interpolación de Terraform: Vincula el destino con el nombre del Bucket creado.
gsutil cp /tmp/backup_tfg_db_$FECHA.sql gs://${google_storage_bucket.bucket_backups.name}/
rm /tmp/backup_tfg_db_$FECHA.sql
EOF_BACKUP

    # Se otorgan permisos de ejecución al script y se programa en Cron
    # para que se ejecute de forma desatendida todos los días a las 03:00 AM.
    chmod +x /usr/local/bin/backup_diario.sh
    (crontab -l 2>/dev/null; echo "0 3 * * * /usr/local/bin/backup_diario.sh") | crontab -
  EOF
}

# ------------------------------------------------------------------------------
# 3. ALMACENAMIENTO EXTERNO (BUCKET PARA LOS COPIAS DE SEGURIDAD)
# ------------------------------------------------------------------------------
resource "google_storage_bucket" "bucket_backups" {
  name          = "bucket-tfg-juliosande-backups" 
  location      = "EUROPE-SOUTHWEST1"
  
  # DIRECTIVA FINOPS: force_destroy permite a Terraform destruir el Bucket 
  # aunque contenga backups dentro. Es obligatorio para que el comando 
  # 'terraform destroy' no falle al apagar el entorno al final de la jornada.
  force_destroy = true 
}