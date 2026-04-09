<?php
/**
 * Heatmap de Posts Publicados en WordPress
 * Estilo Procedural, PHP 8.x
 * Bootstrap 4.6.2 y FontAwesome 5.15.4 vía jsDelivr
 * Archivo independiente para colocar en raíz o carpeta del tema
 */
/**
* NO FUNCIONA. 
* Script PHP para Mostrar Heatmap de wordpress
 * Versión: 1.0
 * Modelo: Copilot
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


global $wpdb;

// Detectar dominio actual
$domain = $_SERVER['HTTP_HOST'];

// Detectar versión de PHP
$php_version = phpversion();

// Detectar versión de WordPress si existe
$wp_version = function_exists('get_bloginfo') ? get_bloginfo('version') : 'No detectada';

// Obtener posts publicados últimos 12 meses
$start_date = date('Y-m-d', strtotime('-12 months'));
$query = "
    SELECT DATE(post_date) as fecha, COUNT(ID) as cantidad
    FROM {$wpdb->posts}
    WHERE post_type = 'post'
      AND post_status = 'publish'
      AND post_date >= %s
    GROUP BY DATE(post_date)
";
$results = $wpdb->get_results($wpdb->prepare($query, $start_date), ARRAY_A);

// Organizar datos en array [fecha => cantidad]
$data = [];
foreach ($results as $row) {
    $data[$row['fecha']] = $row['cantidad'];
}

// Generar rango de días últimos 12 meses
$days = [];
$today = new DateTime();
$interval = new DateInterval('P1D');
$period = new DatePeriod(new DateTime($start_date), $interval, $today->add($interval));
foreach ($period as $date) {
    $fecha = $date->format('Y-m-d');
    $days[$fecha] = $data[$fecha] ?? 0;
}

// Calcular máximo para intensidad
$max_posts = max($days);

// Función para color según cantidad
function getColor($count, $max) {
    if ($count == 0) return '#ebedf0';
    $intensity = $count / $max;
    if ($intensity < 0.25) return '#c6e48b';
    if ($intensity < 0.5) return '#7bc96f';
    if ($intensity < 0.75) return '#239a3b';
    return '#196127';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mapa de Calor de Posts</title>
    <!-- Bootstrap 4.6.2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- FontAwesome 5.15.4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <style>
        body { padding-top: 70px; padding-bottom: 70px; }
        .heatmap { display: grid; grid-template-columns: repeat(53, 12px); grid-gap: 2px; justify-content: center; }
        .day { width: 12px; height: 12px; }
        header, footer { position: fixed; left: 0; right: 0; background: #343a40; color: #fff; padding: 10px; z-index: 1000; }
        header { top: 0; }
        footer { bottom: 0; }
    </style>
</head>
<body>
    <header class="text-center">
        <strong>[INSERTAR MODELO AQUÍ]</strong> | Dominio: <?php echo htmlspecialchars($domain); ?>
    </header>

    <div class="container text-center">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-fire"></i> Mapa de Calor de Posts Publicados (Últimos 12 meses)
            </div>
            <div class="card-body">
                <div class="heatmap">
                    <?php foreach ($days as $fecha => $count): ?>
                        <div class="day" title="<?php echo $fecha . ': ' . $count . ' posts'; ?>"
                             style="background-color: <?php echo getColor($count, $max_posts); ?>"></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center">
        PHP <?php echo $php_version; ?> | WordPress <?php echo $wp_version; ?>
    </footer>

    <!-- JS Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
