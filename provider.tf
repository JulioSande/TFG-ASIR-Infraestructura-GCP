# ==============================================================================
# ARCHIVO: provider.tf
# DESCRIPCIÓN: Configuración central del motor de Terraform y el Proveedor Cloud.
# OBJETIVO: Declarar la conexión con Google Cloud Platform (GCP), estableciendo 
# el entorno de trabajo y bloqueando las versiones del código para garantizar 
# la inmutabilidad y evitar fallos por actualizaciones futuras.
# ==============================================================================

# ------------------------------------------------------------------------------
# 1. BLOQUE DE CONFIGURACIÓN DE TERRAFORM
# ------------------------------------------------------------------------------
terraform {
  required_providers {
    google = {
      # ORIGEN: Se utiliza el proveedor oficial y certificado por HashiCorp para GCP.
      source  = "hashicorp/google"
      
      # CONTROL DE VERSIONES (Version Pinning): "~> 4.0"
      # ¡CRÍTICO PARA PRODUCCIÓN! Obliga a Terraform a usar la versión 4.x.
      # Esto garantiza la "Idempotencia": si este código se ejecuta dentro de 5 años,
      # funcionará exactamente igual. Evita que una actualización futura mayor 
      # (ej. versión 5.0) rompa la sintaxis de nuestra infraestructura.
      version = "~> 4.0"
    }
  }
}

# ------------------------------------------------------------------------------
# 2. DECLARACIÓN DEL PROVEEDOR (GOOGLE CLOUD)
# ------------------------------------------------------------------------------
provider "google" {
  # ID DEL PROYECTO: Identificador único donde se facturarán y alojarán los recursos.
  project = "tfg-asir-2026"
  
  # REGIÓN ESTRATÉGICA Y CUMPLIMIENTO LEGAL (GDPR)
  # "europe-southwest1" corresponde al Centro de Datos de Google en Madrid.
  # Se elige por dos motivos de nivel Enterprise: 
  # 1. Garantizar la mínima latencia y máxima velocidad a los usuarios de España.
  # 2. Cumplir estrictamente con la Ley de Protección de Datos Europea (RGPD), 
  #    asegurando que los datos de los usuarios no salen del territorio nacional.
  region  = "europe-southwest1" 
  
  # ZONA DE DISPONIBILIDAD: Se acota el despliegue a una zona específica ("a") 
  # dentro de la región de Madrid para agrupar físicamente los recursos de cómputo.
  zone    = "europe-southwest1-a"
}