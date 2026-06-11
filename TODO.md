# WOLO Project Implementation Checklist (TODO)

This document tracks the tasks required to build the WOLO Real Estate Chatbot using a Test-Driven Development (TDD) approach, Symfony, microservices, GCP, and Vertex AI.

> [!TIP]
> **How to use this checklist:**
> * To mark a task as **completed**, change the checkbox from `[ ]` to `[x]` (e.g., `- [x] Task`).
> * To mark a task as **in progress**, change the checkbox to `[/]` (e.g., `- [/] Task`).
> * In interactive markdown viewers/editors, you can click directly on the checkboxes to toggle them.

---

## Phase 1: Project Setup & Test Infrastructure

- [x] **1.1. Repository & Directory Structure Setup**
  - [x] 1.1.1. Initialize Git monorepo or individual repositories for the services.
  - [x] 1.1.2. Configure Docker Compose for local development (PostgreSQL with `pgvector`, Redis, and LocalStack/PubSub emulator).

- [x] **1.2. Property Catalog Service Setup (Symfony)**
  - [x] 1.2.1. Initialize Symfony project (`symfony new property-catalog --webapp`).
  - [x] 1.2.2. Install testing dependencies: `phpunit/phpunit`, `symfony/browser-kit`, `symfony/css-selector`.
  - [x] 1.2.3. Configure `phpunit.xml.dist` and set up an in-memory SQLite database environment for testing.
  - [x] 1.2.4. Write a base test case to verify that the test environment and database migrations are functional.

- [x] **1.3. Chat Orchestrator Service Setup (Symfony)**
  - [x] 1.3.1. Initialize Symfony project (`symfony new chat-orchestrator`).
  - [x] 1.3.2. Configure testing environment with mocked HTTP client (`MockHttpClient`) to intercept future LLM calls.
  - [x] 1.3.3. Establish initial unit test for verifying session token creation and verification.

- [x] **1.4. Lead & Notification Service Setup (Symfony)**
  - [x] 1.4.1. Initialize Symfony project (`symfony new lead-service`).
  - [x] 1.4.2. Set up testing framework with a mock client for GCP Firestore and Pub/Sub.

---

## Phase 2: Property Catalog Service (TDD Implementation)

- [x] **2.1. TDD: Property Entity & Database Schema**
  - [x] 2.1.1. **Test**: Write a unit test asserting that a `Property` entity can be instantiated with required fields (title, price, type, bedrooms, location, embeddings) and validation errors are triggered for invalid values.
  - [x] 2.1.2. **Code**: Implement `Property` entity and configure Symfony validator rules. Verify tests pass.
  - [x] 2.1.3. **Refactor**: Clean up constraints, optimize getter/setter signatures.

- [x] **2.2. TDD: Property Database Repository & Seeders**
  - [x] 2.2.1. **Test**: Write an integration test asserting that the custom `PropertyRepository` can find properties using various filters (location, price range, listing type).
  - [x] 2.2.2. **Code**: Implement repository queries using Doctrine Query Builder. Run tests and ensure success.
  - [x] 2.2.3. **Refactor**: Abstract complex search filter logic into separate filter classes.

- [x] **2.3. TDD: Property Semantic Search (pgvector)**
  - [x] 2.3.1. **Test**: Write an integration test asserting that searching for properties with a simulated text embedding returns the properties closest in cosine distance.
  - [x] 2.3.2. **Code**: Implement custom SQL function in Doctrine or raw SQL query to compute vector distance using the `pgvector` operators (`<=>`).
  - [x] 2.3.3. **Refactor**: Optimize vector dimension handling and search index types.

- [x] **2.4. TDD: REST API Controller Endpoints**
  - [x] 2.4.1. **Test**: Write a functional test (`WebTestCase`) asserting that `GET /api/properties` returns a `200 OK` code and a JSON response with schema validation matching the API design.
  - [x] 2.4.2. **Code**: Create `PropertyController` and map search filters from the query parameters to the repository search service.
  - [x] 2.4.3. **Refactor**: Standardize error handling and JSON formatter utilities.

---

## Phase 3: Chat Orchestrator Service (TDD Implementation)

- [x] **3.1. TDD: Chat Session Manager (Redis integration)**
  - [x] 3.1.1. **Test**: Write an integration test asserting that user/bot messages can be appended to a session ID and retrieved in the correct chronological array order.
  - [x] 3.1.2. **Code**: Implement `ChatSessionManager` using `predis/predis` or Symfony Cache component.
  - [x] 3.1.3. **Refactor**: Extract message formatting logic.

