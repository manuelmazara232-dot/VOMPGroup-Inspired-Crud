# CRUD de Solicitudes de Crédito — PHP puro + JSON

CRUD de ejemplo construido en **PHP puro (sin frameworks)**, sin dependencias
externas, que persiste sus datos en un archivo **`storage/solicitudes.json`**
en lugar de una base de datos. El frontend está inspirado visualmente en
[vopm.net](https://vopm.net) (cabecera oscura, acento dorado, tipografía en
mayúsculas con espaciado, tarjetas claras de bordes suaves).

La entidad elegida para este CRUD es **Solicitud de crédito**, en línea con
el negocio de un grupo financiero como VOPM (Panacrédito / CrediGo): un
cliente registra una solicitud con un monto y un plazo, y esa solicitud pasa
por los estados `Pendiente`, `Aprobada` o `Rechazada`.



## 1. Requisitos

- **PHP 8.1 o superior** (el proyecto usa tipado estricto, `match`,
  propiedades `readonly` y argumentos con nombre; se probó en PHP 8.3).
- **Extensión `mbstring`** habilitada. Viene incluida por defecto en casi
  cualquier instalación de PHP (XAMPP, MAMP, la mayoría de distribuciones
  Linux y de hostings compartidos), pero si se usa una instalación mínima
  de PHP (por ejemplo, recién compilada o una imagen Docker "slim") puede
  no estar activada. Se usa únicamente para contar correctamente caracteres
  acentuados (á, é, í, ó, ú, ñ) en el nombre del cliente.
- Permisos de escritura sobre la carpeta `storage/`, para poder crear y
  modificar `solicitudes.json`.
- No se necesita Composer, ni ninguna librería de terceros, ni conexión a
  internet para funcionar: el único archivo CSS y el único archivo
  JavaScript del proyecto son propios, no se descarga ninguna fuente ni
  ningún script externo.



## 2. Cómo ejecutar el proyecto

No hace falta configurar Apache ni Nginx. Basta con el servidor embebido de
PHP, apuntando a la carpeta `public/` como raíz del sitio:

```bash
cd vopm-crud
php -S localhost:8000 -t public
```

Y abrir `http://localhost:8000/index.php` en el navegador.

Si se prefiere usar Apache o Nginx, la configuración del *virtual host* debe
apuntar su `DocumentRoot` (o `root`, en Nginx) a la carpeta `public/`, nunca
a la raíz del proyecto, para que los archivos de `src/`, `views/` y
`storage/` no queden accesibles directamente desde el navegador.

El archivo `storage/solicitudes.json` ya viene con tres solicitudes de
ejemplo (una en cada estado posible) para poder ver el listado funcionando
de inmediato. Se puede vaciar dejándolo como `[]`, o simplemente borrarlo:
la aplicación lo vuelve a crear automáticamente, vacío, la primera vez que
lo necesita.



## 3. Estructura de carpetas

```
vopm-crud/
├── public/                        Raíz pública del sitio (lo único expuesto al navegador)
│   ├── index.php                  Front controller: arranca la app y enruta cada petición
│   └── assets/
│       ├── css/style.css          Único hoja de estilos del proyecto
│       └── js/app.js              Único script: confirmación antes de eliminar
│
├── src/                           Código de la aplicación (no accesible directamente vía navegador)
│   ├── Bootstrap/
│   │   └── Autoloader.php         Autoloader manual (equivalente casero a Composer)
│   │
│   ├── Domain/                    Capa de dominio: reglas y contratos, sin detalles técnicos
│   │   ├── Entity/
│   │   │   └── SolicitudCredito.php
│   │   └── Repository/
│   │       └── SolicitudCreditoRepositoryInterface.php
│   │
│   ├── Application/                Capa de aplicación: casos de uso y validaciones
│   │   ├── Service/
│   │   │   └── SolicitudCreditoService.php
│   │   └── Exception/
│   │       └── ValidationException.php
│   │
│   ├── Infrastructure/             Capa de infraestructura: cómo se guardan los datos
│   │   └── Persistence/Json/
│   │       ├── JsonFileStorage.php
│   │       └── JsonSolicitudCreditoRepository.php
│   │
│   └── Presentation/                Capa de presentación: HTTP, controladores
│       ├── Controller/
│       │   └── SolicitudCreditoController.php
│       └── Http/
│           └── Request.php
│
├── views/                          Plantillas PHP puras (sin motor de plantillas)
│   ├── layout/
│   │   ├── header.php
│   │   └── footer.php
│   └── solicitudes/
│       ├── index.php               Listado
│       └── form.php                Formulario compartido (crear / editar)
│
├── storage/
│   └── solicitudes.json            "Base de datos" del proyecto
│
└── README.md                       Este archivo
```



## 4. Arquitectura: por qué está organizado así

