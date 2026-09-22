<?php
/**
 * Página principal - Lee directorios (materias) y archivos .html (ejercicios)
 * Autoadaptable: úsalo en la raíz, en materias o en temas.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Utilidades ---

function listarDirectorios(string $dir): array {
    $items = [];
    $handle = opendir($dir);
    if ($handle === false) return $items;
    while (($entry = readdir($handle)) !== false) {
        if ($entry === '.' || $entry === '..') continue;
        if ($entry[0] === '.') continue; // ocultos
        $full = $dir . DIRECTORY_SEPARATOR . $entry;
        if (is_dir($full)) {
            $items[] = $entry;
        }
    }
    closedir($handle);
    natcasesort($items);
    return array_values($items);
}

function listarHtml(string $dir): array {
    $items = [];
    $handle = opendir($dir);
    if ($handle === false) return $items;
    while (($entry = readdir($handle)) !== false) {
        if ($entry === '.' || $entry === '..') continue;
        if ($entry[0] === '.') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $entry;
        if (is_file($full) && preg_match('/\.html?$/i', $entry)) {
            $items[] = $entry;
        }
    }
    closedir($handle);
    natcasesort($items);
    return array_values($items);
}

/**
 * Obtiene el "nombre humano" del enlace desde el propio HTML:
 * 1) <title> del documento
 * 2) primer <h1>
 * 3) fallback: nombre del archivo humanizado
 */
function nombreHumanoDesdeHtml(string $rutaArchivo, string $nombreArchivo): string {
    $html = @file_get_contents($rutaArchivo);
    if ($html !== false) {
        // <title>
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            $t = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8'));
            if ($t !== '') {
                return $t;
            }
        }
        // <h1>
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $m)) {
            $t = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8'));
            if ($t !== '') {
                return $t;
            }
        }
    }
    return humanizarNombreArchivo($nombreArchivo);
}

function humanizarNombreArchivo(string $nombre): string {
    $base = pathinfo($nombre, PATHINFO_FILENAME);
    $base = preg_replace('/^\d+[\.\-\_]\s*/u', '', $base); // quita "1. " al inicio
    $base = str_replace(['_', '-'], ' ', $base);
    $base = preg_replace('/\s+/u', ' ', $base);
    $base = trim($base);
    // Capitaliza primera letra de cada palabra
    $base = mb_convert_case($base, MB_CASE_TITLE, 'UTF-8');
    return $base !== '' ? $base : $nombre;
}

function humanizarDirectorio(string $nombre): string {
    $n = str_replace(['_', '-'], ' ', $nombre);
    $n = preg_replace('/\s+/u', ' ', $n);
    $n = trim($n);
    return mb_convert_case($n, MB_CASE_TITLE, 'UTF-8');
}

