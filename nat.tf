# ==============================================================================
# ARCHIVO: nat.tf
# DESCRIPCIÓN: Configuración de la pasarela de traducción de direcciones (Cloud NAT).
# OBJETIVO: Permitir que los recursos de la subred privada (Base de Datos) tengan 
# salida a Internet para descargar actualizaciones sin exponer una IP pública, 
# bloqueando cualquier intento de conexión entrante (Arquitectura Zero Trust).
# ==============================================================================

# ------------------------------------------------------------------------------
# 1. CREACIÓN DEL ENRUTADOR VIRTUAL (CLOUD ROUTER)
# ------------------------------------------------------------------------------
# El servicio Cloud NAT no funciona de forma aislada, requiere un Cloud Router 
# que gestione el enrutamiento dinámico (BGP) y sirva como punto de anclaje.
resource "google_compute_router" "router" {
  name    = "router-proyecto-asir"
  region  = "europe-southwest1" # Misma región que la VPC para minimizar latencia
  
  # Se asocia el enrutador directamente a la red virtual (VPC) del proyecto
  network = google_compute_network.vpc_asir.id
}

# ------------------------------------------------------------------------------
# 2. CONFIGURACIÓN DEL SERVICIO CLOUD NAT
# ------------------------------------------------------------------------------
resource "google_compute_router_nat" "nat" {
  name   = "nat-proyecto-asir"
  router = google_compute_router.router.name
  region = google_compute_router.router.region

  # ASIGNACIÓN DE IP: "AUTO_ONLY"
  # Se delega a Google Cloud la provisión y gestión automática de las IPs 
  # públicas de salida. Esto optimiza costes al no reservar IPs estáticas fijas.
  nat_ip_allocate_option = "AUTO_ONLY"

  # CONTROL DE ALCANCE: "LIST_OF_SUBNETWORKS"
  # Por seguridad, no se da salida a Internet a toda la VPC por defecto,
  # sino que se restringe explícitamente a las subredes que declaremos debajo.
  source_subnetwork_ip_ranges_to_nat = "LIST_OF_SUBNETWORKS"

  # APLICACIÓN DE LA REGLA: Exclusivamente para la Subred Privada
  subnetwork {
    # Se vincula el NAT al ID de la subred privada (donde reside MariaDB)
    name = google_compute_subnetwork.subred_privada.id
    
    # Se aplica la traducción a todos los rangos de IP internas de dicha subred
    source_ip_ranges_to_nat = ["ALL_IP_RANGES"]
  }
}