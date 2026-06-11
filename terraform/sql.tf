resource "google_sql_database_instance" "main" {
  name             = "wolo-db-instance"
  database_version = "POSTGRES_15"
  region           = var.region

  settings {
    tier = "db-f1-micro"
    
    database_flags {
      name  = "cloudsql.enable_pgvector"
      value = "on"
    }
  }

  deletion_protection = false # for dev/testing
  depends_on          = [google_project_service.services["sqladmin.googleapis.com"]]
}

resource "google_sql_database" "catalog_db" {
  name     = "property_catalog"
  instance = google_sql_database_instance.main.name
}

resource "google_sql_user" "db_user" {
  name     = "wolo_user"
  instance = google_sql_database_instance.main.name
  password = var.db_password
}
