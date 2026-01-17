# Base de Datos - ISO 27001 Platform

## Estructura
```
database/
├── schema.sql              # Schema completo en 3FN
├── seeds/
│   ├── 01_dominios.sql    # 4 dominios ISO 27001
│   ├── 02_controles.sql   # 93 controles Anexo A
│   └── 03_requerimientos.sql # 7 requerimientos obligatorios
├── migrations/
│   ├── 001_initial_schema.php # Ejecutar migración
│   └── rollback.php       # Rollback completo
└── README.md
```

## Tablas Principales

### Multi-Tenant
- `empresas` - Organizaciones (tenant root)
- `usuarios` - Usuarios por empresa

### Controles ISO 27001
- `controles_dominio` - 4 dominios (Org, Personas, Físicos, Tech)
- `controles` - 93 controles Anexo A
- `soa_entries` - SOA por empresa

### GAP Analysis
- `gap_items` - Brechas identificadas
- `acciones` - Acciones correctivas

### Evidencias
- `evidencias` - Documentos de implementación

### Requerimientos
- `requerimientos_base` - 7 documentos obligatorios
- `empresa_requerimientos` - Estado por empresa
- `requerimientos_controles` - Relación M:N

### Auditoría
- `audit_logs` - Log completo de cambios

## Normalización

**3FN Completa:**
- 1FN: Todos los atributos son atómicos
- 2FN: Sin dependencias parciales
- 3FN: Sin dependencias transitivas

**Constraints:**
- PRIMARY KEY en todas las tablas
- FOREIGN KEY con CASCADE apropiado
- UNIQUE para datos únicos de negocio
- CHECK constraints en enums
- INDEX en columnas de búsqueda frecuente

## Ejecutar Migración

### Opción 1: Docker (Recomendado)
```bash
# Ejecutar migración
docker-compose exec php php database/migrations/001_initial_schema.php

# Verificar
docker-compose exec db mysql -u iso_user -piso_pass iso_platform -e "SHOW TABLES;"
```

### Opción 2: Comando Directo
```bash
# Con PHP instalado localmente
php database/migrations/001_initial_schema.php

# Verificar con MySQL CLI
mysql -u iso_user -piso_pass iso_platform -e "SELECT COUNT(*) AS controles FROM controles;"
```

### Opción 3: Manual (SQL)
```bash
# Importar schema
mysql -u iso_user -piso_pass iso_platform < database/schema.sql

# Importar seeds
mysql -u iso_user -piso_pass iso_platform < database/seeds/01_dominios.sql
mysql -u iso_user -piso_pass iso_platform < database/seeds/02_controles.sql
mysql -u iso_user -piso_pass iso_platform < database/seeds/03_requerimientos.sql
```

## Rollback
```bash
# Docker
docker-compose exec php php database/migrations/rollback.php

# Local
php database/migrations/rollback.php
```

## Verificación Post-Migración
```bash
# Contar registros
docker-compose exec db mysql -u iso_user -piso_pass iso_platform <<EOF
SELECT 'Dominios' AS tabla, COUNT(*) AS registros FROM controles_dominio
UNION ALL
SELECT 'Controles', COUNT(*) FROM controles
UNION ALL
SELECT 'Requerimientos', COUNT(*) FROM requerimientos_base
UNION ALL
SELECT 'Relaciones', COUNT(*) FROM requerimientos_controles;
EOF
```

**Resultado esperado:**
```
+------------------+-----------+
| tabla            | registros |
+------------------+-----------+
| Dominios         |         4 |
| Controles        |        93 |
| Requerimientos   |         7 |
| Relaciones       |        22 |
+------------------+-----------+
```

## Índices Críticos

### Performance
- `idx_soa_empresa_control` - Búsquedas de SOA
- `idx_gap_soa` - GAPs por control
- `idx_evidencias_empresa_control` - Evidencias por control
- `idx_audit_empresa_fecha` - Logs de auditoría

### Seguridad (IDOR)
- `uk_soa_empresa_control` - Previene duplicados
- `uk_usuarios_email_empresa` - Email único por tenant
- Todos los índices `empresa_id` - Validación tenant

### Business Logic
- `uk_controles_codigo` - Códigos únicos ISO
- `uk_empresas_ruc` - RUC único
- `ft_gap_brecha` - Búsqueda full-text en GAPs

## Triggers (Próxima Fase)

Pendientes para Fase 3:
- Auto-crear SOA entries al crear empresa
- Auto-crear empresa_requerimientos al crear empresa
- Auto-calcular avance de GAPs
- Auto-actualizar estado de requerimientos
- Audit logs automáticos

## Constraints de Negocio

### GAP Items
- Solo se pueden crear en controles aplicables
- Solo en controles no implementados o parciales
- Soft delete cascada a acciones

### Evidencias
- Solo en controles aplicables
- Validación MIME real
- Hash SHA256 único

### Requerimientos
- Completitud automática basada en:
  - Todos los controles implementados
  - Todas las evidencias aprobadas

## Backup
```bash
# Backup completo
docker-compose exec db mysqldump -u iso_user -piso_pass iso_platform > backup_$(date +%Y%m%d).sql

# Restaurar
docker-compose exec -T db mysql -u iso_user -piso_pass iso_platform < backup_20250117.sql
```

## Próximos Pasos (Fase 3)

- [ ] Crear triggers para auto-población
- [ ] Implementar stored procedures para completitud
- [ ] Agregar vistas optimizadas para dashboard
- [ ] Implementar particionamiento en audit_logs
