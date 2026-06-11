variable "project_id" {
  type        = string
  description = "GCP Project ID"
  default     = "wolo-real-estate-bot"
}

variable "region" {
  type        = string
  description = "GCP Region"
  default     = "us-central1"
}

variable "db_password" {
  type        = string
  description = "Cloud SQL postgres user password"
  sensitive   = true
}