function escape(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function urlEncodePath(string $s): string {
    return rawurlencode($s);
}

// --- Determinar enlaces de navegación ---
// Si estamos en la raíz del proyecto, no hay "subir".
// Si estamos dentro, el padre es "../"

$dirActual = __DIR__;
$esRaiz = !file_exists(dirname($dirActual) . DIRECTORY_SEPARATOR . 'ESTADISTICA')
    && count(listarDirectorios($dirActual)) >= 0; // la raíz tiene materias

// Detectamos si esta carpeta está en la raíz del proyecto:
// La raíz es donde está este mismo archivo index.php junto a las materias.
// Regla práctica: si hay un index.php en el padre Y este index.php es copia,
// consideramos que hay nivel superior. Mejor: buscar un archivo marcador.
// Más simple: asumir raíz si el path relativo no tiene carpetas "materia" conocidas.
// Usamos una convención: la raíz del proyecto es donde existe .home-work-root o
// donde no hay carpeta padre con index.php "idéntico". Simplificamos:

// Nivel: contamos profundidad relativa a la raíz del sitio web.
// Si el script está en /index.php => raíz. Si en /ESTADISTICA/index.php => nivel 1.

$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
// Normalizar
$scriptPath = str_replace('\\', '/', $scriptPath);
$carpetasScript = explode('/', trim(dirname($scriptPath), '/'));
$profundidad = count(array_filter($carpetasScript, fn($c) => $c !== ''));

$hayPadre = $profundidad > 0;

// --- Recolectar contenido ---
$directorios = listarDirectorios($dirActual);
$archivosHtml = listarHtml($dirActual);

// Si estamos en la raíz y hay un solo directorio tipo materia, es materia.
// Si hay directorios => son "temas/materias"
// Si hay html => son ejercicios

$tituloPagina = '🏠 Mis Tareas';
$mensajeNivel = 'Elige una materia para comenzar';

if ($profundidad === 0) {
    $tituloPagina = '🏫 Mis Tareas';
    $mensajeNivel = $directorios ? 'Elige una materia para comenzar' : 'Aún no hay materias';
} elseif ($directorios && !$archivosHtml) {
    $tituloPagina = '📚 Temas';
    $mensajeNivel = 'Elige un tema para practicar';
} elseif ($archivosHtml && !$directorios) {
    $tituloPagina = '📝 Ejercicios';
    $mensajeNivel = '¡Elige un ejercicio y a divertirte!';
} elseif ($directorios && $archivosHtml) {
    $tituloPagina = '🎒 Actividades';
    $mensajeNivel = 'Elige un tema o ejercicio';
} else {
    $tituloPagina = '📂 Vacío';
    $mensajeNivel = 'Esta carpeta está vacía';
}

// Emoji fijo por tipo
function emojiPara(string $nombre, bool $esDirectorio): string {
    $n = mb_strtolower($nombre, 'UTF-8');
    $mapa = [
        'estadistica' => '📊', 'matematicas' => '➗', 'lengua' => '📖',
        'ingles' => '🇬🇧', 'ciencias' => '🔬', 'historia' => '🏛️',
        'arte' => '🎨', 'musica' => '🎵', 'educacion' => '💪',
        'fisica' => '⚛️', 'quimica' => '🧪', 'biologia' => '🧬',
        'geografia' => '🌍', 'filosofia' => '🧠', 'social' => '🤝',
        'media' => '📈', 'mediana' => '📉', 'detective' => '🕵️',
        'aventura' => '🚀', 'datos' => '💾', 'gimnasio' => '🏋️',
        'agencia' => '🎯', 'decision' => '⚖️',
    ];
    foreach ($mapa as $clave => $emoji) {
        if (mb_strpos($n, $clave) !== false) {
            return $emoji;
        }
    }
    return $esDirectorio ? '📂' : '⭐';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($tituloPagina) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Fredoka', 'Comic Sans MS', cursive, sans-serif; }
        body {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 50%, #d4fc79 100%);
            min-height: 100vh;
        }
        .card {
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 4px solid transparent;
            background-image: linear-gradient(white, white), linear-gradient(135deg, #667eea, #764ba2);
            background-origin: border-box;
            background-clip: padding-box, border-box;
        }
        .card:hover {
            transform: translateY(-8px) scale(1.03) rotate(-1deg);
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.25);
        }
        .card:active {
            transform: translateY(-2px) scale(0.98);
        }
        .card-icon {
            animation: bounce 2s ease infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        .title-gradient {
            background: linear-gradient(90deg, #f093fb, #f5576c, #4facfe, #00f2fe);
            background-size: 300% auto;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shine 4s linear infinite;
        }
        @keyframes shine {
            to { background-position: 300% center; }
        }
        .float-emoji {
            position: fixed;
            font-size: 2rem;
            opacity: 0.3;
            animation: floatAround 15s linear infinite;
            pointer-events: none;
            z-index: 0;
        }
        @keyframes floatAround {
            0% { transform: translateY(100vh) rotate(0deg); }
            100% { transform: translateY(-100px) rotate(360deg); }
        }
        .btn-back {
            transition: all 0.2s ease;
        }
        .btn-back:hover {
            transform: translateX(-5px);
        }
    </style>
</head>
<body class="text-gray-800">

<!-- Emojis flotantes de fondo -->
<div class="float-emoji" style="left:5%;">🎈</div>
<div class="float-emoji" style="left:20%; animation-delay:-3s;">⭐</div>
<div class="float-emoji" style="left:50%; animation-delay:-6s;">🚀</div>
<div class="float-emoji" style="left:75%; animation-delay:-9s;">🌈</div>
<div class="float-emoji" style="left:90%; animation-delay:-12s;">🎯</div>

<div class="relative z-10 max-w-5xl mx-auto p-4 md:p-8">

    <!-- Navegación -->
    <?php if ($hayPadre): ?>
    <a href="../" class="btn-back inline-flex items-center gap-2 mb-6 px-5 py-2.5 bg-white/80 backdrop-blur rounded-full shadow-md text-purple-700 font-semibold hover:bg-white">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
    <?php endif; ?>

    <!-- Encabezado -->
    <header class="text-center mb-10">
        <div class="text-7xl mb-3 card-icon inline-block">🎒</div>
        <h1 class="text-4xl md:text-6xl font-bold title-gradient mb-3">
            <?= escape($tituloPagina) ?>
        </h1>
        <p class="text-lg md:text-xl text-purple-800/80 font-medium">
            <?= escape($mensajeNivel) ?>
        </p>

        <?php if ($directorios || $archivosHtml): ?>
        <div class="mt-4 inline-flex gap-3 text-sm">
            <?php if ($directorios): ?>
            <span class="px-4 py-1.5 bg-white/70 rounded-full shadow font-semibold text-indigo-600">
                <i class="fas fa-folder-open mr-1"></i> <?= count($directorios) ?> carpeta<?= count($directorios) !== 1 ? 's' : '' ?>
            </span>
            <?php endif; ?>
            <?php if ($archivosHtml): ?>
            <span class="px-4 py-1.5 bg-white/70 rounded-full shadow font-semibold text-pink-600">
                <i class="fas fa-file-code mr-1"></i> <?= count($archivosHtml) ?> ejercicio<?= count($archivosHtml) !== 1 ? 's' : '' ?>
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </header>

    <!-- Lista de directorios (materias / temas) -->
    <?php if ($directorios): ?>
    <section class="mb-10">
        <h2 class="text-2xl font-bold text-indigo-800 mb-4 flex items-center gap-2">
            <i class="fas fa-book-open text-indigo-500"></i>
            <?= $profundidad === 0 ? 'Materias' : 'Temas' ?>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($directorios as $dir): ?>
            <?php $nombreHumano = humanizarDirectorio($dir); ?>
            <a href="<?= urlEncodePath($dir) ?>/"
               class="card group block bg-white rounded-3xl p-6 shadow-lg text-center">
                <div class="text-5xl mb-3 card-icon"><?= emojiPara($dir, true) ?></div>
                <h3 class="text-xl font-bold text-gray-800 group-hover:text-purple-600 transition-colors">
                    <?= escape($nombreHumano) ?>
                </h3>
                <span class="inline-block mt-3 text-xs font-semibold px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full">
                    Entrar <i class="fas fa-chevron-right ml-1 text-[10px]"></i>
                </span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Lista de archivos HTML (ejercicios) -->
    <?php if ($archivosHtml): ?>
    <section class="mb-10">
        <h2 class="text-2xl font-bold text-pink-800 mb-4 flex items-center gap-2">
            <i class="fas fa-puzzle-piece text-pink-500"></i>
            Ejercicios
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($archivosHtml as $archivo): ?>
            <?php
                $ruta = $dirActual . DIRECTORY_SEPARATOR . $archivo;
                $textoEnlace = nombreHumanoDesdeHtml($ruta, $archivo);
                $emoji = emojiPara($textoEnlace, false);
            ?>
            <a href="<?= urlEncodePath($archivo) ?>"
               class="card group block bg-white rounded-3xl p-6 shadow-lg text-center"
               target="_blank">
                <div class="text-5xl mb-3 card-icon"><?= $emoji ?></div>
                <h3 class="text-lg font-bold text-gray-800 group-hover:text-pink-600 transition-colors leading-snug">
                    <?= escape($textoEnlace) ?>
                </h3>
                <span class="inline-block mt-3 text-xs font-semibold px-3 py-1 bg-pink-100 text-pink-700 rounded-full">
                    Jugar <i class="fas fa-play ml-1 text-[10px]"></i>
                </span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Estado vacío -->
    <?php if (!$directorios && !$archivosHtml): ?>
    <div class="text-center py-16">
        <div class="text-8xl mb-4">🗂️</div>
        <p class="text-xl text-gray-600 font-medium">
            Esta carpeta aún no tiene contenido.<br>
            <span class="text-gray-400 text-base">¡Pídele a papá o mamá que agregue tareas!</span>
        </p>
    </div>
    <?php endif; ?>

    <!-- Pie -->
    <footer class="text-center mt-12 pb-6 text-purple-700/60 text-sm font-medium">
        Hecho con 💖 para aprender jugando
    </footer>
</div>

</body>
</html>
