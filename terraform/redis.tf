resource "google_redis_instance" "cache" {
  name           = "wolo-redis-cache"
  tier           = "BASIC"
  memory_size_gb = 1
  region         = var.region

  redis_version = "REDIS_6_X"

  depends_on = [google_project_service.services["redis.googleapis.com"]]
}
