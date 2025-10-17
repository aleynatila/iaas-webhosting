# Docker Networking in IaaS Web Hosting

## Network Architecture

```
┌─────────────────────────────────────┐
│    Docker Network                   │
│  (iaas-webhosting_default)          │
│                                     │
│  ┌──────────────┐                  │
│  │   nginx      │ :8080 -> host    │
│  │ (web layer)  │                  │
│  │ 172.20.0.2   │                  │
│  └──────┬───────┘                  │
│         │                           │
│         │ FastCGI /var/run/php.sock
│         │                           │
│  ┌──────▼───────┐                  │
│  │   php-fpm    │                  │
│  │ (app layer)  │                  │
│  │ 172.20.0.3   │                  │
│  └──────┬───────┘                  │
│         │                           │
│         │ TCP/3306                  │
│         │                           │
│  ┌──────▼───────┐                  │
│  │   mysql      │                  │
│  │ (db layer)   │                  │
│  │ 172.20.0.4   │                  │
│  └──────────────┘                  │
│                                     │
└─────────────────────────────────────┘
```

## Service Discovery

Docker Compose automatically enables DNS-based service discovery:

```yaml
# From php-fpm container, connect to database:
$pdo = new PDO("mysql:host=db;dbname=iaas_demo", "user", "pass");

# "db" resolves to the MySQL container's IP (e.g., 172.20.0.4)
# This is automatic - no manual DNS configuration needed
```

### Service Names (as defined in docker-compose.yml)

| Service | DNS Name | Port | Notes |
|---------|----------|------|-------|
| mysql   | db       | 3306 | Internal only |
| php     | php      | 9000 | Internal only (FastCGI) |
| nginx   | nginx    | 80   | External: localhost:8080 |

## Port Mapping

```
Host Machine      Docker Network    Container
─────────────────────────────────────────────

localhost:8080 ───> nginx:80 ──────> 172.20.0.2:80
localhost:3306 ───> mysql:3306 ────> 172.20.0.4:3306

Internal only:
php:9000 (FastCGI from nginx)
```

## Volume Mounts and Network Effects

### Persistent Volumes

```yaml
volumes:
  db_data:        # Named volume for MySQL data
  nginx_logs:     # Named volume for nginx logs
  ./site:         # Bind mount for PHP code (shared with host)
```

### Network Communication via Mounts

- **Application Code**: Shared via bind mount `./site` (host filesystem accessible to both nginx and php)
- **Socket Communication**: FastCGI uses Unix socket (localhost) or TCP if configured
- **Data Persistence**: Docker volumes are shared via Docker daemon (independent of network)

## Testing Network Connectivity

### From Host Machine

```bash
# Test nginx is accessible
curl http://localhost:8080

# Test MySQL from host (if exposed)
mysql -h 127.0.0.1 -P 3306 -u root -pchangeme

# Check which services are listening
docker compose ps
```

### From Within Containers

```bash
# Exec into nginx container
docker compose exec nginx bash

# Inside nginx container, test PHP connectivity
curl http://php:9000/

# From PHP container, test MySQL
docker compose exec php php -r "var_dump(gethostbyname('db'));"

# Ping other services
docker compose exec php ping db
docker compose exec php ping nginx
```

## Network Isolation

By default, only the `nginx` container exposes a port to the host. MySQL and PHP-FPM are **not directly accessible** from outside the Docker network:

```bash
# ✅ Works: Access via nginx
curl http://localhost:8080

# ❌ Fails: Direct access to php-fpm
curl http://localhost:9000/

# ✅ Works: Within containers, service names resolve
docker compose exec nginx curl http://php:9000
```

### Expose Additional Ports

To access MySQL from the host:

```yaml
# In docker-compose.yml
db:
  ports:
    - "3306:3306"  # Expose MySQL to host
```

Then:
```bash
mysql -h 127.0.0.1 -u root -pchangeme iaas_demo
```

## Networking Limitations & Solutions

### Problem: Containers Can't Reach Each Other

**Cause**: Services not defined in same docker-compose.yml or different networks

**Solution**: Ensure all services in one compose file or use user-defined networks:

```yaml
networks:
  custom-net:
    driver: bridge

services:
  nginx:
    networks:
      - custom-net
  php:
    networks:
      - custom-net
  db:
    networks:
      - custom-net
```

### Problem: DNS Resolution Fails

**Cause**: Network driver misconfiguration

**Solution**: Use default bridge network (created by docker-compose automatically) or explicit named networks.

### Problem: Performance Degradation with Unix Sockets

**Consideration**: FastCGI over TCP is slightly slower than Unix sockets but is more portable.

**Current Setup**: Uses TCP (configurable in nginx/default.conf)

## Advanced Configurations

### Multi-Host Networking (Overlay)

For deployment across multiple Docker hosts:

```yaml
networks:
  overlay-net:
    driver: overlay
```

Requires Docker Swarm or Kubernetes.

### Host Networking

```yaml
services:
  nginx:
    network_mode: "host"
```

⚠️ Not recommended for this use case (requires special handling)

## Debugging Network Issues

```bash
# Inspect network
docker network inspect iaas-webhosting_default

# Check container network settings
docker inspect iaas-webhosting-php-1 | grep -A 20 NetworkSettings

# Trace DNS resolution
docker compose exec php nslookup db
docker compose exec php getent hosts db

# Monitor network traffic
docker compose exec nginx tcpdump -i eth0 -n

# Check open ports in container
docker compose exec php netstat -tulpn
```

## Security Considerations

1. **Network Isolation**: By default, only nginx is exposed to the host
2. **Credentials**: MySQL credentials in .env are available to all containers (not encrypted)
3. **Data in Transit**: No TLS between services (add nginx reverse proxy for TLS termination if needed)
4. **Host Network Access**: Applications should not run as root

## References

- [Docker Compose Networking](https://docs.docker.com/compose/networking/)
- [Docker Network Drivers](https://docs.docker.com/network/#network-drivers)
- [Service Discovery in Docker](https://docs.docker.com/engine/userguide/networking/configure-dns.md)
