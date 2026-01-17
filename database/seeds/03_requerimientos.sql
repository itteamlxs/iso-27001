-- ============================================================================
-- SEED: 7 Requerimientos Obligatorios ISO 27001
-- ============================================================================

-- Insertar requerimientos base
INSERT INTO `requerimientos_base` (`numero`, `identificador`, `descripcion`) VALUES
(1, 'DOC-POL-001', 'Manual de políticas de seguridad de la información'),
(2, 'DOC-INV-001', 'Inventario de activos de información'),
(3, 'DOC-CAP-001', 'Plan anual de capacitaciones en seguridad'),
(4, 'DOC-CON-001', 'Estrategia de concientización en seguridad'),
(5, 'DOC-EVI-001', 'Evidencia de cumplimiento del plan de capacitación y estrategia de concientización'),
(6, 'DOC-INC-001', 'Manual de gestión de incidentes de seguridad de la información'),
(7, 'DOC-MON-001', 'Evidencia de monitoreo continuo de seguridad');

-- ============================================================================
-- Relacionar requerimientos con controles
-- ============================================================================

-- Requerimiento 1: Manual de políticas (relacionado con políticas y organización)
INSERT INTO `requerimientos_controles` (`requerimiento_base_id`, `control_id`) VALUES
(1, (SELECT id FROM controles WHERE codigo = '5.1')),
(1, (SELECT id FROM controles WHERE codigo = '5.2')),
(1, (SELECT id FROM controles WHERE codigo = '5.15'));

-- Requerimiento 2: Inventario de activos
INSERT INTO `requerimientos_controles` (`requerimiento_base_id`, `control_id`) VALUES
(2, (SELECT id FROM controles WHERE codigo = '5.9')),
(2, (SELECT id FROM controles WHERE codigo = '5.10')),
(2, (SELECT id FROM controles WHERE codigo = '5.11')),
(2, (SELECT id FROM controles WHERE codigo = '5.12')),
(2, (SELECT id FROM controles WHERE codigo = '5.13'));

-- Requerimiento 3: Plan de capacitaciones
INSERT INTO `requerimientos_controles` (`requerimiento_base_id`, `control_id`) VALUES
(3, (SELECT id FROM controles WHERE codigo = '6.3')),
(3, (SELECT id FROM controles WHERE codigo = '6.2'));

-- Requerimiento 4: Estrategia de concientización
INSERT INTO `requerimientos_controles` (`requerimiento_base_id`, `control_id`) VALUES
(4, (SELECT id FROM controles WHERE codigo = '6.3')),
(4, (SELECT id FROM controles WHERE codigo = '6.8'));

-- Requerimiento 5: Evidencia de cumplimiento
INSERT INTO `requerimientos_controles` (`requerimiento_base_id`, `control_id`) VALUES
(5, (SELECT id FROM controles WHERE codigo = '6.3')),
(5, (SELECT id FROM controles WHERE codigo = '5.33'));

-- Requerimiento 6: Manual de gestión de incidentes
INSERT INTO `requerimientos_controles` (`requerimiento_base_id`, `control_id`) VALUES
(6, (SELECT id FROM controles WHERE codigo = '5.24')),
(6, (SELECT id FROM controles WHERE codigo = '5.25')),
(6, (SELECT id FROM controles WHERE codigo = '5.26')),
(6, (SELECT id FROM controles WHERE codigo = '5.27')),
(6, (SELECT id FROM controles WHERE codigo = '5.28'));

-- Requerimiento 7: Evidencia de monitoreo continuo
INSERT INTO `requerimientos_controles` (`requerimiento_base_id`, `control_id`) VALUES
(7, (SELECT id FROM controles WHERE codigo = '8.15')),
(7, (SELECT id FROM controles WHERE codigo = '8.16')),
(7, (SELECT id FROM controles WHERE codigo = '5.35'));
