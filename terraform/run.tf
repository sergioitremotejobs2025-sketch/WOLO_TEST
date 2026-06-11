resource "google_cloud_run_v2_service" "property_catalog" {
  name     = "property-catalog-svc"
  location = var.region
  ingress  = "INGRESS_TRAFFIC_ALL"

  template {
    containers {
      image = "us-central1-docker.pkg.dev/${var.project_id}/wolo/property-catalog:latest"
      
      env {
        name  = "DATABASE_URL"
        value = "postgresql://wolo_user:${var.db_password}@${google_sql_database_instance.main.public_ip_address}:5432/property_catalog"
      }
    }
  }

  depends_on = [google_project_service.services["run.googleapis.com"]]
}

resource "google_cloud_run_v2_service" "chat_orchestrator" {
  name     = "chat-orchestrator-svc"
  location = var.region
  ingress  = "INGRESS_TRAFFIC_ALL"

  template {
    containers {
      image = "us-central1-docker.pkg.dev/${var.project_id}/wolo/chat-orchestrator:latest"
      
      env {
        name  = "REDIS_URL"
        value = "redis://${google_redis_instance.cache.host}:${google_redis_instance.cache.port}"
      }
      env {
        name  = "PROPERTY_CATALOG_URL"
        value = google_cloud_run_v2_service.property_catalog.uri
      }
      env {
        name  = "GCP_PROJECT_ID"
        value = var.project_id
      }
      env {
        name  = "GCP_REGION"
        value = var.region
      }
    }
  }

  depends_on = [google_project_service.services["run.googleapis.com"]]
}

resource "google_cloud_run_v2_service" "lead_service" {
  name     = "lead-service-svc"
  location = var.region
  ingress  = "INGRESS_TRAFFIC_INTERNAL_ONLY"

  template {
    containers {
      image = "us-central1-docker.pkg.dev/${var.project_id}/wolo/lead-service:latest"
      
      env {
        name  = "MESSENGER_TRANSPORT_DSN"
        value = "gcp-pubsub://default"
      }
    }
  }

  depends_on = [google_project_service.services["run.googleapis.com"]]
}

resource "google_cloud_run_service_iam_member" "chat_public" {
  location = google_cloud_run_v2_service.chat_orchestrator.location
  project  = google_cloud_run_v2_service.chat_orchestrator.project
  service  = google_cloud_run_v2_service.chat_orchestrator.name
  role     = "roles/run.invoker"
  member   = "allUsers"
}
