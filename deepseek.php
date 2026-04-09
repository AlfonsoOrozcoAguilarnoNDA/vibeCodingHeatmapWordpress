<?php
/**
 * Script: WordPress Post Heatmap (GitHub-style)
 * Autor: IA Asistente (Claude 3.5 Sonnet - [INSERTAR MODELO AQUÍ])
 * Uso: Colocar en la raíz del sitio o carpeta del tema. Acceder directamente.
 */
/**
 * Script PHP para Mostrar Heatmap de wordpress
 * Versión: 3.0
 * Modelo: Deepseek pero se identifica a si mismo como sonnet.
 * Licencia: MIT
 *
 * Copyright (c) 2026 Alfonso Orozco Aguilar
 *
 * Se otorga permiso, de forma gratuita, a cualquier persona que obtenga una copia
 * de este software y los archivos de documentación asociados (el "Software"), para
 * tratar en el Software sin restricción, incluyendo sin limitación los derechos
 * de usar, copiar, modificar, fusionar, publicar, distribuir, sublicenciar, y/o
 * vender copias del Software, y para permitir a las personas a las que se les
 * proporcione el Software a hacerlo, sujeto a las siguientes condiciones:
 *
 * El aviso de copyright anterior y este aviso de permiso se incluirán en todas
 * las copias o partes sustanciales del Software.
 *
 * EL SOFTWARE SE PROPORCIONA "TAL CUAL", SIN GARANTÍA DE NINGÚN TIPO, EXPRESA O
 * IMPLÍCITA, INCLUYENDO PERO NO LIMITADO A LAS GARANTÍAS DE COMERCIABILIDAD,
 * IDONEIDAD PARA UN PROPÓSITO PARTICULAR Y NO INFRACCIÓN. EN NINGÚN CASO LOS
 * AUTORES O TITULARES DEL COPYRIGHT SERÁN RESPONSABLES DE NINGUNA RECLAMACIÓN,
 * DAÑOS U OTRAS RESPONSABILIDADES, YA SEA EN UNA ACCIÓN DE CONTRATO, AGRAVIO O
 * CUALQUIER OTRO MOTIVO, DERIVADAS DE, FUERA DE O EN CONEXIÓN CON EL SOFTWARE
 * O EL USO U OTROS TRATOS EN EL SOFTWARE.
*/ 

// Cargar WordPress core para usar $wpdb y funciones de fecha
require_once(dirname(__FILE__) . '/wp-load.php');

global $wpdb;

// Configurar zona horaria de WordPress
if (function_exists('wp_timezone')) {
    $timezone = wp_timezone();
} else {
    $timezone = new DateTimeZone('UTC');
}

// Fechas: últimos 12 meses hasta hoy
$end = new DateTime('now', $timezone);
$start = clone $end;
$start->modify('-12 months');
$start->modify('first day of this month');

