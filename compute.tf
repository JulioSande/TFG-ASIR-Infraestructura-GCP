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
    echo "<h1>¡El Servidor Web de Julio ha sido desplegado con Terraform!</h1>" > /var/www/html/index.html
  EOF
}