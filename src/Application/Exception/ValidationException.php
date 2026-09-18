<?php

declare(strict_types=1);

namespace App\Application\Exception;

use RuntimeException;

/**
 * ValidationException.
 *
 * Excepción que se lanza cuando los datos enviados por el usuario no
 * cumplen las reglas de negocio definidas en SolicitudCreditoService
 * (campos obligatorios, formatos, rangos numéricos, etc.).
 *
 * En lugar de devolver un solo mensaje de texto, esta excepción
 * transporta un arreglo con todos los errores encontrados, para que
 * el controlador pueda mostrárselos todos juntos al usuario en el
 * formulario, en vez de obligarlo a corregir el formulario un campo
 * a la vez.
 */
final class ValidationException extends RuntimeException
{
    /**
     * Lista de errores de validación encontrados.
     *
     * Cada entrada tiene como llave el nombre del campo y como valor
     * el mensaje de error correspondiente a ese campo.
     *
     * @var array<string, string>
     */
    private array $errores;

    /**
     * Crea la excepción a partir del listado de errores encontrados.
     *
     * @param array<string, string> $errores Errores encontrados, indexados por campo.
     */
    public function __construct(array $errores)
    {
        parent::__construct('Los datos enviados no son válidos.');
        $this->errores = $errores;
    }

    /**
     * Devuelve el listado completo de errores de validación.
     *
     * @return array<string, string>
     */
    public function errores(): array
    {
        return $this->errores;
    }
}
