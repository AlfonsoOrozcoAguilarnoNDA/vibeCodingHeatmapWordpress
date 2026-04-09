<?php
/**
 * Script: WordPress Contribution Heatmap (GitHub Style)
 * Generado por: Gemini 3 Flash
 * Arquitectura: Procedural PHP 8.x + Bootstrap 4.6.2
 */
 * Script PHP para Mostrar Heatmap de wordpress
 * Versión: 1.0
 * Modelo: Gemini 3 Flash
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


// 1. Integración con el entorno de WordPress
define('WP_USE_THEMES', false);
$wp_load_path = __DIR__ . '/wp-load.php';

// Buscar wp-load.php si se coloca en una subcarpeta del tema
if (!file_exists($wp_load_path)) {
    $wp_load_path = '../../../wp-load.php';
}

if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    die('Error: No se pudo conectar con el entorno de WordPress. Verifique la ubicación del archivo.');
}

global $wpdb;

// 2. Lógica de Datos: Obtener posts de los últimos 12 meses
$current_domain = $_SERVER['HTTP_HOST'];
$ai_model = "Gemini 3 Flash"; // [MARCADOR DE MODELO]
$wp_version = get_bloginfo('version');
$php_version = phpversion();

$query = "
    SELECT DATE(post_date) as date, COUNT(*) as count 
    FROM {$wpdb->posts} 
    WHERE post_type = 'post' 
    AND post_status = 'publish' 
    AND post_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    GROUP BY DATE(post_date)
";
$results = $wpdb->get_results($query, ARRAY_A);

$stats = [];
foreach ($results as $row) {
    $stats[$row['date']] = (int)$row['count'];
}

// Generar rango de fechas para el heatmap (365 días)
$end_date = new DateTime();
$start_date = (new DateTime())->modify('-1 year');
$interval = new DateInterval('P1D');
$period = new DatePeriod($start_date, $interval, $end_date);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP Activity Heatmap | <?php echo $current_domain; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        body { background-color: #f6f8fa; padding-top: 70px; padding-bottom: 70px; font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Helvetica,Arial,sans-serif; }
        .navbar-fixed { background: #24292e; color: white; }
        .footer-fixed { background: #24292e; color: #959da5; font-size: 0.85rem; }
        .heatmap-container { display: flex; flex-wrap: wrap; gap: 3px; max-width: 850px; margin: 0 auto; }
        .day-box { width: 12px; height: 12px; border-radius: 2px; background-color: #ebedf0; position: relative; }
        /* Intensidades estilo GitHub */
        .lvl-0 { background-color: #ebedf0; }
        .lvl-1 { background-color: #9be9a8; }
        .lvl-2 { background-color: #40c463; }
        .lvl-3 { background-color: #30a14e; }
        .lvl-4 { background-color: #216e39; }
        .day-box:hover::after {
            content: attr(data-info);
            position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%);
            background: #24292e; color: white; padding: 4px 8px; border-radius: 4px;
            font-size: 10px; white-space: nowrap; z-index: 100;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark navbar-fixed fixed-top">
    <span class="navbar-brand mb-0 h1">
        <i class="fas fa-robot mr-2"></i> IA: <?php echo $ai_model; ?>
    </span>
    <span class="navbar-text text-white d-none d-sm-inline">
        <i class="fas fa-globe mr-1"></i> <?php echo $current_domain; ?>
    </span>
</nav>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-dark"><i class="fas fa-chart-line mr-2"></i>Actividad de Publicaciones (Últimos 12 Meses)</h5>
                </div>
                <div class="card-body">
                    <div class="heatmap-container">
                        <?php
                        foreach ($period as $date) {
                            $d_str = $date->format('Y-m-d');
                            $count = $stats[$d_str] ?? 0;
                            
                            // Determinar nivel de intensidad
                            $lvl = 0;
                            if ($count > 0) $lvl = 1;
                            if ($count > 2) $lvl = 2;
                            if ($count > 5) $lvl = 3;
                            if ($count > 10) $lvl = 4;
                            
                            echo "<div class='day-box lvl-{$lvl}' data-info='{$d_str}: {$count} posts'></div>";
                        }
                        ?>
                    </div>
                    <div class="mt-4 d-flex align-items-center justify-content-end">
                        <small class="text-muted mr-2">Menos</small>
                        <div class="day-box lvl-0 mr-1"></div>
                        <div class="day-box lvl-1 mr-1"></div>
                        <div class="day-box lvl-2 mr-1"></div>
                        <div class="day-box lvl-3 mr-1"></div>
                        <div class="day-box lvl-4 mr-1"></div>
                        <small class="text-muted">Más</small>
                    </div>
                </div>
                <div class="card-footer bg-light text-center">
                    <small class="text-muted">Total de días analizados: 365 | Únicamente Post Type: 'post'</small>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="footer-fixed fixed-bottom py-2">
    <div class="container text-center">
        <span>
            <i class="fab fa-php mr-1"></i> PHP v<?php echo $php_version; ?> | 
            <i class="fab fa-wordpress mr-1"></i> WordPress v<?php echo $wp_version; ?>
        </span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
