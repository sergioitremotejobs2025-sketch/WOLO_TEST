resource "google_api_gateway_api" "api" {
  provider = google-beta
  api_id   = "wolo-api"
  depends_on = [google_project_service.services["apigateway.googleapis.com"]]
}

resource "google_api_gateway_api_config" "api_cfg" {
  provider     = google-beta
  api          = google_api_gateway_api.api.api_id
  api_config_id = "wolo-api-cfg"

  openapi_documents {
    document {
      path     = "openapi.yaml"
      contents = base64encode(<<-EOF
swagger: "2.0"
info:
  title: "WOLO API"
  version: "1.0.0"
x-google-endpoints:
- name: "wolo-api"
  allowCors: true
paths:
  /api/chat:
    post:
      summary: "Chat Endpoint"
      operationId: "chat"
      x-google-backend:
        address: "${google_cloud_run_v2_service.chat_orchestrator.uri}/api/chat"
      responses:
        '200':
          description: "A successful response"
EOF
      )
    }
  }
}

resource "google_api_gateway_gateway" "gw" {
  provider   = google-beta
  api_config = google_api_gateway_api_config.api_cfg.id
  gateway_id = "wolo-gw"
  region     = var.region
}