- [x] **3.2. TDD: Vertex AI HTTP Client wrapper**
  - [x] 3.2.1. **Test**: Write a test asserting that a valid JSON payload matching Gemini 1.5 Flash requirements is constructed given a chat history array.
  - [x] 3.2.2. **Code**: Implement the HTTP call using Symfony `HttpClient` against the GCP REST endpoint using a test service account key.
  - [x] 3.2.3. **Refactor**: Move API keys and Model version strings to environment variables.

- [x] **3.3. TDD: LLM Function calling (tool executions)**
  - [x] 3.3.1. **Test**: Write a test asserting that if Vertex AI responds with a `functionCall` to `search_properties`, the orchestrator dispatches a REST call to the `property-catalog` mock and appends the response to the LLM.
  - [x] 3.3.2. **Code**: Implement the `ToolDispatcher` service.
  - [x] 3.3.3. **Refactor**: Create a registry for multiple tools if needed later.

- [x] **3.4. TDD: Main conversation endpoint**
  - [x] 3.4.1. **Test**: Write a functional test asserting `POST /api/chat` with `{ "message": "Hi" }` returns `{ "response": "..." }`.
  - [x] 3.4.2. **Code**: Implement the ChatController tying all 3 services together.
  - [x] 3.4.3. **Refactor**: Extract the main orchestration loop into a domain service and handle rate-limiting.

---

## Phase 4: Lead Service (TDD Implementation)

- [x] **4.1. TDD: Lead Domain Model & Validation**
  - [x] 4.1.1. **Test**: Write a unit test ensuring that missing or invalid Lead properties (e.g. invalid email format) raise validation violations.
  - [x] 4.1.2. **Code**: Implement `Lead` object using Symfony constraints.
  - [x] 4.1.3. **Refactor**: Simplify to a generic DTO struct if Doctrine mapping is not needed here.

- [x] **4.2. TDD: GCP Pub/Sub Listener**
  - [x] 4.2.1. **Test**: Write an integration test where a mocked Pub/Sub message triggers the `LeadSubmittedHandler`.
  - [x] 4.2.2. **Code**: Implement `LeadSubmittedHandler` using Symfony Messenger bound to `google-cloud-messenger`.
  - [x] 4.2.3. **Refactor**: Separate message consumption and business logic execution.

- [x] **4.3. TDD: Notification Dispatcher**
  - [x] 4.3.1. **Test**: Write a unit test verifying `NotificationDispatcher` properly formats and hands over an `Email` object to `MailerInterface`.
  - [x] 4.3.2. **Code**: Implement the Notification Dispatcher wrapping Symfony Mailer.
  - [x] 4.3.3. **Refactor**: Move agent notification email templates out of the logic class into Twig or environment variables.

---

## Phase 5: Infrastructure & Deployment Automation

- [x] **5.1. Terraform Infrastructure Code**
  - [x] 5.1.1. Write Terraform configurations to deploy GCP Cloud Run instances for the 3 services.
  - [x] 5.1.2. Write Terraform configurations to deploy GCP API Gateway.
  - [x] 5.1.3. Write Terraform configurations to deploy Cloud SQL (PostgreSQL with `pgvector` enabled).
  - [x] 5.1.4. Write Terraform configurations to deploy Cloud Firestore & Memorystore (Redis).
  - [x] 5.1.5. Write Terraform configurations to deploy Cloud Pub/Sub topics and subscriptions.
  - [x] 5.1.6. Verify configuration using `terraform plan`.

- [x] **5.2. CI/CD Pipeline Configuration**
  - [x] 5.2.1. Setup PHP coding standards checkers (`PHPStan`, `PHP-CS-Fixer`) in the pipeline.
  - [x] 5.2.2. Setup automatic unit, integration, and contract test executions in the pipeline.
  - [x] 5.2.3. Setup Docker image builds and deployment configurations for Cloud Run.

---

## Phase 6: End-to-End Validation & Release

- [x] **6.1. E2E System Tests**
  - [x] 6.1.1. Conduct E2E testing scenarios simulating real conversation threads (e.g., searching, selecting, submitting contact details).
  - [x] 6.1.2. Measure latency of Vertex AI API calls and optimize cache/session payloads.

- [x] **6.2. Security and Hardening**
  - [x] 6.2.1. Validate CORS configurations and setup rate limits on the API Gateway.
  - [x] 6.2.2. Execute security audits on Symfony packages using `composer audit`.

