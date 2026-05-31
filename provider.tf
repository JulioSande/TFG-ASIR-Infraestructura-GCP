terraform {
  required_providers {
    google = {
      source  = "hashicorp/google"
      version = "~> 4.0"
    }
  }
}

provider "google" {
  project = "tfg-asir-2026"
  region  = "europe-southwest1" # Región de Madrid
  zone    = "europe-southwest1-a"
}