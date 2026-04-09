<?php
/**
 * WordPress Post Heatmap
 * Modelo: [INSERTAR MODELO AQUÍ]
 * 
 * Colocar este archivo en la raíz de WordPress o en una carpeta del tema.
 * Acceder vía: https://tudominio.com/ruta/heatmap.php
 */
/**
 * Script PHP para Mostrar Heatmap de wordpress
 * Versión: 3.0
 * Modelo: 2.5 Instant - Kimi
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

// Cargar WordPress
require_once __DIR__ . '/wp-load.php';

// Obtener datos del heatmap (últimos 12 meses)
global $wpdb;

$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-12 months'));

$query = $wpdb->prepare("
    SELECT DATE(post_date) as publish_date, COUNT(ID) as post_count
    FROM {$wpdb->posts}
    WHERE post_type = 'post'
    AND post_status = 'publish'
    AND post_date >= %s
    AND post_date <= %s
    GROUP BY DATE(post_date)
    ORDER BY publish_date ASC
", $start_date, $end_date);

$results = $wpdb->get_results($query);

// Organizar datos por fecha
$heatmap_data = [];
$max_posts = 0;

foreach ($results as $row) {
    $heatmap_data[$row->publish_date] = (int) $row->post_count;
    if ($row->post_count > $max_posts) {
        $max_posts = $row->post_count;
    }
}

// Generar array de los últimos 12 meses con semanas
$heatmap_grid = [];
$current = new DateTime($start_date);
$end = new DateTime($end_date);

while ($current <= $end) {
    $week_num = (int) $current->format('W');
    $year = $current->format('Y');
    $week_key = $year . '-W' . str_pad($week_num, 2, '0', STR_PAD_LEFT);
    $day_of_week = (int) $current->format('w'); // 0 = domingo
    
    $date_str = $current->format('Y-m-d');
    $count = isset($heatmap_data[$date_str]) ? $heatmap_data[$date_str] : 0;
    
    // Calcular intensidad (0-4)
    // Solo calcular intensidad si hay posts (> 0)
    $intensity = 0;
    if ($count > 0 && $max_posts > 0) {
        $percentage = $count / $max_posts;
        if ($percentage <= 0.25) $intensity = 1;
        elseif ($percentage <= 0.5) $intensity = 2;
        elseif ($percentage <= 0.75) $intensity = 3;
        else $intensity = 4;
    }
    
    if (!isset($heatmap_grid[$week_key])) {
        $heatmap_grid[$week_key] = array_fill(0, 7, null);
    }
    
    $heatmap_grid[$week_key][$day_of_week] = [
        'date' => $date_str,
        'count' => $count,
        'intensity' => $intensity
    ];
    
    $current->modify('+1 day');
}

// Nombres de días
$days_labels = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$months_labels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

// Obtener información del sistema
$php_version = phpversion();
$wp_version = get_bloginfo('version');
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
$site_name = get_bloginfo('name');

// Colores para el heatmap (estilo GitHub)
// Índice 0 = sin posts (gris muy claro, casi invisible)
$colors = [
    0 => '#ebedf0',      // Sin posts - color base neutro
    1 => '#9be9a8',      // Pocos posts
    2 => '#40c463',      // Algunos posts
    3 => '#30a14e',      // Varios posts
    4 => '#216e39'       // Muchos posts
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Calor - <?php echo esc_html($site_name); ?></title>
    
    <!-- Bootstrap 4.6.2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    
    <!-- FontAwesome 5.15.4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
    
    <style>
        body {
            background-color: #f6f8fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
            padding-top: 70px;
            padding-bottom: 60px;
            min-height: 100vh;
        }
        
        /* Header fijo */
        .fixed-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            padding: 0 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .model-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }
        
        .domain-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }
        
        /* Footer fijo */
        .fixed-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            background: #24292e;
            color: #8b949e;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }
        
        .footer-info {
            display: flex;
            gap: 30px;
        }
        
        .footer-info span {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Card del heatmap */
        .heatmap-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: none;
            overflow: hidden;
        }
        
        .card-header-custom {
            background: #fafbfc;
            border-bottom: 1px solid #e1e4e8;
            padding: 20px;
        }
        
        .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #24292e;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Heatmap Grid */
        .heatmap-container {
            padding: 30px;
            overflow-x: auto;
        }
        
        .heatmap-wrapper {
            display: inline-block;
            min-width: 100%;
        }
        
        .heatmap {
            display: flex;
            gap: 3px;
        }
        
        .week-column {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        
        .day-cell {
            width: 12px;
            height: 12px;
            border-radius: 2px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        
        /* Días sin posts: sin efecto hover y cursor default */
        .day-cell.empty {
            cursor: default;
        }
        
        .day-cell.empty:hover {
            transform: none;
            box-shadow: none;
        }
        
        /* Días con posts: efecto hover */
        .day-cell.has-posts:hover {
            transform: scale(1.3);
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        /* Tooltip personalizado - solo para días con posts */
        .day-cell.has-posts::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #24292e;
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            margin-bottom: 5px;
            z-index: 20;
        }
        
        .day-cell.has-posts:hover::after {
            opacity: 1;
        }
        
        /* Labels */
        .heatmap-labels {
            display: flex;
            margin-bottom: 10px;
        }
        
        .month-labels {
            display: flex;
            margin-left: 35px;
            gap: 3px;
        }
        
        .month-label {
            font-size: 0.75rem;
            color: #586069;
            min-width: 12px;
            text-align: center;
        }
        
        .days-labels {
            display: flex;
            flex-direction: column;
            gap: 3px;
            margin-right: 10px;
            padding-top: 20px;
        }
        
        .day-label {
            font-size: 0.75rem;
            color: #586069;
            height: 12px;
            line-height: 12px;
            text-align: right;
            width: 25px;
        }
        
        .day-label:nth-child(odd) {
            opacity: 0;
        }
        
        /* Leyenda */
        .heatmap-legend {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
            margin-top: 20px;
            font-size: 0.75rem;
            color: #586069;
        }
        
        .legend-item {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }
        
        /* Stats */
        .stats-row {
            display: flex;
            gap: 30px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e1e4e8;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #24292e;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #586069;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .heatmap-container {
                padding: 15px;
            }
            
            .day-cell {
                width: 10px;
                height: 10px;
            }
            
            .header-content {
                flex-direction: column;
                gap: 5px;
                text-align: center;
            }
            
            .fixed-header {
                height: auto;
                padding: 10px;
            }
            
            body {
                padding-top: 90px;
            }
        }
    </style>
</head>
<body>

    <!-- Header Fijo -->
    <header class="fixed-header">
        <div class="header-content">
            <div class="model-badge">
                <i class="fas fa-robot mr-2"></i>
                <?php echo esc_html('[INSERTAR MODELO AQUÍ]'); ?>
            </div>
            <div class="domain-info">
                <i class="fas fa-globe"></i>
                <span><?php echo esc_html($domain); ?></span>
                <span class="mx-2">|</span>
                <i class="fas fa-blog"></i>
                <span><?php echo esc_html($site_name); ?></span>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="card heatmap-card">
                    <div class="card-header-custom">
                        <h2 class="card-title">
                            <i class="fas fa-fire-alt text-warning"></i>
                            Mapa de Calor de Publicaciones
                        </h2>
                        <p class="text-muted mb-0 mt-2" style="font-size: 0.9rem;">
                            Actividad de posts publicados en los últimos 12 meses
                        </p>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="heatmap-container">
                            <div class="heatmap-wrapper">
                                <!-- Labels de meses -->
                                <div class="heatmap-labels">
                                    <div style="width: 35px;"></div>
                                    <div class="month-labels" id="monthLabels"></div>
                                </div>
                                
                                <!-- Heatmap -->
                                <div style="display: flex;">
                                    <div class="days-labels">
                                        <?php foreach ($days_labels as $label): ?>
                                            <div class="day-label"><?php echo $label; ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="heatmap" id="heatmapGrid">
                                        <?php 
                                        $week_count = 0;
                                        foreach ($heatmap_grid as $week_key => $days): 
                                            $week_count++;
                                        ?>
                                            <div class="week-column">
                                                <?php for ($d = 0; $d < 7; $d++): 
                                                    $day_data = $days[$d] ?? null;
                                                    
                                                    if ($day_data && $day_data['count'] > 0):
                                                        // Día CON posts
                                                        $color = $colors[$day_data['intensity']];
                                                        $tooltip = date('M j, Y', strtotime($day_data['date'])) . ': ' . 
                                                                   $day_data['count'] . ' ' . 
                                                                   ($day_data['count'] === 1 ? 'post' : 'posts');
                                                        $css_class = 'day-cell has-posts';
                                                    else:
                                                        // Día SIN posts (0 o null)
                                                        $color = $colors[0];
                                                        $tooltip = '';
                                                        $css_class = 'day-cell empty';
                                                    endif;
                                                ?>
                                                    <div class="<?php echo $css_class; ?>" 
                                                         style="background-color: <?php echo $color; ?>"
                                                         data-tooltip="<?php echo esc_attr($tooltip); ?>"
                                                         data-week="<?php echo $week_count; ?>"
                                                         data-day="<?php echo $d; ?>">
                                                    </div>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Leyenda -->
                                <div class="heatmap-legend">
                                    <span>Menos</span>
                                    <?php foreach ($colors as $intensity => $color): ?>
                                        <div class="legend-item" style="background-color: <?php echo $color; ?>"></div>
                                    <?php endforeach; ?>
                                    <span>Más</span>
                                </div>
                                
                                <!-- Estadísticas -->
                                <div class="stats-row">
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo number_format(array_sum($heatmap_data)); ?></span>
                                        <span class="stat-label">Total Posts (12 meses)</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo count(array_filter($heatmap_data, function($v) { return $v > 0; })); ?></span>
                                        <span class="stat-label">Días con actividad</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo $max_posts; ?></span>
                                        <span class="stat-label">Máximo posts en un día</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value"><?php echo round(array_sum($heatmap_data) / max(count(array_filter($heatmap_data, function($v) { return $v > 0; })), 1), 1); ?></span>
                                        <span class="stat-label">Promedio por día activo</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Fijo -->
    <footer class="fixed-footer">
        <div class="footer-info">
            <span>
                <i class="fab fa-php"></i>
                PHP <?php echo esc_html($php_version); ?>
            </span>
            <span>
                <i class="fab fa-wordpress"></i>
                WordPress <?php echo esc_html($wp_version); ?>
            </span>
            <span>
                <i class="far fa-calendar-alt"></i>
                <?php echo date('Y'); ?>
            </span>
        </div>
    </footer>

    <!-- jQuery y Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Generar labels de meses dinámicamente
            const monthLabels = document.getElementById('monthLabels');
            const weekColumns = document.querySelectorAll('.week-column');
            const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            
            let currentMonth = -1;
            let lastMonthShown = -1;
            
            weekColumns.forEach((col, index) => {
                const firstDayCell = col.querySelector('.day-cell[data-day="0"]');
                if (firstDayCell) {
                    const tooltip = firstDayCell.getAttribute('data-tooltip');
                    if (tooltip) {
                        const dateMatch = tooltip.match(/([A-Za-z]{3})/);
                        if (dateMatch) {
                            const monthName = dateMatch[1];
                            const monthIndex = months.findIndex(m => m.toLowerCase() === monthName.toLowerCase());
                            
                            if (monthIndex !== -1 && monthIndex !== lastMonthShown && index % 4 === 0) {
                                const label = document.createElement('div');
                                label.className = 'month-label';
                                label.textContent = months[monthIndex];
                                label.style.minWidth = '40px';
                                monthLabels.appendChild(label);
                                lastMonthShown = monthIndex;
                            } else {
                                const spacer = document.createElement('div');
                                spacer.className = 'month-label';
                                spacer.style.minWidth = '12px';
                                monthLabels.appendChild(spacer);
                            }
                        }
                    }
                }
            });
            
            // Animación de entrada solo para días con posts
            const cells = document.querySelectorAll('.day-cell.has-posts');
            cells.forEach((cell, index) => {
                cell.style.opacity = '0';
                cell.style.transform = 'scale(0)';
                setTimeout(() => {
                    cell.style.transition = 'all 0.3s ease';
                    cell.style.opacity = '1';
                    cell.style.transform = 'scale(1)';
                }, index * 20);
            });
        });
    </script>
</body>
</html>