El proyecto sigue una variante simplificada de **arquitectura limpia /
arquitectura por capas**, con una regla central: **las capas internas
(Dominio y Aplicación) no saben nada sobre HTTP, HTML ni archivos JSON**.
Solo la capa externa (Infraestructura y Presentación) conoce esos detalles.

```
Presentation  ──depende de──>  Application  ──depende de──>  Domain
                                                                  ^
Infrastructure ─────────────────implementa────────────────────┘
```

En la práctica, esto se traduce en:

- **`Domain\Entity\SolicitudCredito`** es un objeto de datos simple: no sabe
  leer ni escribir archivos, ni nada sobre HTTP. Solo conoce sus propios
  campos y sabe convertirse hacia/desde un arreglo asociativo.

- **`Domain\Repository\SolicitudCreditoRepositoryInterface`** es un
  **contrato**: define qué operaciones existen (`listarTodas`,
  `buscarPorId`, `crear`, `actualizar`, `eliminar`) sin decir cómo se
  implementan. Es la pieza que permite que el resto del sistema no dependa
  directamente de "JSON".

- **`Application\Service\SolicitudCreditoService`** contiene **todas** las
  reglas de negocio (qué es un nombre válido, qué formato debe tener la
  cédula, en qué rango debe estar el monto, etc.) y depende únicamente de
  la interfaz del repositorio, nunca de `JsonSolicitudCreditoRepository`
  directamente. Si mañana se quisiera cambiar el almacenamiento a MySQL o
  SQLite, este archivo no tendría que cambiar ni una línea: solo habría
  que escribir una nueva clase que implemente la misma interfaz.

- **`Infrastructure\Persistence\Json\*`** es la única parte del sistema que
  sabe que los datos viven en un archivo `.json`. Se dividió en dos clases:
  - `JsonFileStorage`: bajo nivel, genérico, no sabe qué es una
    "solicitud de crédito"; solo sabe leer y escribir arreglos PHP como
    JSON en un archivo, con bloqueo (`flock`) para evitar corrupción si
    dos peticiones escriben al mismo tiempo.
  - `JsonSolicitudCreditoRepository`: usa `JsonFileStorage` por dentro y
    traduce entre arreglos crudos y entidades `SolicitudCredito`. Es la
    implementación concreta de `SolicitudCreditoRepositoryInterface`.

- **`Presentation\Controller\SolicitudCreditoController`** es
  intencionalmente "delgado": recibe la petición, llama al servicio, y
  decide qué vista mostrar. No valida nada por su cuenta ni sabe nada de
  archivos.

- **`public/index.php`** es el *front controller* y, además, el
  *composition root*: es el único lugar de todo el proyecto donde se
  decide qué implementación concreta del repositorio se va a usar
  (`JsonSolicitudCreditoRepository`) y se ensamblan las dependencias a
  mano (sin ningún contenedor de inyección de dependencias, que sería una
  dependencia externa innecesaria para un proyecto de este tamaño).

### Por qué no se usó Composer

El autoloading normalmente se resuelve con Composer (`composer.json` +
`vendor/autoload.php`), pero eso agrega una dependencia externa (el propio
Composer) que el enunciado pedía evitar salvo que fuera absolutamente
necesaria. En su lugar, `src/Bootstrap/Autoloader.php` implementa un
autoloader manual de unas pocas líneas, basado en `spl_autoload_register`,
que traduce el namespace `App\...` directamente a una ruta dentro de
`src/`. Es exactamente el mismo mecanismo que usa Composer por debajo (el
estándar PSR-4), solo que escrito a mano.



## 5. La entidad: Solicitud de crédito

| Campo             | Tipo    | Reglas de validación                                              |
|--|||
| `id`               | int     | Autoincremental, asignado por el repositorio. No editable.         |
| `nombreCompleto`   | string  | Obligatorio, mínimo 3 caracteres, solo letras y espacios.          |
| `cedula`           | string  | Obligatoria, se normaliza a solo dígitos, debe tener 11 dígitos.   |
| `correo`           | string  | Obligatorio, formato de correo electrónico válido.                 |
| `telefono`         | string  | Obligatorio, se normaliza a solo dígitos, entre 10 y 11 dígitos.   |
| `montoSolicitado`  | float   | Obligatorio, numérico, entre RD$1,000 y RD$5,000,000.               |
| `plazoMeses`       | int     | Obligatorio, entero, entre 1 y 360 meses.                           |
| `estado`           | string  | `Pendiente` (por defecto al crear), `Aprobada` o `Rechazada`. Solo editable al modificar una solicitud existente. |
| `fechaSolicitud`   | string  | Asignada automáticamente al crear (`Y-m-d H:i:s`). No editable.     |

Todas estas reglas viven en un único lugar: el método privado `validar()`
de `SolicitudCreditoService`. Si la validación falla, se lanza una
`ValidationException` que transporta el listado completo de errores
(uno por campo), para que el formulario pueda mostrarlos todos juntos en
lugar de obligar al usuario a corregir un error a la vez.



