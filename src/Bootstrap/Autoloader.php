<?php

declare(strict_types=1);

namespace App\Bootstrap;

/**
 * Autoloader.
 *
 * Carga automáticamente las clases del proyecto siguiendo una convención
 * de tipo PSR-4 muy simplificada, sin depender de Composer ni de ninguna
 * librería externa.
 *
 * El proyecto no usa Composer a propósito: el enunciado pide evitar
 * dependencias salvo que sean absolutamente necesarias, y para un CRUD
 * de este tamaño un autoloader manual de unas pocas líneas es más que
 * suficiente y mantiene el proyecto ejecutable con un simple
 * "php -S localhost:8000 -t public".
 *
 * Convención utilizada:
 *   Namespace base  -> "App\"
 *   Carpeta base    -> "src/"
 *   App\Domain\Entity\SolicitudCredito
 *      se traduce a
 *   src/Domain/Entity/SolicitudCredito.php
 */
final class Autoloader
{
    /**
     * Prefijo de namespace que este autoloader sabe resolver.
     *
     * @var string
     */
    private const NAMESPACE_PREFIX = 'App\\';

    /**
     * Ruta absoluta a la carpeta "src" del proyecto.
     *
     * @var string
     */
    private string $sourceDirectory;

    /**
     * Crea el autoloader indicando dónde vive la carpeta "src".
     *
     * @param string $sourceDirectory Ruta absoluta a la carpeta "src".
     */
    public function __construct(string $sourceDirectory)
    {
        $this->sourceDirectory = rtrim($sourceDirectory, DIRECTORY_SEPARATOR);
    }

    /**
     * Registra este autoloader en la pila de autoloaders de PHP.
     *
     * A partir de este punto, cualquier "new App\Algo\Clase()" que no
     * exista todavía en memoria disparará automáticamente el método
     * resolve() de esta clase.
     *
     * @return void
     */
    public function register(): void
    {
        spl_autoload_register([$this, 'resolve']);
    }

    /**
     * Traduce un nombre de clase completamente calificado a una ruta de
     * archivo física y lo incluye si existe.
     *
     * @param string $fullyQualifiedClassName Ejemplo: App\Domain\Entity\SolicitudCredito
     *
     * @return void
     */
    private function resolve(string $fullyQualifiedClassName): void
    {
        // Si la clase no pertenece a nuestro namespace raíz, no hacemos nada
        // y dejamos que otro autoloader (si existiera) intente resolverla.
        if (strncmp($fullyQualifiedClassName, self::NAMESPACE_PREFIX, strlen(self::NAMESPACE_PREFIX)) !== 0) {
            return;
        }

        // Quitamos el prefijo "App\" y convertimos los separadores de
        // namespace ("\") en separadores de carpeta del sistema operativo.
        $relativeClassName = substr($fullyQualifiedClassName, strlen(self::NAMESPACE_PREFIX));
        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClassName);

        $filePath = $this->sourceDirectory . DIRECTORY_SEPARATOR . $relativePath . '.php';

        if (is_file($filePath)) {
            require $filePath;
        }
    }
}
