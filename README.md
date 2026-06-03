# TFG ASIR: Infraestructura Cloud Segura y Automatizada en GCP con Terraform

[![Terraform](https://img.shields.io/badge/Terraform-%235C4EE5.svg?style=flat&logo=terraform&logoColor=white)](https://www.terraform.io/)
[![Google Cloud](https://img.shields.io/badge/GoogleCloud-%234285F4.svg?style=flat&logo=google-cloud&logoColor=white)](https://cloud.google.com/)
[![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![MariaDB](https://img.shields.io/badge/MariaDB-003545?style=flat&logo=mariadb&logoColor=white)](https://mariadb.org/)

Este repositorio contiene el código fuente y el plano declarativo perimetral para el despliegue automatizado de una infraestructura multinivel segura en **Google Cloud Platform (GCP)** utilizando **Terraform** como herramienta de Infraestructura como Código (IaC).

El proyecto simula un entorno corporativo real enfocado bajo el modelo de seguridad **AAA (Autenticación, Autorización y Auditoría)** y políticas de acceso basado en roles (**RBAC**).

---

## 🚀 Características Principales

*   **Infraestructura como Código (IaC):** Despliegue perimetral 100% automatizado, modular y versionable con Terraform.
*   **Arquitectura de Red Corporativa Segura:** 
    *   VPC aislada con dos subredes segmentadas (Pública para Frontend y Privada para Backend).
    *   Reglas de firewall estrictas aplicando el principio de menor privilegio (Capa de datos inaccesible desde Internet).
*   **Capa de Presentación (Frontend):** Instancia de Compute Engine ejecutando Apache y PHP con un Portal Web responsivo (Bootstrap 5).
*   **Capa de Persistencia (Backend):** Servidor MariaDB aislado en la subred privada, poblado de forma masiva y automatizada en el arranque con hashes criptográficos (**SHA-256**) para la gestión de identidades.
*   **Control de Accesos Interactivos (RBAC):** Sistema de login inteligente que discrimina vistas:
    *   *Profesores/Alumnos:* Visualizan paneles informativos del proyecto.
    *   *Administrador:* Consola exclusiva de auditoría perimetral con captura de IPs públicas origen en tiempo real.
*   **Plan de Continuidad de Negocio:** Script Bash de copias de seguridad automatizado mediante `cron` con replicación directa en un bucket de **Google Cloud Storage**.

---

## 🗺️ Arquitectura de Red y Flujo AAA

```text
[ INTERNET ] 
      │ (HTTP / Puerto 80)
      ▼
┌────────────────────────── VPC: vpc-asir ──────────────────────────┐
│                                                                   │
│  ┌────────────────────── subred-publica ──────────────────────┐  │
│  │                                                            │  │
│  │  [ Servidor Web Frontend (Debian 11 + Apache + PHP) ]      │  │
│  │  • Gestiona sesiones seguras (RBAC)                        │  │
│  │  • Dirección IP Pública Dinámica Asignada                  │  │
│  └──────────────────────────────┬─────────────────────────────┘  │
│                                 │ (MariaDB / Puerto 3306)         │
│                                 ▼                                 │
│  ┌────────────────────── subred-privada ──────────────────────┐  │
│  │                                                            │  │
│  │  [ Servidor Base de Datos (MariaDB Server) ]                │  │
│  │  • Base de datos relacional (tfg_db)                       │  │
│  │  • Almacena credenciales hasheadas y logs de auditoría     │  │
│  │  • Aislado del exterior (Sin IP pública)                  │  │
│  └──────────────────────────────┬─────────────────────────────┘  │
└─────────────────────────────────┼─────────────────────────────────┘
                                  ▼ (gsutil cp / Backup Diario)
                ┌──────────────────────────────────┐
                │  [ Cloud Storage: bucket-tfg ]   │
                └──────────────────────────────────┘