<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Json;

use RuntimeException;

/**
 * JsonFileStorage.
 *
 * Componente genérico de infraestructura, sin conocimiento alguno del
 * dominio de negocio, responsable únicamente de leer y escribir un
 * arreglo PHP como contenido de un archivo JSON en disco.
 *
 * Se separó deliberadamente de JsonSolicitudCreditoRepository para
 * respetar el principio de responsabilidad única: esta clase solo
 * sabe de archivos y de JSON, mientras que el repositorio es quien
 * sabe transformar esos datos crudos en entidades SolicitudCredito.
 * Si en el futuro se necesitara otro repositorio basado también en
 * JSON (por ejemplo, para otra entidad), esta misma clase se podría
 * reutilizar sin cambios.
 *
 * Para evitar que dos peticiones concurrentes corrompan el archivo,
 * todas las operaciones usan bloqueo de archivo (flock) mientras leen
 * o escriben.
 */
final class JsonFileStorage
{
    /**
     * Ruta absoluta del archivo JSON que esta instancia administra.
     *
     * @var string
     */
    private string $rutaArchivo;

    /**
     * Crea el manejador de almacenamiento para una ruta de archivo
     * específica. Si el archivo o la carpeta que lo contiene no
     * existen todavía, se crean automáticamente con un arreglo vacío.
     *
     * @param string $rutaArchivo Ruta absoluta al archivo .json.
     */
    public function __construct(string $rutaArchivo)
    {
        $this->rutaArchivo = $rutaArchivo;
        $this->asegurarQueElArchivoExista();
    }

    /**
     * Lee el contenido completo del archivo y lo devuelve como arreglo.
     *
     * Se usa un bloqueo compartido (LOCK_SH) porque varias lecturas
     * simultáneas son seguras; solo la escritura necesita bloqueo
     * exclusivo.
     *
     * @return array<int, array<string, mixed>> Filas leídas del archivo.
     *
     * @throws RuntimeException Si el archivo no se puede abrir o su
     *                           contenido no es JSON válido.
     */
    public function leerTodo(): array
    {
        $recurso = fopen($this->rutaArchivo, 'rb');

        if ($recurso === false) {
            throw new RuntimeException(sprintf('No se pudo abrir el archivo de datos "%s" para lectura.', $this->rutaArchivo));
        }

        try {
            flock($recurso, LOCK_SH);
            $contenido = stream_get_contents($recurso);
            flock($recurso, LOCK_UN);
        } finally {
            fclose($recurso);
        }

        if ($contenido === false || trim($contenido) === '') {
            return [];
        }

        $datos = json_decode($contenido, true);

        if (!is_array($datos)) {
            throw new RuntimeException(sprintf('El archivo de datos "%s" contiene JSON inválido.', $this->rutaArchivo));
        }

        return $datos;
    }

    /**
     * Sobrescribe por completo el contenido del archivo con el
     * arreglo recibido, serializado como JSON legible (con sangría).
     *
     * Se usa un bloqueo exclusivo (LOCK_EX) para evitar que dos
     * peticiones escriban al mismo tiempo y se pisen entre sí.
     *
     * @param array<int, array<string, mixed>> $datos Filas a persistir.
     *
     * @return void
     *
     * @throws RuntimeException Si el archivo no se puede abrir para escritura.
     */
    public function escribirTodo(array $datos): void
    {
        $recurso = fopen($this->rutaArchivo, 'cb');

        if ($recurso === false) {
            throw new RuntimeException(sprintf('No se pudo abrir el archivo de datos "%s" para escritura.', $this->rutaArchivo));
        }

        $json = json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            flock($recurso, LOCK_EX);
            ftruncate($recurso, 0);
            rewind($recurso);
            fwrite($recurso, (string) $json);
            fflush($recurso);
            flock($recurso, LOCK_UN);
        } finally {
            fclose($recurso);
        }
    }

    /**
     * Crea el archivo (y la carpeta que lo contiene, si hace falta)
     * con un arreglo JSON vacío, únicamente si todavía no existe.
     *
     * @return void
     */
    private function asegurarQueElArchivoExista(): void
    {
        $carpeta = dirname($this->rutaArchivo);

        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0775, true);
        }

        if (!is_file($this->rutaArchivo)) {
            file_put_contents($this->rutaArchivo, '[]');
        }
    }
}
