# IaaS Web Hosting - Deployment Guide

This guide covers deploying the IaaS Web Hosting stack on various platforms.

## Prerequisites

- Docker (v20.10+)
- Docker Compose (v1.29+)
- Git (optional, for cloning)
- bash/sh shell

## Local Development Deployment

### macOS / Linux

```bash
# Clone repository (or navigate to existing directory)
cd IaaS-WebHosting

# Copy environment file
cp .env.example .env

# Make deploy script executable
chmod +x deploy.sh

# Deploy the stack
./deploy.sh

# Verify all services are running
docker compose ps

# Check logs
docker compose logs -f
```

**Access the application:**
- Home: http://localhost:8080
- Posts: http://localhost:8080/posts.php

### Windows (WSL2)

```bash
# From WSL terminal
cd /mnt/c/Projects/Growing/IaaS-WebHosting

# Copy and edit environment
cp .env.example .env
# Edit .env in your text editor if needed

# Deploy
bash deploy.sh

# Verify services
docker compose ps
```

**Access from Windows:**
- Open browser to http://localhost:8080
- Docker Desktop must be running and integrated with WSL2

## Production-like Deployment

For a more realistic deployment, create a `.env.prod` file:

```bash
NGINX_PORT=80
MYSQL_ROOT_PASSWORD=<strong-random-password>
MYSQL_DATABASE=iaas_prod
MYSQL_USER=appuser
MYSQL_PASSWORD=<strong-random-password>
```

Deploy with:
```bash
docker compose --env-file .env.prod up -d
```

## Database Initialization

On first run, the `init/01-init.sql` script automatically:
1. Creates the database schema (users, posts tables)
2. Inserts sample data
3. Sets up foreign key relationships

To reinitialize (warning: deletes data):
```bash
docker compose down -v  # Remove volumes
docker compose up -d    # Recreate everything
```

## Monitoring & Maintenance

### View Logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f nginx
docker compose logs -f php
docker compose logs -f db
```

### Service Health

```bash
docker compose ps
# STATUS column shows:
# - "Up (healthy)" - service OK
# - "Up (unhealthy)" - service has issues
# - "Up" - no health check configured
```

### Backup Database

```bash
# Dump MySQL database
docker compose exec db mysqldump -u root -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE > backup.sql

# Restore
docker compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE < backup.sql
```

### Access MySQL CLI

```bash
docker compose exec db mysql -u root -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE
```

### Access PHP Container

```bash
docker compose exec php bash
# Inside container:
php -v
ls -la /var/www/html
```

## Scaling

### Add More PHP Workers

```bash
docker compose up -d --scale php=3
```

This creates 3 PHP-FPM instances behind the single nginx load balancer.

### Monitor Resource Usage

```bash
docker stats
```

## Cleanup

### Stop All Services

```bash
docker compose down
```

### Remove All Data (including volumes)

```bash
docker compose down -v
```

### Remove Images

```bash
docker compose down --rmi all
```

## Common Issues

### Port 8080 Already in Use

```bash
# Edit .env
NGINX_PORT=8081

# Restart
docker compose down
docker compose up -d
```

### MySQL Won't Start

```bash
# Check logs
docker compose logs db

# Verify credentials in .env
# Restart
docker compose down -v
docker compose up -d
```

### PHP Errors

```bash
# Check PHP logs
docker compose logs php

# Check if MySQL is accessible
docker compose exec php php -r "new PDO('mysql:host=db', 'root', 'changeme');"
```

### Browser Shows "Connection Refused"

```bash
# Ensure containers are running
docker compose ps

# Check if Docker daemon is running
docker ps

# Verify port binding
docker compose port nginx 80
```

## Integration with CI/CD

### GitHub Actions Example

```yaml
name: Test IaaS Web Hosting

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      db:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: test
          MYSQL_DATABASE: iaas_demo
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    steps:
      - uses: actions/checkout@v2
      - uses: docker/setup-buildx-action@v1
      - run: docker compose build
      - run: docker compose up -d
      - run: docker compose exec -T php php -l site/index.php
```

## Next Steps

1. Customize `./site/` with your own PHP application
2. Modify `./init/01-init.sql` for your database schema
3. Update `.env` for production credentials
4. Use this as a base for Kubernetes migration
5. Add SSL/TLS with nginx reverse proxy
6. Integrate monitoring (Prometheus, Grafana)
