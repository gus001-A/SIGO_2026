<p align="center">
  <img src="public/img/sigo-logo.png" alt="SIGO" width="160">
</p>

<h1 align="center">SIGO · Sistema Integral de Gestión Organizacional</h1>

<p align="center">
  Plataforma web para sindicatos y organizaciones: padrón de agremiados, red de coordinación, cuotas, apoyos con evidencia, tienda, capacitación y seguimiento GPS, con trazabilidad completa de cada acción.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/Vue-3.5-4FC08D?logo=vuedotjs&logoColor=white" alt="Vue 3.5">
  <img src="https://img.shields.io/badge/Inertia-3-9553E9" alt="Inertia 3">
  <img src="https://img.shields.io/badge/PrimeVue-4-41B883" alt="PrimeVue 4">
  <img src="https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white" alt="MySQL 8">
</p>

---

## Contenido

- [Características](#características)
- [Módulos](#módulos)
- [App del agremiado](#app-del-agremiado)
- [Tecnologías](#tecnologías)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Pruebas](#pruebas)
- [Estructura del proyecto](#estructura-del-proyecto)

## Características

- **Trazabilidad total:** cada registro guarda quién lo creó y quién lo modificó, con fecha y hora. Una bitácora central conserva los valores anteriores y los nuevos de cada cambio.
- **Eliminación lógica:** nada se borra físicamente; los registros dados de baja se pueden consultar y reactivar.
- **Permisos por perfil:** cada perfil tiene un nivel por módulo (sin acceso, ver, editar o total) que se aplica en el servidor y en la interfaz.
- **Interfaz de pantalla fija:** la página no se desplaza; solo lo hace la tabla, con un máximo de 10 registros por página y paginación.
- **Filtros al pie de cada tabla:** un filtro por columna, de texto, de lista o de fecha.
- **Formularios guiados:**
  - ventanas centradas a tres columnas, con ayuda en cada campo;
  - validación de CURP, clave de elector y fechas;
  - vista previa de fotos y documentos antes de subirlos;
  - domicilio que se completa a partir del código postal (catálogo SEPOMEX).
- **Panel con indicadores:** KPIs, alertas de atención y gráficas de cuotas, apoyos, ventas y crecimiento del padrón.

## Módulos

| Módulo | Descripción |
| --- | --- |
| **Panel** | Indicadores generales, alertas de adeudos e inventario bajo, y gráficas. |
| **Agremiados** | Padrón con expediente digital: fotos, INE, datos de contacto y emergencia. Incluye incidencias, bajas con motivo, reactivación y cuenta de acceso a la app. |
| **Coordinación** | Árbol de la red: quién coordina a quién, con reasignación de integrantes. |
| **Cuotas** | Matriz de los últimos seis meses por agremiado, cobro de varios meses a la vez, anulación de pagos y configuración del monto. |
| **Apoyos** | Registro de despensas, apoyos económicos y otros, con evidencia fotográfica obligatoria. |
| **Tienda** | Productos y servicios, inventario con entradas y salidas, pedidos con descuento automático de existencias, y proveedores. |
| **Capacitación** | Cursos asignados a todos los agremiados o a la red de un coordinador, con el avance de cada inscrito. |
| **GPS** | Actividades en campo con mapa en tiempo real (OpenStreetMap) que muestra la ubicación de los participantes. |
| **Bitácora** | Historial de todas las acciones del sistema, con paginación y filtros en el servidor. |
| **Perfiles y usuarios** | Perfiles con permisos por módulo y administración de usuarios. |

## App del agremiado

Los usuarios con perfil de agremiado entran a una aplicación web optimizada para celular (`/app`) donde pueden:

- consultar su credencial digital;
- revisar el estado de sus cuotas;
- comprar en la tienda de la organización;
- dar seguimiento a sus cursos;
- autorizar y compartir su ubicación mientras participan en una actividad.

## Tecnologías

**Backend**
- Laravel 13 (PHP 8.3+)
- MySQL 8 (motor InnoDB)
- Inertia.js 3 (inertia-laravel)

**Frontend**
- Vue 3 con TypeScript
- PrimeVue 4 con tema propio
- Tailwind CSS 4
- Chart.js para gráficas
- Leaflet con OpenStreetMap para mapas
- Lucide para íconos
- Vite 8

## Requisitos

- PHP 8.3 o superior, con las extensiones `pdo_mysql`, `mbstring`, `fileinfo` y `gd`
- Composer 2
- Node.js 20 o superior, con npm
- MySQL 8

## Instalación

```bash
# 1. Clonar el repositorio
git clone <url-del-repositorio> sigo
cd sigo

# 2. Instalar dependencias
composer install
npm install

# 3. Configurar el entorno
cp .env.example .env
php artisan key:generate
```

Edita `.env` con los datos de tu base de datos:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigo
DB_USERNAME=root
DB_PASSWORD=
```

```bash
# 4. Crear tablas y cargar datos iniciales (perfiles, catálogos y datos de ejemplo)
php artisan migrate --seed

# 5. Enlazar el almacenamiento público para fotos y evidencias
php artisan storage:link

# 6. Compilar el frontend
npm run build

# 7. Levantar el servidor
php artisan serve
```

Para desarrollar con recarga en caliente, ejecuta `npm run dev` en otra terminal.

> **Códigos postales:** el seeder copia el catálogo SEPOMEX desde una base `sireh` del mismo servidor, si existe. Si no está disponible, la tabla `codigos_postales` queda vacía y se puede llenar con el catálogo oficial de Correos de México.

> **Subcarpeta:** el sistema puede publicarse en una subcarpeta del servidor (por ejemplo, `https://dominio/sigo`). El frontend detecta la ruta base automáticamente y la antepone a enlaces, peticiones e imágenes.

## Pruebas

Las pruebas de funcionalidad corren sobre SQLite en memoria y nunca tocan la base de MySQL:

```bash
php artisan test
```

Cubren los flujos principales:

- carga de todas las pantallas;
- alta de agremiados con trazabilidad y validaciones;
- bajas lógicas;
- pago de cuotas;
- apoyos con evidencia;
- pedidos e inventario;
- permisos por perfil.

## Estructura del proyecto

```
app/
├── Http/Controllers/   Controladores por módulo
├── Http/Middleware/    Permisos por módulo y cuenta activa
├── Models/             Modelos y traits de trazabilidad (Auditable, Trazable, Registro)
└── Support/            Catálogo de módulos y permisos, cálculo de cuotas
database/
├── migrations/         Esquema completo
└── seeders/            Perfiles, catálogos y datos de ejemplo
resources/js/
├── Components/         TablaSigo, Modal, Campo, CampoFecha, SubirArchivos, TrazaBoton…
├── Layouts/            Layout principal con navegación por módulos
└── Pages/              Una página por módulo, más el login y la app del agremiado
tests/Feature/          Pruebas de los flujos principales
```

---

<p align="center">© 2026 SIGO GAVE</p>