// Consulta: posts publicados por día
$query = $wpdb->prepare("
    SELECT
        DATE(post_date) as post_date,
        COUNT(ID) as count
    FROM {$wpdb->posts}
    WHERE post_type = 'post'
        AND post_status = 'publish'
        AND post_date >= %s
        AND post_date <= %s
    GROUP BY DATE(post_date)
    ORDER BY post_date ASC
", $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'));

$results = $wpdb->get_results($query);

// Crear array asociativo [fecha => cantidad]
$posts_by_day = [];
foreach ($results as $row) {
    $posts_by_day[$row->post_date] = (int)$row->count;
}

// Generar estructura de semanas para heatmap
$heatmap_data = [];
$current = clone $start;
$max_count = max($posts_by_day) ?: 1;

while ($current <= $end) {
    $year_week = $current->format('Y-W');
    $day_key = $current->format('Y-m-d');
    $count = isset($posts_by_day[$day_key]) ? $posts_by_day[$day_key] : 0;

    // Calcular intensidad (0-4)
    if ($count == 0) $level = 0;
    elseif ($count == 1) $level = 1;
    elseif ($count <= 3) $level = 2;
    elseif ($count <= 6) $level = 3;
    else $level = 4;

    $heatmap_data[$year_week][] = [
        'date' => $day_key,
        'count' => $count,
        'level' => $level
    ];

    $current->modify('+1 day');
}

// Ordenar semanas
ksort($heatmap_data);

// Obtener dominio actual
$current_domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'dominio no detectado';

// Versiones
$php_version = phpversion();
$wp_version = get_bloginfo('version');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heatmap de Publicaciones · <?php echo esc_html($current_domain); ?></title>
    <!-- Bootstrap 4.6.2 + FontAwesome 5.15.4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: #f0f2f5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
        }
        .heatmap-card {
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }
        .heatmap-card:hover {
            transform: translateY(-5px);
        }
        .heatmap-container {
            overflow-x: auto;
            padding: 15px 0;
        }
        .heatmap-grid {
            display: flex;
            gap: 4px;
            min-width: 700px;
        }
        .week-column {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .day-cell {
            width: 28px;
            height: 28px;
            border-radius: 4px;
            transition: all 0.2s ease;
            cursor: default;
            position: relative;
        }
        .day-cell.level-0 { background-color: #ebedf0; }
        .day-cell.level-1 { background-color: #9be9a8; }
        .day-cell.level-2 { background-color: #40c463; }
        .day-cell.level-3 { background-color: #30a14e; }
        .day-cell.level-4 { background-color: #216e39; }

        .day-cell:hover {
            transform: scale(1.2);
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .day-cell:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e1f22;
            color: white;
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 8px;
            white-space: nowrap;
            z-index: 100;
            font-weight: normal;
            pointer-events: none;
        }
        .month-label {
            font-size: 12px;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 8px;
            text-align: center;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            display: inline-block;
        }
        .fixed-header, .fixed-footer {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .fixed-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .fixed-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 10px 0;
            font-size: 13px;
            border-top: 1px solid #e9ecef;
            background: rgba(255,255,255,0.95);
        }
        .main-content {
            margin-top: 90px;
            margin-bottom: 70px;
        }
        @media (max-width: 768px) {
            .day-cell { width: 20px; height: 20px; }
            .week-column { gap: 2px; }
            .heatmap-grid { gap: 2px; }
            .month-label { font-size: 9px; }
        }
    </style>
</head>
<body>

<!-- HEADER FIJO -->
<div class="fixed-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <i class="fas fa-brain text-primary mr-2"></i>
                <strong>Modelo IA:</strong> [INSERTAR MODELO AQUÍ]
                <span class="badge badge-secondary ml-2">Claude 3.5 Sonnet</span>
            </div>
            <div>
                <i class="fas fa-globe text-info mr-1"></i>
                <span class="font-weight-bold"><?php echo esc_html($current_domain); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- CONTENIDO PRINCIPAL -->
<div class="container main-content">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card heatmap-card border-0">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-fire text-danger mr-2"></i> 
                        Mapa de calor de publicaciones
                    </h3>
                    <p class="text-muted mt-2">
                        <i class="far fa-calendar-alt"></i> 
                        Últimos 12 meses · Actividad editorial día a día
                    </p>
                </div>
                <div class="card-body">
                    <div class="heatmap-container">
                        <?php if (empty($heatmap_data)): ?>
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-chart-line"></i> No hay datos de publicaciones en los últimos 12 meses.
                            </div>
                        <?php else: ?>
                            <div class="heatmap-grid">
                                <?php 
                                $month_index = 0;
                                $last_month_shown = null;
                                foreach ($heatmap_data as $week_number => $days):
                                    // Extraer mes del primer día de la semana
                                    $first_day = $days[0]['date'] ?? '';
                                    $month_name = $first_day ? date('M', strtotime($first_day)) : '';
                                    $current_month_key = $first_day ? date('Y-m', strtotime($first_day)) : '';
                                ?>
                                    <div class="week-column">
                                        <?php if ($last_month_shown !== $current_month_key && $month_name): ?>
                                            <div class="month-label">
                                                <?php echo esc_html($month_name); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="month-label" style="opacity:0;">&nbsp;</div>
                                        <?php endif; ?>
                                        <?php 
                                        foreach ($days as $day):
                                            $tooltip = sprintf(
                                                "%s · %d publicación%s",
                                                date('d/m/Y', strtotime($day['date'])),
                                                $day['count'],
                                                ($day['count'] != 1) ? 'es' : ''
                                            );
                                        ?>
                                            <div class="day-cell level-<?php echo $day['level']; ?>" 
                                                 data-tooltip="<?php echo esc_attr($tooltip); ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php 
                                    $last_month_shown = $current_month_key;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Leyenda de intensidad -->
                    <div class="d-flex justify-content-end align-items-center mt-4 flex-wrap">
                        <span class="text-muted mr-2 small">Menos</span>
                        <span class="legend-color level-0 mr-1"></span>
                        <span class="legend-color level-1 mr-1"></span>
                        <span class="legend-color level-2 mr-1"></span>
                        <span class="legend-color level-3 mr-1"></span>
                        <span class="legend-color level-4 mr-2"></span>
                        <span class="text-muted small">Más publicaciones</span>
                        <span class="ml-3 small text-info">
                            <i class="fas fa-chart-line"></i> 
                            Máximo diario: <?php echo $max_count; ?> posts
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER FIJO -->
<div class="fixed-footer">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <i class="fab fa-php"></i> PHP v<?php echo esc_html($php_version); ?>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <i class="fab fa-wordpress"></i> WordPress v<?php echo esc_html($wp_version); ?>
            </div>
            <div>
                <i class="far fa-clock"></i> 
                Datos actualizados hasta: <?php echo $end->format('d/m/Y'); ?>
            </div>
        </div>
    </div>
</div>

<!-- Scripts Bootstrap (jQuery, Popper, Bootstrap JS) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
</body>
</html>
