# ==============================================================================
# ARCHIVO: network.tf
# DESCRIPCIÓN: Definición de la Topología de Red (VPC y Subredes).
# OBJETIVO: Desplegar la infraestructura base de red (Virtual Private Cloud) 
# aplicando un modelo de diseño segmentado. Se aísla físicamente la capa de 
# presentación (Frontend) de la capa de datos (Backend) para máxima seguridad.
# ==============================================================================

# ------------------------------------------------------------------------------
# 1. CREACIÓN DE LA RED VIRTUAL PRIVADA (VPC)
# ------------------------------------------------------------------------------
resource "google_compute_network" "vpc_asir" {
  name                    = "vpc-proyecto-asir"
  
  # SEGURIDAD POR DISEÑO: Se deshabilita la creación automática de subredes.
  # Esto proporciona un control absoluto (modo Custom) sobre el enrutamiento y 
  # los bloques CIDR, evitando exponer redes innecesarias en otras regiones globales.
  auto_create_subnetworks = false 
}

# ------------------------------------------------------------------------------
# 2. CAPA PERIMETRAL: SUBRED PÚBLICA (FRONTEND)
# ------------------------------------------------------------------------------
resource "google_compute_subnetwork" "subred_publica" {
  name          = "subred-publica"
  
  # ESPACIO DE DIRECCIONAMIENTO: Capacidad para 256 IPs lógicas.
  ip_cidr_range = "10.0.1.0/24" 
  
  # UBICACIÓN ESTRATÉGICA: Desplegada en la región de Madrid para garantizar 
  # la mínima latencia a los usuarios finales y cumplir con la normativa europea.
  region        = "europe-southwest1" 
  
  # VINCULACIÓN: Se asocia explícitamente a la VPC creada en el paso anterior.
  network       = google_compute_network.vpc_asir.id
}

# ------------------------------------------------------------------------------
# 3. CAPA AISLADA: SUBRED PRIVADA (BACKEND / BASE DE DATOS)
# ------------------------------------------------------------------------------
resource "google_compute_subnetwork" "subred_privada" {
  name          = "subred-privada"
  
  # SEGMENTACIÓN LÓGICA: Se asigna un bloque CIDR distinto al del Frontend.
  ip_cidr_range = "10.0.2.0/24" 
  region        = "europe-southwest1" 
  network       = google_compute_network.vpc_asir.id
  
  # NOTA DE ARQUITECTURA: Al aprovisionar los recursos de Base de Datos
  # dentro de este segmento, quedan confinados y protegidos de Internet
  # por defecto, cumpliendo con el principio del menor privilegio.
}