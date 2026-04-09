<?php
/**
 * WordPress Post Heatmap
 * Modelo: [INSERTAR MODELO AQUÍ]
 * Descripción: Mapa de calor de publicaciones al estilo GitHub para WordPress.
 * Instrucciones: Coloca este archivo en la raíz de WordPress o en una carpeta del tema.
 *                Accede directamente vía URL. No requiere permisos de administrador.
 */
/**
 * Script PHP para Mostrar Heatmap de wordpress
 * Versión: 1.0
 * Modelo: Claude Sonnet 4.6
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
// ─── Cargar entorno de WordPress ─────────────────────────────────────────────
$wp_load_paths = [
    dirname(__FILE__) . '/wp-load.php',                    // Raíz del sitio
    dirname(__FILE__) . '/../../wp-load.php',              // Desde carpeta de tema (2 niveles)
    dirname(__FILE__) . '/../../../wp-load.php',           // Desde subcarpeta de tema (3 niveles)
    dirname(__FILE__) . '/../../../../wp-load.php',        // 4 niveles
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('<h2 style="font-family:monospace;color:red;padding:2rem;">ERROR: No se pudo cargar wp-load.php. Asegúrate de colocar este archivo en la raíz de WordPress o ajusta la ruta manualmente.</h2>');
}

// ─── Variables Globales ───────────────────────────────────────────────────────
global $wpdb;

$modelo_ia    = '[INSERTAR MODELO AQUÍ]';
$dominio      = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field($_SERVER['HTTP_HOST']) : 'desconocido';
$php_version  = PHP_VERSION;
$wp_version   = get_bloginfo('version');
$site_name    = get_bloginfo('name');

// ─── Lógica: Obtener posts de los últimos 12 meses ───────────────────────────
$fecha_fin    = date('Y-m-d');
$fecha_inicio = date('Y-m-d', strtotime('-12 months'));

$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT DATE(post_date) as fecha, COUNT(*) as total
         FROM {$wpdb->posts}
         WHERE post_status = 'publish'
           AND post_type = 'post'
           AND DATE(post_date) >= %s
           AND DATE(post_date) <= %s
         GROUP BY DATE(post_date)
         ORDER BY fecha ASC",
        $fecha_inicio,
        $fecha_fin
    ),
    ARRAY_A
);

// Convertir a mapa fecha => total
$posts_por_dia = [];
foreach ($results as $row) {
    $posts_por_dia[$row['fecha']] = (int) $row['total'];
}

$max_posts = $posts_por_dia ? max($posts_por_dia) : 1;

// ─── Construir grid de días (semanas x días) ──────────────────────────────────
// Empezamos desde hace 12 meses, ajustando al domingo anterior
$start_ts  = strtotime($fecha_inicio);
$start_dow = (int) date('w', $start_ts); // 0=Dom ... 6=Sáb
$grid_start = strtotime("-{$start_dow} days", $start_ts);

$end_ts    = strtotime($fecha_fin);
$end_dow   = (int) date('w', $end_ts);
$grid_end  = strtotime("+" . (6 - $end_dow) . " days", $end_ts);

// Construir semanas
$weeks = [];
$cur   = $grid_start;
while ($cur <= $grid_end) {
    $week = [];
    for ($d = 0; $d < 7; $d++) {
        $week[] = $cur;
        $cur    = strtotime('+1 day', $cur);
    }
    $weeks[] = $week;
}

// Etiquetas de meses para el encabezado del heatmap
$month_labels = [];
foreach ($weeks as $wi => $week) {
    $first_day = $week[0];
    $month_key = date('Y-m', $first_day);
    if (!isset($month_labels[$month_key])) {
        $month_labels[$month_key] = ['week_index' => $wi, 'label' => month_abbr($first_day)];
    }
}

function month_abbr(int $ts): string {
    $meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    return $meses[(int)date('n', $ts) - 1];
}

// Función para calcular el color según intensidad
function heatmap_color(int $count, int $max): string {
    if ($count === 0) return '#161b22'; // Sin actividad
    $ratio = $count / $max;
    if ($ratio <= 0.25) return '#0e4429';
    if ($ratio <= 0.50) return '#006d32';
    if ($ratio <= 0.75) return '#26a641';
    return '#39d353';
}

// Estadísticas del período
$total_posts_periodo = array_sum($posts_por_dia);
$dias_activos        = count($posts_por_dia);
$promedio_dia        = $dias_activos > 0 ? round($total_posts_periodo / $dias_activos, 1) : 0;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heatmap de Posts – <?php echo esc_html($site_name); ?></title>

    <!-- Bootstrap 4.6.2 via jsDelivr -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
          integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N"
          crossorigin="anonymous">

    <!-- FontAwesome 5.15.4 via jsDelivr -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css"
          integrity="sha256-mUZM63G8m73Mcidfrv5E+Y61y7a12O5mW4ezU3bxqW4="
          crossorigin="anonymous">

    <style>
        /* ── Reset & Base ── */
        *, *::before, *::after { box-sizing: border-box; }

        body {
            background-color: #0d1117;
            color: #c9d1d9;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            padding-top: 56px;
            padding-bottom: 48px;
        }

        /* ── Header Fijo ── */
        .site-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 56px;
            background: #161b22;
            border-bottom: 1px solid #30363d;
            z-index: 1000;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            gap: 1rem;
        }
        .site-header .brand {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-size: .85rem;
            font-weight: 600;
        }
        .badge-modelo {
            background: linear-gradient(135deg, #238636, #2ea043);
            color: #fff;
            padding: .2rem .55rem;
            border-radius: 12px;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .02em;
        }
        .site-header .domain-info {
            margin-left: auto;
            font-size: .8rem;
            color: #8b949e;
        }
        .site-header .domain-info i { margin-right: .3rem; }

        /* ── Footer Fijo ── */
        .site-footer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 36px;
            background: #161b22;
            border-top: 1px solid #30363d;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2.5rem;
            font-size: .75rem;
            color: #8b949e;
            z-index: 1000;
        }
        .site-footer .footer-item i {
            margin-right: .3rem;
            color: #3fb950;
        }

        /* ── Main Wrapper ── */
        .main-wrap {
            min-height: calc(100vh - 104px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        /* ── Card ── */
        .heatmap-card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 12px;
            padding: 1.75rem 2rem;
            width: 100%;
            max-width: 920px;
            box-shadow: 0 8px 40px rgba(0,0,0,.5);
        }
        .card-title-text {
            font-size: 1rem;
            font-weight: 700;
            color: #e6edf3;
            margin: 0;
        }
        .card-subtitle-text {
            font-size: .78rem;
            color: #8b949e;
            margin-top: .25rem;
            margin-bottom: 0;
        }

        /* ── Stats Strip ── */
        .stats-strip {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            margin: 1.25rem 0;
        }
        .stat-item .stat-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: #3fb950;
            line-height: 1;
            display: block;
        }
        .stat-item .stat-label {
            font-size: .68rem;
            color: #8b949e;
            text-transform: uppercase;
            letter-spacing: .06em;
            display: block;
            margin-top: .2rem;
        }

        /* ── Divider ── */
        .hm-divider {
            border-color: #30363d;
            margin: 1.25rem 0;
        }

        /* ── Heatmap Outer (scroll horizontal en móvil) ── */
        .heatmap-outer {
            overflow-x: auto;
            padding-bottom: .5rem;
        }

        /* ── Heatmap Layout ── */
        .heatmap-wrapper {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: max-content;
        }

        /* Fila de etiquetas de meses */
        .month-row {
            display: flex;
            gap: 3px;
            margin-bottom: 2px;
        }
        .dow-spacer { width: 30px; flex-shrink: 0; }
        .month-cell {
            width: 14px;
            font-size: .62rem;
            color: #8b949e;
            user-select: none;
        }

        /* Grid principal */
        .hm-grid {
            display: flex;
            gap: 3px;
        }

        /* Columna días de semana */
        .dow-col {
            display: flex;
            flex-direction: column;
            gap: 3px;
            width: 30px;
            flex-shrink: 0;
        }
        .dow-lbl {
            height: 14px;
            font-size: .62rem;
            color: #8b949e;
            display: flex;
            align-items: center;
            user-select: none;
        }

        /* Columna de semana */
        .week-col {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        /* Celda de día */
        .day-cell {
            width: 14px;
            height: 14px;
            border-radius: 2px;
            cursor: default;
            position: relative;
            transition: transform .1s ease, outline .1s ease;
        }
        .day-cell.in-range:hover {
            transform: scale(1.5);
            outline: 1.5px solid #58a6ff;
            z-index: 20;
        }
        .day-cell.out-of-range {
            background: #0d1117 !important;
        }

        /* Tooltip CSS puro */
        .day-cell.in-range[data-tip]:hover::after {
            content: attr(data-tip);
            position: absolute;
            bottom: calc(100% + 7px);
            left: 50%;
            transform: translateX(-50%);
            background: #1c2128;
            color: #e6edf3;
            font-size: .68rem;
            padding: .25rem .55rem;
            border-radius: 6px;
            white-space: nowrap;
            pointer-events: none;
            box-shadow: 0 3px 12px rgba(0,0,0,.6);
            border: 1px solid #30363d;
            z-index: 999;
        }

        /* ── Leyenda ── */
        .legend {
            display: flex;
            align-items: center;
            gap: .45rem;
            margin-top: 1.1rem;
            font-size: .72rem;
            color: #8b949e;
        }
        .leg-cell {
            width: 14px;
            height: 14px;
            border-radius: 2px;
            flex-shrink: 0;
        }

        /* ── Responsive ── */
        @media (max-width: 576px) {
            .heatmap-card { padding: 1.25rem 1rem; }
            .stats-strip  { gap: 1.25rem; }
        }
    </style>
</head>
<body>

<!-- ══════════════════════════════════════════
     HEADER FIJO
══════════════════════════════════════════ -->
<header class="site-header">
    <div class="brand">
        <i class="fas fa-fire-alt" style="color:#3fb950;"></i>
        Post Heatmap
        <span class="badge-modelo">
            <i class="fas fa-robot" style="font-size:.65rem;margin-right:.25rem;"></i>
            <?php echo esc_html($modelo_ia); ?>
        </span>
    </div>
    <div class="domain-info">
        <i class="fas fa-globe-americas"></i>
        <?php echo esc_html($dominio); ?>
    </div>
</header>


<!-- ══════════════════════════════════════════
     CONTENIDO PRINCIPAL
══════════════════════════════════════════ -->
<main class="main-wrap">
    <div class="heatmap-card">

        <!-- Encabezado de tarjeta -->
        <div class="d-flex align-items-start justify-content-between flex-wrap" style="gap:.75rem;">
            <div>
                <p class="card-title-text">
                    <i class="fas fa-chart-bar" style="color:#3fb950;margin-right:.4rem;"></i>
                    Actividad de Publicaciones
                </p>
                <p class="card-subtitle-text">
                    Últimos 12 meses &nbsp;·&nbsp; Solo <strong style="color:#c9d1d9;">posts</strong> publicados
                    &nbsp;·&nbsp;
                    <?php echo esc_html(date('d M Y', strtotime($fecha_inicio))); ?>
                    &nbsp;→&nbsp;
                    <?php echo esc_html(date('d M Y', strtotime($fecha_fin))); ?>
                </p>
            </div>
            <span class="badge badge-secondary"
                  style="background:#238636;font-size:.7rem;padding:.35rem .65rem;border-radius:6px;line-height:1.4;">
                <i class="fas fa-globe" style="margin-right:.3rem;"></i>
                <?php echo esc_html($site_name); ?>
            </span>
        </div>

        <!-- Estadísticas -->
        <div class="stats-strip">
            <div class="stat-item">
                <span class="stat-value"><?php echo number_format($total_posts_periodo); ?></span>
                <span class="stat-label">Posts totales</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo number_format($dias_activos); ?></span>
                <span class="stat-label">Días activos</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo number_format($promedio_dia, 1); ?></span>
                <span class="stat-label">Promedio / día activo</span>
            </div>
            <div class="stat-item">
                <span class="stat-value"><?php echo number_format($max_posts); ?></span>
                <span class="stat-label">Máx. en un día</span>
            </div>
        </div>

        <hr class="hm-divider">

        <!-- ──────────────── HEATMAP ──────────────── -->
        <div class="heatmap-outer">
            <div class="heatmap-wrapper">

                <!-- Fila de meses -->
                <div class="month-row">
                    <div class="dow-spacer"></div>
                    <?php foreach ($weeks as $wi => $week):
                        $mk = date('Y-m', $week[0]);
                        $show = isset($month_labels[$mk]) && $month_labels[$mk]['week_index'] === $wi;
                    ?>
                        <div class="month-cell">
                            <?php echo $show ? esc_html($month_labels[$mk]['label']) : ''; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Grid principal -->
                <div class="hm-grid">

                    <!-- Etiquetas días de semana -->
                    <div class="dow-col">
                        <?php
                        $dow_labels = ['Dom', '', 'Mar', '', 'Jue', '', 'Sáb'];
                        foreach ($dow_labels as $dl) {
                            echo '<div class="dow-lbl">' . esc_html($dl) . '</div>';
                        }
                        ?>
                    </div>

                    <!-- Semanas -->
                    <?php foreach ($weeks as $week): ?>
                    <div class="week-col">
                        <?php foreach ($week as $ts_day):
                            $date_str = date('Y-m-d', $ts_day);
                            $count    = $posts_por_dia[$date_str] ?? 0;
                            $in_range = ($ts_day >= strtotime($fecha_inicio) && $ts_day <= strtotime($fecha_fin));
                            $color    = $in_range ? heatmap_color($count, $max_posts) : '#0d1117';
                            $cls      = $in_range ? 'in-range' : 'out-of-range';
                            $fecha_fmt = date('d M Y', $ts_day);

                            if ($in_range) {
                                $tip = $count > 0
                                    ? "{$count} post" . ($count !== 1 ? 's' : '') . " – {$fecha_fmt}"
                                    : "Sin posts – {$fecha_fmt}";
                            } else {
                                $tip = '';
                            }
                        ?>
                            <div class="day-cell <?php echo $cls; ?>"
                                 style="background:<?php echo $color; ?>;"
                                 <?php if ($tip): ?>data-tip="<?php echo esc_attr($tip); ?>"<?php endif; ?>>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>

                </div><!-- /.hm-grid -->
            </div><!-- /.heatmap-wrapper -->
        </div><!-- /.heatmap-outer -->

        <!-- Leyenda -->
        <div class="legend">
            <span>Menos</span>
            <div class="leg-cell" style="background:#161b22;border:1px solid #30363d;"></div>
            <div class="leg-cell" style="background:#0e4429;"></div>
            <div class="leg-cell" style="background:#006d32;"></div>
            <div class="leg-cell" style="background:#26a641;"></div>
            <div class="leg-cell" style="background:#39d353;"></div>
            <span>Más</span>
        </div>

    </div><!-- /.heatmap-card -->
</main>


<!-- ══════════════════════════════════════════
     FOOTER FIJO
══════════════════════════════════════════ -->
<footer class="site-footer">
    <span class="footer-item">
        <i class="fab fa-php"></i>
        PHP <?php echo esc_html($php_version); ?>
    </span>
    <span class="footer-item">
        <i class="fab fa-wordpress"></i>
        WordPress <?php echo esc_html($wp_version); ?>
    </span>
    <span class="footer-item">
        <i class="fas fa-server"></i>
        <?php echo esc_html($dominio); ?>
    </span>
</footer>


<!-- jQuery 3.5.1 slim + Bootstrap 4.6.2 bundle via jsDelivr -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"
        integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct"
        crossorigin="anonymous"></script>

</body>
</html>
