# 1. Creación del Servidor Web en la Subred Pública
resource "google_compute_instance" "servidor_web" {
  name         = "servidor-web-asir"
  machine_type = "e2-micro" # Máquina económica/gratuita, perfecta para pruebas
  zone         = "europe-southwest1-a" # Zona dentro de la región de Madrid

  # Esta etiqueta vincula la máquina con la regla de firewall que hicimos
  tags = ["servidor-web"]

  # Definimos el disco duro y el Sistema Operativo (Debian 11)
  boot_disk {
    initialize_params {
      image = "debian-cloud/debian-11"
    }
  }

  # Conectamos la máquina a nuestra red
  network_interface {
    network    = google_compute_network.vpc_asir.id
    subnetwork = google_compute_subnetwork.subred_publica.id

    # Este bloque 'access_config' le dice a Google que le dé una IP Pública
    access_config {
    }
  }

  # Script de inicio (Cloud-init): Se ejecuta como root la primera vez que arranca
  metadata_startup_script = <<-EOF
    #!/bin/bash
    apt-get update
    apt-get install -y apache2
    systemctl start apache2
    systemctl enable apache2
    echo "<html><head><meta charset='utf-8'></head><body><h1>¡El Servidor Web de Julio ha sido desplegado con Terraform!</h1></body></html>" > /var/www/html/index.html
  EOF
}


# 2. Creación del Servidor de Base de Datos en la Subred Privada
resource "google_compute_instance" "base_datos" {
  name         = "servidor-db-asir"
  machine_type = "e2-micro"
  zone         = "europe-southwest1-a"

  # Vincula esta máquina con la regla de firewall que aísla la DB
  tags = ["base-datos"]

  boot_disk {
    initialize_params {
      image = "debian-cloud/debian-11"
    }
  }

  network_interface {
    network    = google_compute_network.vpc_asir.id
    subnetwork = google_compute_subnetwork.subred_privada.id
    
    # ¡MUY IMPORTANTE! Aquí NO ponemos el bloque 'access_config'.
    # Al no ponerlo, Google sabe que esta máquina NO debe tener IP pública.
  }

  # Script de inicio para instalar el motor de Base de Datos (MariaDB)
  metadata_startup_script = <<-EOF
    #!/bin/bash
    apt-get update
    apt-get install -y mariadb-server
    systemctl start mariadb
    systemctl enable mariadb
  EOF
}