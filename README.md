# README.md

**Ubicación:** `README.md` (REEMPLAZAR el actual)

```markdown
# ISO 27001 Compliance Platform v2.0

Plataforma de gestión de cumplimiento de seguridad de la información basada en ISO 27001:2022.

## Características

- Multi-tenancy estricto con aislamiento por empresa
- Gestión de 93 controles ISO 27001
- Análisis de brechas (GAP Analysis)
- Gestión de evidencias con validación
- 7 requerimientos obligatorios automatizados
- Auditoría completa de cambios

## Seguridad

- CSRF protection con regeneración automática
- IDOR protection con validación de tenant
- Rate limiting granular por acción
- Session fingerprinting (IP + User-Agent)
- Password hashing con Argon2id
- File upload con validación MIME real y anti-malware
- SQL Injection prevention (prepared statements)
- XSS prevention (sanitización automática)
- Security headers (CSP, HSTS, X-Frame-Options)

## Requisitos

- PHP 8.3+
- MySQL/MariaDB 10.11+
- Nginx/Apache
- Composer
- Docker (opcional)

## Instalación

### Con Docker

```bash
# Clonar repositorio
git clone <repo-url>
cd iso-27001

# Copiar .env
cp .env.example .env

# Editar .env y configurar APP_KEY
# Generar: php -r "echo bin2hex(random_bytes(16));"

# Instalar dependencias
docker-compose exec php composer install

# Levantar contenedores
docker-compose up -d

# Acceder
http://localhost:8080
```

### Sin Docker

```bash
# Instalar dependencias
composer install

# Configurar .env
cp .env.example .env

# Configurar permisos
chmod 775 storage/logs storage/cache storage/sessions public/uploads

# Configurar virtual host apuntando a /public
```

## Estructura

```
iso_platform/
├── config/          # Configuración (app, database, security, etc)
├── src/
│   ├── Core/        # Framework base (Router, Request, Database)
│   ├── Middleware/  # CSRF, Auth, RateLimit, Tenant
│   └── Services/    # Log, File, Cache
├── public/          # Entry point
├── routes/          # Definición de rutas
├── storage/         # Logs, cache, sessions
└── vendor/          # Dependencias
```

## Estado del Proyecto

### Fase 1 - Infraestructura y Seguridad Base ✓

- [x] Configuración completa
- [x] Core framework (MVC)
- [x] Middlewares de seguridad
- [x] Servicios base (Log, File, Cache)
- [x] Entry point con error handling

### Próximas Fases

- [ ] Fase 2: Base de datos normalizada
- [ ] Fase 3: Multi-tenancy
- [ ] Fase 4: Autenticación
- [ ] Fase 5-14: Módulos funcionales

## Seguridad Implementada

**CSRF:**
- Tokens de 32 caracteres
- Regeneración automática
- Validación en POST/PUT/DELETE/PATCH

**Rate Limiting:**
- Login: 5 intentos / 15 min
- Upload: 10 archivos / hora
- Forms: 20 envíos / 5 min
- Bloqueo exponencial

**Session:**
- Regeneración cada 5 minutos
- Timeout absoluto 2 horas
- Fingerprinting IP + User-Agent
- Cookies HttpOnly, Secure, SameSite=Strict

**File Upload:**
- Validación MIME real (finfo)
- Escaneo anti-malware
- Prevención path traversal
- Hash SHA256 de archivos
- Organización por empresa/fecha

## Licencia

Proprietary

## Autor

Anderson Leon
```


