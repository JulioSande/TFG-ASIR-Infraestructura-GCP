# 1. Creación de la Red Virtual (VPC) principal
resource "google_compute_network" "vpc_asir" {
  name                    = "vpc-proyecto-asir"
  auto_create_subnetworks = false 
}

# 2. Creación de la Subred Pública (Para el Servidor Web)
resource "google_compute_subnetwork" "subred_publica" {
  name          = "subred-publica"
  ip_cidr_range = "10.0.1.0/24" 
  region        = "europe-southwest1" 
  network       = google_compute_network.vpc_asir.id
}

# 3. Creación de la Subred Privada (Para la Base de Datos)
resource "google_compute_subnetwork" "subred_privada" {
  name          = "subred-privada"
  ip_cidr_range = "10.0.2.0/24" 
  region        = "europe-southwest1" 
  network       = google_compute_network.vpc_asir.id
}