<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Exception\ValidationException;
use App\Application\Service\SolicitudCreditoService;
use App\Presentation\Http\Request;

/**
 * SolicitudCreditoController.
 *
 * Controlador de la capa de presentación. Traduce peticiones HTTP en
 * llamadas a SolicitudCreditoService y decide qué vista renderizar
 * con el resultado.
 *
 * El controlador es deliberadamente "delgado": no contiene reglas de
 * negocio ni sabe nada sobre archivos JSON. Su única responsabilidad
 * es la coordinación entre la petición HTTP, el servicio de
 * aplicación y las vistas.
 */
final class SolicitudCreditoController
{
    /**
     * @var SolicitudCreditoService
     */
    private SolicitudCreditoService $servicio;

    /**
     * Ruta absoluta a la carpeta donde viven las plantillas (.php)
     * de esta sección del sitio.
     *
     * @var string
     */
    private string $carpetaVistas;

    /**
     * @param SolicitudCreditoService $servicio      Servicio de aplicación ya configurado.
     * @param string                  $carpetaVistas Ruta absoluta a la carpeta "views".
     */
    public function __construct(SolicitudCreditoService $servicio, string $carpetaVistas)
    {
        $this->servicio = $servicio;
        $this->carpetaVistas = rtrim($carpetaVistas, DIRECTORY_SEPARATOR);
    }

    /**
     * Acción "index": muestra el listado completo de solicitudes.
     *
     * Ruta: GET /?action=index (o sin parámetro "action", ya que es
     * la acción por defecto definida en el front controller).
     *
     * @return void
     */
    public function index(): void
    {
        $solicitudes = $this->servicio->listar();

        $this->renderizar('solicitudes/index', [
            'titulo' => 'Solicitudes de credito',
            'solicitudes' => $solicitudes,
        ]);
    }

    /**
     * Acción "create": muestra el formulario vacío para registrar una
     * nueva solicitud.
     *
     * Ruta: GET /?action=create
     *
     * @return void
     */
    public function create(): void
    {
        $this->renderizar('solicitudes/form', [
            'titulo' => 'Nueva solicitud de credito',
            'modo' => 'crear',
            'solicitud' => null,
            'errores' => [],
            'valoresPrevios' => [],
        ]);
    }

    /**
     * Acción "store": procesa el envío del formulario de creación.
     *
     * Ruta: POST /?action=store
     *
     * @param Request $peticion Datos de la petición HTTP actual.
     *
     * @return void
     */
    public function store(Request $peticion): void
    {
        try {
            $this->servicio->registrar($peticion->todoElPost());

            $this->establecerMensajeFlash('exito', 'La solicitud fue registrada correctamente.');
            $this->redirigir('index.php');
        } catch (ValidationException $excepcion) {
            // Si la validación falla, se vuelve a mostrar el mismo
            // formulario de creación, pero conservando lo que el
            // usuario ya había escrito y señalando los errores.
            $this->renderizar('solicitudes/form', [
                'titulo' => 'Nueva solicitud de credito',
                'modo' => 'crear',
                'solicitud' => null,
                'errores' => $excepcion->errores(),
                'valoresPrevios' => $peticion->todoElPost(),
            ]);
        }
    }

    /**
     * Acción "edit": muestra el formulario de edición, precargado con
     * los datos actuales de la solicitud solicitada.
     *
     * Ruta: GET /?action=edit&id=123
     *
     * @param int $id Identificador de la solicitud a editar.
     *
     * @return void
     */
    public function edit(int $id): void
    {
        $solicitud = $this->servicio->buscar($id);

        if ($solicitud === null) {
            $this->establecerMensajeFlash('error', 'La solicitud indicada no existe.');
            $this->redirigir('index.php');
            return;
        }

        $this->renderizar('solicitudes/form', [
            'titulo' => 'Editar solicitud de credito',
            'modo' => 'editar',
            'solicitud' => $solicitud,
            'errores' => [],
            'valoresPrevios' => [],
        ]);
    }

