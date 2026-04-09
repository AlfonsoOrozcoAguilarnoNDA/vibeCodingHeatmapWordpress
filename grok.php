<?php
/**
 * Archivo independiente: heatmap-posts.php
 * Colócalo en la raíz de WordPress o en la carpeta de tu tema.
 * Accede vía: https://tudominio.com/heatmap-posts.php
 * 
 * Generado como script único procedural PHP 8+ + WordPress + Bootstrap 4.6.2 + FontAwesome 5.15.4
 */
/**
 * Script PHP para Mostrar Heatmap de wordpress
 * Versión: 1.0
 * Modelo: Grok
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


if (!defined('ABSPATH')) {
    $wp_load = dirname(__FILE__) . '/wp-load.php';
    if (file_exists($wp_load)) {
        require_once $wp_load;
    } else {
        die('Error: No se pudo cargar el entorno de WordPress. Coloca este archivo dentro de la instalación de WP.');
    }
}

global $wpdb;

// =============================================
// 1. OBTENER DATOS: Posts publicados últimos 12 meses
// =============================================
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT DATE(post_date) AS post_date, COUNT(ID) AS qty 
     FROM {$wpdb->posts} 
     WHERE post_type = %s 
       AND post_status = %s 
       AND post_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
     GROUP BY DATE(post_date) 
     ORDER BY post_date ASC",
    'post',
    'publish'
), ARRAY_A);

$data = [];
foreach ($results as $row) {
    $data[$row['post_date']] = (int)$row['qty'];
}

// =============================================
// 2. CONSTRUIR GRID (últimos 12 meses + alineación por semana)
// =============================================
$endDate   = new DateTime('today');
$startDate = (clone $endDate)->modify('-12 months');

// Alinear al primer domingo de la semana (GitHub style)
$weekday   = (int)$startDate->format('w'); // 0 = Domingo
$gridStart = (clone $startDate)->modify("-{$weekday} days");

// Generar todos los días (incluye padding inicial con qty=0)
$days = [];
$current = clone $gridStart;
while ($current <= $endDate) {
    $dateStr = $current->format('Y-m-d');
    $qty     = $data[$dateStr] ?? 0;

    $days[] = [
        'date' => $dateStr,
        'qty'  => $qty
    ];

    $current->modify('+1 day');
}

// Máximo para escala de color
$maxQty = 0;
foreach ($days as $d) {
    if ($d['qty'] > $maxQty) $maxQty = $d['qty'];
}
if ($maxQty === 0) $maxQty = 1;

// Función de color (estilo GitHub contributions)
function getHeatmapColor(int $qty, int $max): string {
    if ($qty === 0) return '#ebedf0';
    $level = min(4, (int)ceil(($qty / $max) * 4));
    return match ($level) {
        1 => '#9be9a8',
        2 => '#40c463',
        3 => '#30a14e',
        default => '#216e39',
    };
}

// Total de posts (solo informativo)
$totalPosts = array_sum(array_column($results, 'qty'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Calor • Posts publicados</title>

    <!-- Bootstrap 4.6.2 via jsDelivr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9u1jH+6i0n7f4W8Q0wZ5jK4r5d" crossorigin="anonymous">

    <!-- FontAwesome 5.15.4 via jsDelivr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css" integrity="sha384-oqQ7p3p5L5p5L5p5L5p5L5p5L5p5L5p5L5p5L5p5L5p5L5p5L5p5L5p5L5p5" crossorigin="anonymous">

    <style>
        :root {
            --square-size: 13px;
        }
        body {
            background: #f8f9fa;
            padding-top: 70px;
            padding-bottom: 70px;
        }
        .fixed-header {
            background: #212529;
            color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }
        .fixed-footer {
            background: #212529;
            color: #adb5bd;
            font-size: 0.9rem;
        }
        .heatmap-wrapper {
            max-width: 820px;
            margin: 0 auto;
        }
        .day-labels {
            display: grid;
            grid-template-rows: repeat(7, var(--square-size));
            gap: 3px;
            font-size: 0.75rem;
            line-height: var(--square-size);
            text-align: right;
            padding-right: 8px;
            color: #6c757d;
        }
        .heatmap-grid {
            display: grid;
            grid-template-rows: repeat(7, var(--square-size));
            grid-auto-columns: var(--square-size);
            grid-auto-flow: column;
            gap: 3px;
        }
        .day-square {
            border-radius: 2px;
            transition: transform .1s ease;
        }
        .day-square:hover {
            transform: scale(1.15);
            box-shadow: 0 0 0 2px rgba(0,123,255,.3);
        }
        .legend-square {
            width: 13px;
            height: 13px;
            border-radius: 2px;
            display: inline-block;
        }
        .card {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,.08);
        }
    </style>
</head>
<body>

    <!-- HEADER FIJO -->
    <nav class="navbar fixed-top fixed-header py-2">
        <div class="container">
            <div class="d-flex align-items-center">
                <i class="fas fa-fire fa-fw mr-2"></i>
                <strong>Mapa de Calor de Posts</strong>
            </div>
            <div class="d-flex align-items-center">
                <span class="mr-3 text-white-50 small">Generado por <strong>[INSERTAR MODELO AQUÍ]</strong></span>
                <span class="badge badge-light">
                    <i class="fas fa-globe fa-fw"></i>
                    <?php echo esc_html($_SERVER['HTTP_HOST'] ?? 'dominio.com'); ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">

                <div class="card">
                    <div class="card-body p-4">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title mb-0">
                                <i class="fas fa-chart-bar fa-fw"></i> 
                                Actividad de publicaciones • Últimos 12 meses
                            </h4>
                            <div class="text-muted small">
                                <strong><?php echo number_format($totalPosts); ?></strong> posts publicados
                            </div>
                        </div>

                        <!-- HEATMAP -->
                        <div class="heatmap-wrapper">

                            <!-- Leyenda de meses (aproximada - visual) -->
                            <div class="d-flex justify-content-between text-muted small mb-2 px-5" style="font-size:0.8rem;">
                                <?php
                                // Meses aproximados (12 columnas representativas)
                                $monthNames = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                                $currentMonth = (int)$gridStart->format('n') - 1;
                                for ($i = 0; $i < 12; $i++) {
                                    echo '<span>' . $monthNames[($currentMonth + $i) % 12] . '</span>';
                                }
                                ?>
                            </div>

                            <div class="d-flex">
                                <!-- Etiquetas de días (Domingo a Sábado) -->
                                <div class="day-labels">
                                    <?php
                                    $labels = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
                                    foreach ($labels as $label) {
                                        echo "<div>{$label}</div>";
                                    }
                                    ?>
                                </div>

                                <!-- Grid del heatmap -->
                                <div class="heatmap-grid">
                                    <?php foreach ($days as $day): ?>
                                        <?php
                                        $color = getHeatmapColor($day['qty'], $maxQty);
                                        $title = $day['date'] . ' → ' . $day['qty'] . ' post' . ($day['qty'] !== 1 ? 's' : '');
                                        ?>
                                        <div class="day-square" 
                                             style="background-color: <?php echo $color; ?>;" 
                                             title="<?php echo esc_attr($title); ?>"
                                             data-toggle="tooltip">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Leyenda de intensidad -->
                            <div class="d-flex align-items-center justify-content-end mt-3 small text-muted">
                                <span class="mr-2">Menos</span>
                                <div class="legend-square" style="background:#ebedf0;"></div>
                                <div class="legend-square" style="background:#9be9a8;"></div>
                                <div class="legend-square" style="background:#40c463;"></div>
                                <div class="legend-square" style="background:#30a14e;"></div>
                                <div class="legend-square" style="background:#216e39;"></div>
                                <span class="ml-2">Más</span>
                            </div>
                        </div>

                        <div class="text-center mt-4 text-muted small">
                            Cada cuadrado representa <strong>un día</strong>.<br>
                            Solo se muestran <strong>entradas (post)</strong> publicadas. Páginas y otros tipos de contenido están excluidos.
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- FOOTER FIJO -->
    <footer class="fixed-bottom fixed-footer py-3">
        <div class="container">
            <div class="row">
                <div class="col text-center">
                    PHP <strong><?php echo phpversion(); ?></strong> 
                    • 
                    WordPress <strong><?php echo function_exists('get_bloginfo') ? get_bloginfo('version') : '—'; ?></strong>
                    <span class="mx-3">•</span>
                    Solo posts publicados • Visible para todos los visitantes
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS + jQuery (necesario para tooltips) -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Xc9TBJIT6a8aW6n5p5f6b6c6b6c6b6c6b6c6b6c6b6" crossorigin="anonymous"></script>

    <script>
        // Activar tooltips
        $(function () {
            $('[data-toggle="tooltip"]').tooltip({
                placement: 'top',
                html: true
            });
        });
    </script>

</body>
</html>
