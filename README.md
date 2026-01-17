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
- Docker (recomendado)

## Instalación

### Con Docker
```bash
# Clonar repositorio
git clone <repo-url>
cd iso-27001

# Copiar .env
cp .env.example .env

# Generar APP_KEY
sed -i "s/APP_KEY=/APP_KEY=$(php -r 'echo bin2hex(random_bytes(16));')/" .env

# Levantar contenedores
docker compose up -d

# Instalar dependencias
docker compose exec php composer install

# Ejecutar migración
docker compose exec php php database/migrations/001_initial_schema.php

# Acceder
http://localhost:8080
```

### Verificar Base de Datos
```bash
# Ver tablas
docker compose exec db mysql -u iso_user -piso_pass iso_platform -e "SHOW TABLES;"

# Verificar datos
docker compose exec db mysql -u iso_user -piso_pass iso_platform -e "
SELECT 'Dominios' AS tabla, COUNT(*) AS total FROM controles_dominio
UNION ALL SELECT 'Controles', COUNT(*) FROM controles
UNION ALL SELECT 'Requerimientos', COUNT(*) FROM requerimientos_base;"
```

## Estructura
```
iso_platform/
├── config/          # Configuración (app, database, security, etc)
├── src/
│   ├── Core/        # Framework base (Router, Request, Database)
│   ├── Middleware/  # CSRF, Auth, RateLimit, Tenant
│   └── Services/    # Log, File, Cache
├── database/        # Schema, seeds y migraciones
│   ├── schema.sql
│   ├── seeds/
│   └── migrations/
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

### Fase 2 - Base de Datos Normalizada ✓

- [x] Schema 3FN con 12 tablas
- [x] 4 dominios ISO 27001
- [x] 93 controles Anexo A
- [x] 7 requerimientos obligatorios
- [x] Constraints e índices optimizados
- [x] Scripts de migración

### Próximas Fases

- [ ] Fase 3: Multi-tenancy
- [ ] Fase 4: Autenticación
- [ ] Fase 5-14: Módulos funcionales

## Base de Datos

**12 Tablas en 3FN:**
- empresas, usuarios (multi-tenant)
- controles_dominio, controles (93 controles ISO)
- soa_entries (SOA por empresa)
- gap_items, acciones (GAP analysis)
- evidencias (documentos)
- requerimientos_base, empresa_requerimientos, requerimientos_controles
- audit_logs (auditoría completa)

**Datos iniciales:**
- 4 dominios
- 93 controles
- 7 requerimientos
- 22 relaciones

Ver documentación completa en `database/README.md`

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