- [x] **6.3. Launch**
  - [x] 6.3.1. Perform production deployment and run final smoke tests.

---

## Phase 7: Visual Chat Interface (Web UI)

- [x] **7.1. Frontend Design & UX (Tailwind/CSS)**
  - [x] 7.1.1. Design a clean, premium, and responsive chat UI component (matching modern aesthetics, dark mode support).
  - [x] 7.1.2. Setup CSS tokens, custom avatars, and message bubbles with loading placeholders.

- [x] **7.2. Web Chat Controller & View (Symfony/Twig)**
  - [x] 7.2.1. Implement a web route (`GET /chat`) in the Chat Orchestrator service to render the Twig template.
  - [x] 7.2.2. Create standard Symfony Form or JS fetch controller to send inputs to `/api/chat` and update the message list.
  - [x] 7.2.3. Integrate markdown parsing for formatting chatbot responses (lists, bolding, links).

- [x] **7.3. Interactive Property Cards**
  - [x] 7.3.1. Design interactive visual cards for displaying property catalog items inside the chat transcript.
  - [x] 7.3.2. Implement "Register Interest" CTA buttons on cards, calling the Lead & Notification service API.

- [x] **7.4. UI Testing & Release**
  - [x] 7.4.1. Write E2E browser tests (Symfony Panthère / Cypress) asserting message dispatching and UI updates.
  - [x] 7.4.2. Redeploy Chat Orchestrator with the frontend enabled and run visual verification.

---

## Phase 8: Browse Properties Interface

- [x] **8.1. TDD: Property Browse Page Test (Symfony/Twig)**
  - [x] 8.1.1. Create a `PropertyWebControllerTest` WebTestCase asserting that `GET /properties` returns status `200 OK` and contains key elements like filters (city, type, price_max) and property grid.
  - [x] 8.1.2. Assert that dynamic filters correctly query the underlying database or mock results.

- [x] **8.2. Properties Browser Controller & View**
  - [x] 8.2.1. Implement `browse()` in `PropertyController` to handle `GET /properties` and render a Twig template.
  - [x] 8.2.2. Design a premium, dark-mode glassmorphic Twig template (`browse.html.twig`) with custom Google Fonts (Outfit).
  - [x] 8.2.3. Implement the property grid rendering, displaying title, price, location, type, and bedrooms.

- [x] **8.3. Dynamic Frontend Filtering & Interactivity**
  - [x] 8.3.1. Add vanilla JavaScript logic to intercept filter changes (city, type, max price) and perform asynchronous fetch calls to `/api/properties`.
  - [x] 8.3.2. Implement transitions and hover animations on the property cards to create a fluid, responsive interface.
  - [x] 8.3.3. Add a detail view popup modal for property cards to show expanded details and CTA to start a chat with the WOLO assistant.

- [x] **8.4. Testing & Cloud Run Deployment**
  - [x] 8.4.1. Run all unit and WebTestCase suites to verify green status in the local environment.
  - [x] 8.4.2. Redeploy the updated `property-catalog` service to GCP Cloud Run and perform manual E2E validation.

---

## Phase 9: Add New Properties

- [x] **9.1. TDD: Property Creation API Test (Symfony/PHPUnit)**
  - [x] 9.1.1. Create a `PropertyCreateTest` WebTestCase asserting that `POST /api/properties` with valid data saves the property and returns a `201 Created` status with the serialized property details.
  - [x] 9.1.2. Assert that `POST /api/properties` with invalid data returns `400 Bad Request` and detailed validation error messages.

- [x] **9.2. Properties Creation API Endpoint**
  - [x] 9.2.1. Implement the `create()` method in `PropertyController` to handle `POST /api/properties`.
  - [x] 9.2.2. Validate input constraints using Symfony's `ValidatorInterface` and persist properties using the entity manager.
  - [x] 9.2.3. Enable CORS support if cross-origin requests are expected.

- [x] **9.3. Properties Browser Creation Form UI**
  - [x] 9.3.1. Modify `browse.html.twig` to add a "+ Add Property" button in the header.
  - [x] 9.3.2. Implement a glassmorphic modal form for adding properties with validation fields.
  - [x] 9.3.3. Add JS logic to post data to `/api/properties` using `fetch` and update the property list dynamically.

- [x] **9.4. Testing & Cloud Run Deployment**
  - [x] 9.4.1. Run all unit and WebTestCase suites to verify green status in the local environment.
  - [x] 9.4.2. Redeploy the updated `property-catalog` service to GCP Cloud Run and perform manual E2E validation.




