<?php

declare(strict_types=1);

namespace App\Presentation\Http;

/**
 * Request.
 *
 * Envoltorio muy simple alrededor de las superglobales $_GET y $_POST.
 *
 * No es un componente HTTP completo (no maneja cabeceras, cuerpo
 * crudo, ni subida de archivos) porque este CRUD no lo necesita; su
 * único propósito es evitar que $_GET y $_POST se usen directamente
 * dentro de los controladores, para que estos sean más fáciles de
 * leer y, en teoría, de probar de forma aislada.
 */
final class Request
{
    /**
     * @param array<string, mixed> $parametrosGet  Copia de $_GET.
     * @param array<string, mixed> $parametrosPost Copia de $_POST.
     */
    public function __construct(
        private readonly array $parametrosGet,
        private readonly array $parametrosPost
    ) {
    }

    /**
     * Construye un Request a partir de las superglobales actuales.
     *
     * Este es el único punto del proyecto donde se leen $_GET y
     * $_POST directamente; se llama una sola vez, desde el front
     * controller (public/index.php).
     *
     * @return self
     */
    public static function desdeSuperglobales(): self
    {
        return new self($_GET, $_POST);
    }

    /**
     * Obtiene un valor de la cadena de consulta (query string).
     *
     * @param string $nombre        Nombre del parámetro.
     * @param string $valorPorDefecto Valor a devolver si el parámetro no existe.
     *
     * @return string
     */
    public function obtenerDeGet(string $nombre, string $valorPorDefecto = ''): string
    {
        $valor = $this->parametrosGet[$nombre] ?? $valorPorDefecto;

        return is_scalar($valor) ? (string) $valor : $valorPorDefecto;
    }

    /**
     * Obtiene todos los datos enviados por POST, tal como llegaron.
     *
     * Se usa dentro del controlador para pasarle el arreglo completo
     * al servicio de aplicación, que es quien valida cada campo.
     *
     * @return array<string, mixed>
     */
    public function todoElPost(): array
    {
        return $this->parametrosPost;
    }

    /**
     * Indica si la petición actual llegó por el método HTTP POST.
     *
     * @return bool
     */
    public function esPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }
}
