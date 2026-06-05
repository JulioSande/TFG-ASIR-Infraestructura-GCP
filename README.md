# ☁️ Proyecto ASIR: Infraestructura Cloud Segura y Automatizada en GCP con Terraform

![Terraform](https://img.shields.io/badge/Terraform-7B42BC?style=for-the-badge&logo=terraform&logoColor=white)
![Google Cloud](https://img.shields.io/badge/Google_Cloud-4285F4?style=for-the-badge&logo=google-cloud&logoColor=white)
![Debian](https://img.shields.io/badge/Debian-A81D33?style=for-the-badge&logo=debian&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-003545?style=for-the-badge&logo=mariadb&logoColor=white)

Este repositorio contiene el código fuente y el plano declarativo perimetral para el despliegue automatizado de una infraestructura multinivel segura en Google Cloud Platform (GCP) utilizando Terraform como herramienta de Infraestructura como Código (IaC).

El proyecto simula un entorno corporativo real enfocado bajo el modelo de seguridad AAA (Autenticación, Autorización y Auditoría) y políticas de acceso basado en roles (RBAC), aplicando metodologías DevOps y optimización de costes (FinOps).

---

## 🚀 Características Principales

* **Infraestructura como Código (IaC):** Despliegue perimetral 100% automatizado, modular, inmutable y versionable con Terraform.
* **Arquitectura de Red Corporativa (Zero Trust):** VPC personalizada aislada con dos subredes segmentadas (Pública para Frontend y Privada para Backend). Capa de datos completamente inaccesible desde Internet.
* **Seguridad de Salida Unidireccional (Cloud NAT):** Implementación de una pasarela Cloud NAT asociada a un Cloud Router. Permite al servidor de base de datos privado descargar parches de seguridad desde Internet sin exponer una dirección IP pública.
* **Capa de Presentación (Frontend):** Instancia de Compute Engine ejecutando Apache y PHP con un Portal Web responsivo securizado mediante Bootstrap 5 e Inter Fonts.
* **Capa de Persistencia (Backend):** Servidor MariaDB aislado en la subred privada, poblado de forma masiva e idempotente en el arranque utilizando hashes criptográficos (**SHA-256**) para la gestión de identidades.
* **Control de Accesos Interactivos (RBAC):** Sistema de login inteligente con mitigación de inyecciones SQL que discrimina las vistas según privilegios:
  * **Profesores/Alumnos:** Visualizan paneles informativos interactivos del proyecto.
  * **Administrador:** Acceso exclusivo a una consola de auditoría perimetral que captura las IPs públicas origen en tiempo real.
* **Plan de Continuidad de Negocio (Disaster Recovery):** Script Bash de copias de seguridad lógicas automatizado mediante el demonio `cron`, con replicación y desacoplamiento directo en un bucket de Google Cloud Storage.

---

## 🗺️ Arquitectura de Red y Flujo AAA

```text
       [ INTERNET ] 
            │ (HTTP/S - Puertos 80/443)
            ▼
┌───────────────────────────────── VPC: vpc-proyecto-asir ─────────────────────────────────┐
│                                                                                          │
│  ┌─────────────────────────── subred-publica (10.0.1.0/24) ───────────────────────────┐  │
│  │                                                                                    │  │
│  │  [ Servidor Web Frontend (Debian 11 + Apache + PHP) ]                              │  │
│  │  • Gestiona sesiones seguras e inyecciones SQL (RBAC)                              │  │
│  │  • Dirección IP Pública Efímera Asignada                                           │  │
│  └───────────────────────────────────┬────────────────────────────────────────────────┘  │
│                                      │ (MariaDB / Puerto 3306 Interno)                   │
│                                      ▼                                                   │
│  ┌─────────────────────────── subred-privada (10.0.2.0/24) ───────────────────────────┐  │
│  │                                                                                    │  │
│  │  [ Servidor Base de Datos (MariaDB Server) ] ────► [ Cloud NAT ] ──► [ Internet ]  │  │
│  │  • Almacena credenciales SHA-256 e IP logs          (Solo salida para updates)     │  │
│  │  • Aislado del exterior (Sin IP pública)                                           │  │
│  └───────────────────────────────────┬────────────────────────────────────────────────┘  │
└──────────────────────────────────────┼───────────────────────────────────────────────────┘
                                       ▼ (gsutil cp / Backup Diario Cron 03:00 AM)
                     ┌──────────────────────────────────────────────────┐
                     │  [ Cloud Storage: bucket-tfg-juliosande-backups ]│
                     └──────────────────────────────────────────────────┘
```

---

## 🛠️ Requisitos Previos

Antes de desplegar la infraestructura, asegúrate de contar con los siguientes elementos instalados y configurados en tu entorno local:
* **Terraform** (Versión ~> 4.0 del proveedor de Google).
* **Google Cloud SDK** (gcloud CLI) instalado y autenticado en tu máquina local.

---

## 💻 Comandos de Despliegue

Sigue este orden cronológico en la terminal de tu sistema para interactuar con la infraestructura:

### 1. Autenticación en la plataforma Cloud
Generar los tokens de acceso locales de forma segura sin necesidad de gestionar archivos JSON de claves privadas:
```bash
gcloud auth application-default login
```

### 2. Inicializar el proyecto IaC
Descarga los plugins del proveedor oficial de Google Cloud e inicializa el entorno de trabajo de Terraform:
```bash
terraform init
```

### 3. Validar el código fuente
Verifica de forma estática que la sintaxis de todos los archivos `.tf` sea correcta:
```bash
terraform validate
```

### 4. Planificar el entorno (Simulación)
Muestra un extracto detallado de los recursos que se van a crear en la nube antes de realizar cualquier cambio real:
```bash
terraform plan
```

### 5. Aplicar el despliegue automático
Aprovisiona toda la arquitectura de red, seguridad perimetral y servidores en Google Cloud:
```bash
terraform apply
```

### 6. Destrucción del entorno (Cultura FinOps)
Para cumplir con las buenas prácticas de gestión financiera en la nube y evitar el desperdicio de créditos, destruya toda la infraestructura al finalizar las pruebas:
```bash
terraform destroy
```

---

## 📂 Estructura del Repositorio

* **`provider.tf`:** Configuración del motor de Terraform y bloqueo de versiones del proveedor de Google Cloud. Define la región estratégica de Madrid (`europe-southwest1`) para cumplir con el RGPD.
* **`network.tf`:** Configuración en modo *Custom* de la red VPC y declaración de las subredes pública y privada segmentadas.
* **`firewall.tf`:** Reglas perimetrales del cortafuegos nativo de GCP bajo políticas *Zero Trust* (Acceso web abierto, SSH administrativo y puerto 3306 blindado para la red interna).
* **`nat.tf`:** Aprovisionamiento de Cloud Router y Cloud NAT para dotar de salida a Internet unidireccional a la subred privada.
* **`compute.tf`:** Creación de las instancias de Compute Engine (Frontend y Backend), el bucket de Cloud Storage y los scripts automatizados de arranque (poblado criptográfico de MariaDB, tablas de auditoría activa y tareas programadas en `cron`).
* **`web_TFG.php`:** Código fuente de la aplicación web en PHP. Contiene los módulos de control de accesos por roles, prevención contra ataques de inyección SQL mediante saneamiento de cadenas y el panel de auditoría para el administrador.

---

## 👨‍💻 Autor

* **Alumno:** Julio Sande Noriega
* **Centro:** C.P.R. Liceo "La Paz"
* **Especialidad:** Administración de Sistemas Informáticos en Red (ASIR)
* **Año del Despliegue:** 2026