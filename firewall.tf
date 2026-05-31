# 1. Regla Pública: Permitir tráfico Web (Internet -> Servidor Web)
resource "google_compute_firewall" "allow_web" {
  name    = "permitir-web"
  network = google_compute_network.vpc_asir.id

  allow {
    protocol = "tcp"
    ports    = ["80", "443"] # HTTP y HTTPS
  }

  source_ranges = ["0.0.0.0/0"] # 0.0.0.0/0 significa "Cualquier persona en Internet"
  target_tags   = ["servidor-web"] # Esta regla solo afectará a las máquinas que tengan esta etiqueta
}

# 2. Regla Administrativa: Permitir SSH (Para que podamos entrar a configurarlos)
resource "google_compute_firewall" "allow_ssh" {
  name    = "permitir-ssh"
  network = google_compute_network.vpc_asir.id

  allow {
    protocol = "tcp"
    ports    = ["22"] # Puerto estándar de conexión segura Linux
  }

  source_ranges = ["0.0.0.0/0"] 
}

# 3. Regla Privada: Permitir conexión a la Base de Datos SOLO desde la subred pública
resource "google_compute_firewall" "allow_internal_db" {
  name    = "permitir-db-interna"
  network = google_compute_network.vpc_asir.id

  allow {
    protocol = "tcp"
    ports    = ["3306"] # Puerto por defecto de MySQL / MariaDB
  }

  # ¡LA CLAVE DE LA SEGURIDAD! Solo dejamos pasar a las IPs de nuestra subred pública
  source_ranges = ["10.0.1.0/24"] 
  target_tags   = ["base-datos"]
}