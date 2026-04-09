<?php
 * Script PHP para Mostrar Heatmap de wordpress
 * Versión: 1.0
 * Modelo: Qwen 3.6
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

declare(strict_types=1);

// 1. CARGA DEL ENTORNO WORDPRESS (Ajusta la ruta si lo colocas en una subcarreta del tema)
$wp_load_paths = [
    __DIR__ . '/wp-load.php',
    dirname(__DIR__) . '/wp-load.php',
    $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php'
];
$wordpress_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wordpress_loaded = true;
        break;
    }
}
if (!$wordpress_loaded) {
    die('<h1 style="color:#dc3545; font-family:sans-serif; padding:20px;">Error Crítico: No se pudo localizar wp-load.php. Coloca este archivo en la raíz de WordPress.</h1>');
}

global $wpdb;

// 2. CONFIGURACIÓN DE DATOS
$ai_model       = '[INSERTAR MODELO AQUÍ]';
$domain         = $_SERVER['HTTP_HOST'] ?? 'dominio-desconocido.com';
$php_version    = phpversion();
$wp_version     = function_exists('get_bloginfo') ? get_bloginfo('version') : 'N/D';

// 3. LÓGICA DEL MAPA DE CALOR (Últimos 12 meses, solo posts publicados)
$end_date   = new DateTime();
$start_date = new DateTime('-12 months');

// Alinear al domingo más cercano para mantener la rejilla 7x52 estilo GitHub
$day_of_week = (int)$start_date->format('w'); // 0 = Domingo
$start_date->modify("-{$day_of_week} days");

$query = $wpdb->prepare(
    "SELECT DATE(post_date) AS day, COUNT(ID) AS count 
     FROM {$wpdb->posts} 
     WHERE post_type = %s AND post_status = %s AND post_date >= %s AND post_date <= %s 
     GROUP BY day ORDER BY day ASC",
    'post', 'publish', $start_date->format('Y-m-d H:i:s'), $end_date->format('Y-m-d 23:59:59')
);

$posts_per_day = [];
foreach ($wpdb->get_results($query, ARRAY_A) as $row) {
    $posts_per_day[$row['day']] = (int)$row['count'];
}

// Generación de la rejilla HTML
$heatmap_html = '';
$current_date = clone $start_date;
$weeks_rendered = 0;

while ($current_date <= $end_date) {
    $week_html = '';
    for ($d = 0; $d < 7; $d++) {
        $date_str = $current_date->format('Y-m-d');
        $count    = $posts_per_day[$date_str] ?? 0;
        
        // Lógica de intensidad de color
        $color = match(true) {
            $count === 0  => '#ebedf0',
            $count <= 2   => '#9be9a8',
            $count <= 5   => '#40c463',
            $count <= 10  => '#30a14e',
            default       => '#216e39'
        };

        $tooltip = $count > 0 
            ? "{$count} publicación(es) • {$current_date->format('d/m/Y')}" 
            : "Sin publicaciones • {$current_date->format('d/m/Y')}";
            
        $week_html .= '<div class="heatmap-day" data-toggle="tooltip" data-placement="top" title="' . htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') . '" style="background-color: ' . $color . ';" aria-label="' . htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') . '"></div>';

        if ($current_date >= $end_date) break 2;
        $current_date->modify('+1 day');
    }
    
    // Rellenar celdas vacías de la última semana si es necesario
    for ($i = $d; $i < 7; $i++) {
        $week_html .= '<div class="heatmap-day" style="background-color: #ebedf0;"></div>';
    }
    
    $heatmap_html .= '<div class="heatmap-week">' . $week_html . '</div>';
    $weeks_rendered++;
}

