# ==============================================================================
# ARCHIVO: firewall.tf
# DESCRIPCIÓN: Configuración de Políticas de Cortafuegos Perimetral e Interno.
# OBJETIVO: Implementar una arquitectura de red "Zero Trust" (Confianza Cero).
# Por defecto, GCP bloquea todo el tráfico entrante. Aquí se declaran las 
# únicas tres excepciones permitidas, segmentadas rigurosamente mediante etiquetas.
# ==============================================================================

# ------------------------------------------------------------------------------
# 1. REGLA PÚBLICA: Tráfico Web (HTTP/HTTPS)
# ------------------------------------------------------------------------------
resource "google_compute_firewall" "allow_web" {
  name    = "permitir-web"
  network = google_compute_network.vpc_asir.id

  # PUERTOS HABILITADOS: 80 (HTTP) y 443 (HTTPS).
  allow {
    protocol = "tcp"
    ports    = ["80", "443"] 
  }

  # ORIGEN: "0.0.0.0/0" permite el acceso desde cualquier dirección IP de Internet.
  source_ranges = ["0.0.0.0/0"] 
  
  # MICRO-SEGMENTACIÓN (Target Tags): La regla NO se aplica a toda la red de la VPC, 
  # sino ÚNICAMENTE a las máquinas virtuales que tengan la etiqueta "servidor-web".
  target_tags   = ["servidor-web"] 
}

# ------------------------------------------------------------------------------
# 2. REGLA ADMINISTRATIVA: Acceso Seguro de Gestión (SSH)
# ------------------------------------------------------------------------------
resource "google_compute_firewall" "allow_ssh" {
  name    = "permitir-ssh"
  network = google_compute_network.vpc_asir.id

  # PROTOCOLO: Se habilita el puerto 22 exclusivo para conexiones de terminal.
  allow {
    protocol = "tcp"
    ports    = ["22"] 
  }

  # ORIGEN: En este entorno de pruebas ágil se abre a Internet (0.0.0.0/0), pero 
  # en un entorno Enterprise de producción, aquí se declararía la IP pública fija 
  # del centro educativo o la de un servidor "Bastión" para máxima seguridad.
  source_ranges = ["0.0.0.0/0"] 
}

# ------------------------------------------------------------------------------
# 3. REGLA PRIVADA: Aislamiento del Motor de Base de Datos
# ------------------------------------------------------------------------------
resource "google_compute_firewall" "allow_internal_db" {
  name    = "permitir-db-interna"
  network = google_compute_network.vpc_asir.id

  allow {
    protocol = "tcp"
    ports    = ["3306"] # Puerto oficial del demonio MariaDB/MySQL
  }

  # ORIGEN (LA CLAVE DEL ZERO TRUST): El acceso a la Base de Datos está restringido.
  # Solo se aceptan peticiones que provengan estrictamente del bloque CIDR 
  # "10.0.1.0/24", que es el rango exacto donde vive nuestro servidor web (Frontend).
  # Cualquier otro intento de conexión lateral es descartado silenciosamente.
  source_ranges = ["10.0.1.0/24"] 
  
  # DESTINO: Se aplica exclusivamente a la máquina backend.
  target_tags   = ["base-datos"]
}