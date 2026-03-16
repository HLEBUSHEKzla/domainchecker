Domain Checker & Monitoring Service

A comprehensive web application designed to monitor the health, performance, and SEO status of multiple domains. Built with a robust Laravel backend and a dynamic Blade frontend, this tool provides real-time insights and historical data for effective domain management.

Features

- User Authentication
  Secure registration and login system.

- Multi-Domain Management
  Add, edit, and delete domains to monitor.

- Comprehensive Health Checks
  Performs a full suite of checks for each domain:

  - DNS Check
    Verifies that the domain's A records are correctly resolved.

  - SSL Certificate Check
    Validates the SSL certificate, checks its expiration date, and identifies the issuer.

  - HTTP Status Check
    Monitors the server response status code (2xx, 4xx, 5xx).

  - Redirect Chain Analysis
    Traces the full redirect path and detects redirect loops.

  - Content and SEO Analysis
    - Extracts Title, H1, and meta description tags.
    - Checks for the presence of a specific content marker on the page.
    - Detects parked or suspended pages.

  - Search Engine Visibility
    Checks domain reputation against Google Safe Browsing lists.

- Dynamic Dashboard
  Provides an overview of the domain portfolio, including:
  - Key statistics (total, healthy, unhealthy domains)
  - Slowest responding domains

- Detailed History
  View a paginated history of all checks for a specific domain, including detailed error reporting.

- Interactive UI
  - Asynchronous data loading via AJAX for a smooth user experience
  - Auto-refreshing domain list for near real-time status updates
  - Collapsible sections for detailed check metadata without cluttering the interface

- Background Processing
  All checks are performed asynchronously using Laravel Queues, ensuring the UI remains fast and responsive.

Technology Stack

Backend

- Framework: Laravel 12
- PHP: 8.3
- Database: MySQL 8.0
- Queue and Cache: Redis

Architecture

- SOLID principles applied throughout the application
- Strategy Pattern used for monitoring checkers, allowing easy extension
- Chain of Responsibility Pattern used for calculating final domain status based on check results
- API Resources used for standardized and flexible API responses

Frontend

- Templating: Laravel Blade
- Styling: Bootstrap 4 (via CDN)
- JavaScript: jQuery for AJAX calls and DOM manipulation
- Icons: FontAwesome

DevOps

- Containerization: Docker and Docker Compose
- Web Server: Nginx
- Process Manager: Supervisor for managing queue workers

Requirements

- Docker
- Docker Compose
- Web browser

Getting Started

1. Clone the Repository

git clone <your-repository-url>
cd domainchecker

2. Environment Configuration

The project uses Docker for a consistent development environment. All services are defined in the docker-compose.yml file.

3. Build and Run the Docker Containers

From the project root directory, run the following command. This will build the necessary images and start all services, including Nginx, PHP-FPM, MySQL, Redis, Worker, and Scheduler.

docker-compose up -d --build

4. Install Dependencies and Run Migrations

Once the containers are running, install the backend dependencies and set up the database.

# Enter the app container
docker-compose exec app bash

# Inside the container, run:
composer install
php artisan migrate

# Exit the container
exit

5. Access the Application

The application will be available at:

- Web Interface: http://localhost:8098
- API Endpoints: http://localhost:8098/api/...

You can register a new user and start adding domains to monitor.

Project Documentation

Backend Architecture

The backend is designed for scalability and maintainability.

- Controllers
  Located in app/Http/Controllers.

  - API Controllers
    Example: DomainController.php
    Handle API requests and return JSON responses using API Resources.

  - Web Controllers
    Example: app/Http/Controllers/Web/DashboardController.php
    Handle web requests and return Blade views.

- Business Logic
  Encapsulated in Action classes (app/Actions) and Service classes (app/Services).

  - RunDomainCheckAction
    Initiates the monitoring process.

  - CreateCheckHistoryAction
    Saves the results of a check.

- Monitoring Pipeline

  - Checkers
    Located in app/Services/Monitoring/Checkers.
    Each class is responsible for a single type of check, such as DNS or SSL, and implements the CheckerInterface.

  - Status Determiners
    Located in app/Services/Monitoring/StatusDeterminers.
    A chain of classes that determines the final domain status based on a priority of rules.

- Asynchronous Tasks

  - Jobs
    Example: app/Jobs/ProcessDomainCheckJob.php
    A queueable job that orchestrates a single domain check. This is the entry point for background processing.

  - Scheduler
    A dedicated container running php artisan schedule:run every minute to dispatch new jobs for domains that are due for a check.

  - Worker
    A dedicated container running Supervisor to process jobs from the Redis queue.

Frontend Architecture

The frontend is built using Laravel Blade and enhanced with AJAX for a dynamic user experience.

- Layouts
  The main application layout is located in resources/views/layouts/app.blade.php.
  It includes the main navigation and loads the required CSS and JavaScript from CDNs.

- Views
  Each page, such as Dashboard or Domains List, has its own Blade file, for example dashboard.blade.php.
  These files define the HTML structure.

- Dynamic Data Flow
  - Pages initially load as simple HTML shells
  - The @section('scripts') in each Blade file contains jQuery logic
  - On document ready, the script sends an AJAX request to the corresponding API endpoint, for example /api/dashboard
  - The returned JSON data is then used to dynamically build and populate tables, cards, and other UI elements

- Authentication
  - Login and registration pages are implemented as simple Blade views
  - They send credentials to /api/login and /api/register
  - Upon successful authentication, an API token is returned and stored in the browser's localStorage
  - This token is automatically attached as a Bearer token to all subsequent AJAX requests

API Endpoints

All API endpoints are defined in routes/api.php and are prefixed with /api.

- POST   /api/register
  Create a new user.

- POST   /api/login
  Authenticate a user and receive an API token.

- GET    /api/dashboard
  Get aggregated statistics for the authenticated user's domains.

- GET    /api/domains
  Get a paginated list of the user's domains.

- POST   /api/domains
  Create a new domain.

- GET    /api/domains/{id}
  Get details for a single domain.

- PUT    /api/domains/{id}
  Update a domain.

- DELETE /api/domains/{id}
  Delete a domain.

- GET    /api/domains/{id}/history
  Get a paginated history of checks for a domain.

- POST   /api/logout
  Invalidate the current API token.