// 4. SALIDA HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Estadísticas de Publicación | <?php echo htmlspecialchars($domain); ?></title>
    
    <!-- Bootstrap 4.6.2 CSS via jsDelivr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <!-- FontAwesome 5.15.4 CSS via jsDelivr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css" integrity="sha384-DyZ88mC6Up2uqS4h/KRgHuoeGwBcD4Ng9SiP4dIRy0EXTlnuz47vAwmeGwVChigm" crossorigin="anonymous">
    
    <style>
        body {
            padding-top: 70px;
            padding-bottom: 60px;
            background-color: #f4f5f7;
        }
        .fixed-header, .fixed-footer {
            position: fixed;
            left: 0;
            right: 0;
            z-index: 1030;
            background-color: #343a40;
            color: #fff;
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .fixed-header { top: 0; height: 60px; }
        .fixed-footer { bottom: 0; height: 50px; }
        .fixed-header .brand, .fixed-footer .info { font-weight: 600; }
        .heatmap-wrapper {
            overflow-x: auto;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .heatmap-container {
            display: flex;
            flex-direction: row;
            gap: 3px;
            min-width: max-content;
        }
        .heatmap-week {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .heatmap-day {
            width: 13px;
            height: 13px;
            border-radius: 2px;
            cursor: pointer;
            transition: transform 0.1s ease;
        }
        .heatmap-day:hover {
            transform: scale(1.3);
            outline: 2px solid #586069;
            z-index: 10;
        }
        .legend {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            font-size: 12px;
            color: #586069;
            margin-top: 12px;
            padding-right: 10px;
        }
        .legend-item {
            display: inline-block;
            width: 13px;
            height: 13px;
            border-radius: 2px;
            margin-left: 3px;
        }
    </style>
</head>
<body>

    <!-- Header Fijo -->
    <header class="fixed-header d-flex align-items-center px-4">
        <div class="brand"><i class="fas fa-brain mr-2"></i> <?php echo htmlspecialchars($ai_model); ?></div>
        <div class="ml-auto"><i class="fas fa-globe mr-1"></i> <?php echo htmlspecialchars($domain); ?></div>
    </header>

    <!-- Cuerpo Principal -->
    <main class="container-fluid mt-4">
        <div class="card border-0">
            <div class="card-header bg-transparent border-bottom-0">
                <h4 class="mb-1"><i class="fas fa-chart-line text-primary mr-2"></i>Actividad de Publicaciones</h4>
                <small class="text-muted">Contribuciones de los últimos 12 meses (solo entradas publicadas)</small>
            </div>
            <div class="card-body">
                <div class="heatmap-wrapper">
                    <div class="heatmap-container">
                        <?php echo $heatmap_html; ?>
                    </div>
                    <div class="legend">
                        <span>Menos</span>
                        <span class="legend-item" style="background:#ebedf0;"></span>
                        <span class="legend-item" style="background:#9be9a8;"></span>
                        <span class="legend-item" style="background:#40c463;"></span>
                        <span class="legend-item" style="background:#30a14e;"></span>
                        <span class="legend-item" style="background:#216e39;"></span>
                        <span>Más</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Fijo -->
    <footer class="fixed-footer d-flex align-items-center px-4">
        <div class="info"><i class="fab fa-php mr-2"></i> PHP <?php echo htmlspecialchars($php_version); ?></div>
        <div class="ml-auto text-muted"><i class="fab fa-wordpress mr-1"></i> WP <?php echo htmlspecialchars($wp_version); ?></div>
    </footer>

    <!-- Scripts (Bootstrap 4.6.2 dependencies via jsDelivr) -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js" integrity="sha384-q5s23b5v1k0z7g7z1w0k0w0k0w0k0w0k0w0k0w0k0w0k0w0k0w0k0w0k0w0k0" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js" integrity="sha384-+sLIOodYLS7CIrQpBjl+C7nPvqq+FbNUBDunl/OZv93DB7Ln/533i8e/mZXLi/P+" crossorigin="anonymous"></script>
    
    <script>
        $(function () {
            $('[data-toggle="tooltip"]').tooltip({ container: 'body', html: false });
        });
    </script>
</body>
</html>
