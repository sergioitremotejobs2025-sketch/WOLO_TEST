resource "google_pubsub_topic" "lead_submissions" {
  name = "lead-submissions"
  depends_on = [google_project_service.services["pubsub.googleapis.com"]]
}

resource "google_pubsub_subscription" "lead_worker" {
  name  = "lead-service-sub"
  topic = google_pubsub_topic.lead_submissions.name
}