    /**
     * Acción "update": procesa el envío del formulario de edición.
     *
     * Ruta: POST /?action=update&id=123
     *
     * @param int     $id       Identificador de la solicitud a actualizar.
     * @param Request $peticion Datos de la petición HTTP actual.
     *
     * @return void
     */
    public function update(int $id, Request $peticion): void
    {
        try {
            $this->servicio->actualizar($id, $peticion->todoElPost());

            $this->establecerMensajeFlash('exito', 'La solicitud fue actualizada correctamente.');
            $this->redirigir('index.php');
        } catch (ValidationException $excepcion) {
            $solicitud = $this->servicio->buscar($id);

            $this->renderizar('solicitudes/form', [
                'titulo' => 'Editar solicitud de credito',
                'modo' => 'editar',
                'solicitud' => $solicitud,
                'errores' => $excepcion->errores(),
                'valoresPrevios' => $peticion->todoElPost(),
            ]);
        }
    }

    /**
     * Acción "destroy": elimina una solicitud existente.
     *
     * Ruta: POST /?action=destroy&id=123
     *
     * @param int $id Identificador de la solicitud a eliminar.
     *
     * @return void
     */
    public function destroy(int $id): void
    {
        $seElimino = $this->servicio->eliminar($id);

        $this->establecerMensajeFlash(
            $seElimino ? 'exito' : 'error',
            $seElimino ? 'La solicitud fue eliminada.' : 'La solicitud indicada no existe.'
        );

        $this->redirigir('index.php');
    }

    /**
     * Incluye una plantilla PHP envuelta en el layout común
     * (encabezado y pie de página), pasándole las variables
     * indicadas dentro de su propio ámbito local.
     *
     * @param string               $nombreVista Nombre de la vista relativo a la carpeta "views",
     *                                          sin la extensión ".php" (ejemplo: "solicitudes/index").
     * @param array<string, mixed> $datos       Variables que estarán disponibles dentro de la vista.
     *
     * @return void
     */
    private function renderizar(string $nombreVista, array $datos): void
    {
        // extract() convierte cada llave del arreglo en una variable
        // local dentro de esta función; como los "require" de abajo
        // se ejecutan en el mismo ámbito, esas variables quedan
        // disponibles directamente dentro de las plantillas.
        extract($datos, EXTR_SKIP);

        $mensajeFlash = $this->obtenerMensajeFlash();

        require $this->carpetaVistas . '/layout/header.php';
        require $this->carpetaVistas . '/' . $nombreVista . '.php';
        require $this->carpetaVistas . '/layout/footer.php';
    }

    /**
     * Guarda un mensaje de una sola lectura en la sesión, para
     * mostrarlo justo después de una redirección (patrón
     * "flash message").
     *
     * @param string $tipo    "exito" o "error"; se usa como clase CSS en la vista.
     * @param string $mensaje Texto a mostrar.
     *
     * @return void
     */
    private function establecerMensajeFlash(string $tipo, string $mensaje): void
    {
        $_SESSION['mensaje_flash'] = ['tipo' => $tipo, 'texto' => $mensaje];
    }

    /**
     * Recupera el mensaje flash pendiente (si existe) y lo borra
     * inmediatamente de la sesión, para que no se vuelva a mostrar
     * en la siguiente petición.
     *
     * @return array{tipo: string, texto: string}|null
     */
    private function obtenerMensajeFlash(): ?array
    {
        $mensaje = $_SESSION['mensaje_flash'] ?? null;
        unset($_SESSION['mensaje_flash']);

        return $mensaje;
    }

    /**
     * Envía una redirección HTTP 302 hacia otra URL dentro del mismo
     * front controller y detiene la ejecución del script.
     *
     * @param string $destino Ruta relativa a la que se redirige.
     *
     * @return void
     */
    private function redirigir(string $destino): void
    {
        header('Location: ' . $destino);
        exit;
    }
}
