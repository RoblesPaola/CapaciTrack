-- ============================================================
-- CapaciTrack - Script de actualización de base de datos
-- Ejecutar en Supabase SQL Editor sobre la base de datos existente
-- ============================================================

-- 1. Tabla para rastrear qué módulos ha completado cada usuario
--    (la tabla progreso_lecciones existente es para lecciones de texto,
--     pero los módulos de contenido multimedia no la usan)
CREATE TABLE IF NOT EXISTS progreso_modulos (
    id               SERIAL PRIMARY KEY,
    usuario_id       INT REFERENCES usuarios(id) ON DELETE CASCADE,
    modulo_id        INT REFERENCES modulos(id)  ON DELETE CASCADE,
    completado       BOOLEAN   DEFAULT FALSE,
    fecha_completado TIMESTAMP DEFAULT NULL,
    UNIQUE(usuario_id, modulo_id)
);

CREATE INDEX IF NOT EXISTS idx_progreso_modulos_usuario ON progreso_modulos(usuario_id);
CREATE INDEX IF NOT EXISTS idx_progreso_modulos_modulo  ON progreso_modulos(modulo_id);

-- 2. Agregar columnas a inscripciones para tracking completo de la experiencia del usuario
ALTER TABLE inscripciones
    ADD COLUMN IF NOT EXISTS nota_evaluacion      INT           DEFAULT NULL,   -- % obtenido en evaluación (0-100)
    ADD COLUMN IF NOT EXISTS intentos_evaluacion  INT           DEFAULT 0,      -- Cuántas veces intentó la evaluación
    ADD COLUMN IF NOT EXISTS ultima_visita        TIMESTAMPTZ  DEFAULT NOW();   -- Última vez que accedió al curso

-- 3. Comentarios descriptivos
COMMENT ON TABLE  progreso_modulos                       IS 'Rastrea qué módulos multimedia ha completado cada usuario';
COMMENT ON COLUMN inscripciones.nota_evaluacion          IS 'Porcentaje obtenido en la evaluación final (0-100)';
COMMENT ON COLUMN inscripciones.intentos_evaluacion      IS 'Número de veces que el usuario intentó la evaluación';
COMMENT ON COLUMN inscripciones.ultima_visita            IS 'Timestamp de la última vez que el usuario accedió al curso';

-- 4. Inicializar ultima_visita para inscripciones existentes
UPDATE inscripciones SET ultima_visita = fecha_inscripcion WHERE ultima_visita IS NULL;