## 6. Rutas disponibles

Como el proyecto no usa un sistema de rutas con reescritura de URL
(`mod_rewrite`), todas las acciones se resuelven mediante el parámetro de
query string `action`, sobre el único front controller `public/index.php`.

| Acción                          | Método | URL                                      |
|-|--|-|
| Listar todas las solicitudes     | GET    | `/index.php` o `/index.php?action=index`  |
| Formulario de nueva solicitud    | GET    | `/index.php?action=create`                |
| Guardar una solicitud nueva      | POST   | `/index.php?action=store`                 |
| Formulario de edición            | GET    | `/index.php?action=edit&id={id}`          |
| Guardar los cambios de edición   | POST   | `/index.php?action=update&id={id}`        |
| Eliminar una solicitud           | POST   | `/index.php?action=destroy&id={id}`       |

Las acciones que modifican datos (`store`, `update`, `destroy`) solo
aceptan `POST`; si se accede a ellas por `GET`, el front controller
responde `405 Método no permitido` sin ejecutar ninguna lógica de negocio.



## 7. Detalles de implementación que vale la pena señalar

- **Concurrencia**: `JsonFileStorage` usa `flock()` con bloqueo compartido
  (`LOCK_SH`) para lecturas y exclusivo (`LOCK_EX`) para escrituras, de
  forma que dos peticiones simultáneas no puedan corromper el archivo
  JSON escribiendo al mismo tiempo.

- **IDs autoincrementales sin base de datos**: como no hay una base de
  datos que genere identificadores automáticamente, `JsonSolicitudCreditoRepository`
  calcula el siguiente id tomando el máximo id existente en el archivo y
  sumándole uno. Esto evita que se reutilicen identificadores aunque se
  hayan eliminado solicitudes anteriores.

- **Mensajes flash**: los mensajes de éxito/error que aparecen justo
  después de crear, editar o eliminar una solicitud se guardan en
  `$_SESSION` desde el controlador, se leen una sola vez al renderizar la
  siguiente página, y se borran inmediatamente después (patrón conocido
  como "flash message"), para que no reaparezcan si el usuario recarga la
  página.

- **Formulario compartido**: `views/solicitudes/form.php` se usa tanto
  para crear como para editar. La variable `$modo` (`'crear'` o
  `'editar'`) decide la acción del formulario, el texto de los botones, y
  si se muestra o no el campo "Estado" (solo tiene sentido al editar; al
  crear, toda solicitud nueva inicia siempre en `Pendiente`).

- **Repoblado de campos tras un error de validación**: si el usuario
  envía datos inválidos, el controlador vuelve a mostrar el mismo
  formulario, pero con exactamente lo que el usuario ya había escrito
  (no se pierde lo digitado) y con los mensajes de error debajo de cada
  campo afectado.

- **Seguridad básica**: todos los datos que se imprimen dentro del HTML
  pasan por `htmlspecialchars()` antes de mostrarse, para evitar
  inyección de HTML/JavaScript (XSS) a través de campos como el nombre o
  el correo.

- **Comentarios de documentación (PHPDoc)**: PHP no tiene un equivalente
  exacto a los comentarios `/// <summary>` de C#, pero el estándar
  equivalente y ampliamente adoptado por el ecosistema PHP es **PHPDoc**:
  bloques `/** ... */` justo antes de cada clase, interfaz y método, con
  una descripción en lenguaje natural y las etiquetas `@param`, `@return`
  y `@throws`. Se aplicó de forma consistente en absolutamente todas las
  clases del proyecto. Herramientas como PHPStorm, VS Code (con Intelephense)
  o phpDocumentor leen estos bloques exactamente igual que Visual Studio
  lee los `<summary>` de C#, mostrando la documentación al pasar el
  cursor sobre una clase o un método.



## 8. Sobre el frontend y su parecido con vopm.net

El diseño visual (`public/assets/css/style.css`) retoma, a nivel de
composición, varios elementos reconocibles de vopm.net: una barra de
navegación superior con fondo oscuro y el nombre de marca en mayúsculas
con espaciado entre letras, un color de acento cálido (dorado) para los
botones y llamadas a la acción principales, tarjetas claras de bordes
redondeados y suaves para el contenido, y tipografía de sistema (sin
depender de ninguna fuente externa vía internet).

**Aclaración honesta**: las herramientas de consulta disponibles no
permiten leer directamente la hoja de estilos real de vopm.net (un sitio
construido con Astro, cuyo CSS no quedó expuesto al extraer su contenido),
así que la paleta de colores exacta es una aproximación razonable a esa
línea visual, no una copia literal de sus valores. Todos los colores están
centralizados como variables CSS en el bloque `:root` de `style.css`, así
que si se cuenta con los valores de marca exactos (código de color, familia
tipográfica específica), ajustarlos es cuestión de cambiar esas variables
en un solo lugar, sin tocar el resto del archivo.
