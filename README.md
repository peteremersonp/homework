# 🏫 Mis Tareas

Página web para subir tareas y ejercicios para los hijos, organizados por **materias → temas → ejercicios**.

## 📁 Estructura

```
home-work/
├── index.php                  ← Raíz: lista las materias
├── ESTADISTICA/               ← Materia
│   ├── index.php              ← Lista los temas
│   └── Media y Mediana/       ← Tema
│       ├── index.php          ← Lista los ejercicios .html
│       ├── 1. aventura_estad_stica.html
│       ├── 2. detectives_de_datos.html
│       └── ...
└── README.md
```

## 🚀 Cómo usar

1. Servir la carpeta con PHP:
   ```bash
   php -S localhost:8000
   ```
2. Abrir `http://localhost:8000` en el navegador.
3. Hacer clic en una materia → tema → ejercicio.

## ➕ Cómo agregar contenido

**Nueva materia:**
```bash
mkdir NOMBRE_MATERIA
cp index.php NOMBRE_MATERIA/
```

**Nuevo tema dentro de una materia:**
```bash
mkdir "ESTADISTICA/Nuevo Tema"
cp index.php "ESTADISTICA/Nuevo Tema/"
```

**Nuevo ejercicio:** solo colocar un archivo `.html` dentro del tema. El `index.php` lo listará automáticamente usando el `<title>` del HTML como nombre del enlace (no el nombre del archivo).

## ✨ Características

- Estilo amigable para niños (colores, emojis, animaciones)
- Lee directorios como materias/temas automáticamente
- Lee archivos `.html` y humaniza los nombres usando su `<title>`
- Botón "Volver" para navegar hacia atrás
- Autoadaptable: el mismo `index.php` funciona en cualquier nivel
