<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Json;

use App\Domain\Entity\SolicitudCredito;
use App\Domain\Repository\SolicitudCreditoRepositoryInterface;

/**
 * JsonSolicitudCreditoRepository.
 *
 * Implementación concreta de SolicitudCreditoRepositoryInterface que
 * persiste las solicitudes de crédito en un archivo .json en lugar de
 * una base de datos.
 *
 * Esta clase es la única parte del sistema que sabe que los datos
 * viven en un archivo JSON: traduce entre arreglos crudos (lo que
 * entiende JsonFileStorage) y entidades SolicitudCredito (lo que
 * entiende el resto de la aplicación). Ni el servicio ni el
 * controlador conocen este detalle; ambos dependen únicamente de la
 * interfaz de dominio.
 */
final class JsonSolicitudCreditoRepository implements SolicitudCreditoRepositoryInterface
{
    /**
     * Manejador de bajo nivel para leer y escribir el archivo JSON.
     *
     * @var JsonFileStorage
     */
    private JsonFileStorage $almacenamiento;

    /**
     * @param JsonFileStorage $almacenamiento Manejador de archivo ya configurado
     *                                        con la ruta del archivo de datos.
     */
    public function __construct(JsonFileStorage $almacenamiento)
    {
        $this->almacenamiento = $almacenamiento;
    }

    /**
     * {@inheritDoc}
     */
    public function listarTodas(): array
    {
        $filas = $this->almacenamiento->leerTodo();

        return array_map(
            static fn (array $fila): SolicitudCredito => SolicitudCredito::desdeArreglo($fila),
            $filas
        );
    }

    /**
     * {@inheritDoc}
     */
    public function buscarPorId(int $id): ?SolicitudCredito
    {
        foreach ($this->almacenamiento->leerTodo() as $fila) {
            if ((int) ($fila['id'] ?? 0) === $id) {
                return SolicitudCredito::desdeArreglo($fila);
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function crear(SolicitudCredito $solicitud): SolicitudCredito
    {
        $filas = $this->almacenamiento->leerTodo();

        $siguienteId = $this->calcularSiguienteId($filas);
        $solicitudConId = $solicitud->conId($siguienteId);

        $filas[] = $solicitudConId->haciaArreglo();

        $this->almacenamiento->escribirTodo($filas);

        return $solicitudConId;
    }

    /**
     * {@inheritDoc}
     */
    public function actualizar(SolicitudCredito $solicitud): bool
    {
        $filas = $this->almacenamiento->leerTodo();
        $encontrada = false;

        foreach ($filas as $indice => $fila) {
            if ((int) ($fila['id'] ?? 0) === $solicitud->id()) {
                $filas[$indice] = $solicitud->haciaArreglo();
                $encontrada = true;
                break;
            }
        }

        if ($encontrada) {
            $this->almacenamiento->escribirTodo($filas);
        }

        return $encontrada;
    }

    /**
     * {@inheritDoc}
     */
    public function eliminar(int $id): bool
    {
        $filas = $this->almacenamiento->leerTodo();
        $cantidadOriginal = count($filas);

        $filas = array_values(array_filter(
            $filas,
            static fn (array $fila): bool => (int) ($fila['id'] ?? 0) !== $id
        ));

        $seElimino = count($filas) < $cantidadOriginal;

        if ($seElimino) {
            $this->almacenamiento->escribirTodo($filas);
        }

        return $seElimino;
    }

    /**
     * Calcula el siguiente identificador autoincremental disponible,
     * tomando el mayor id existente en el archivo y sumándole uno.
     *
     * Se calcula así, en lugar de simplemente contar filas, para que
     * los identificadores nunca se reutilicen aunque se hayan borrado
     * solicitudes anteriores.
     *
     * @param array<int, array<string, mixed>> $filas Filas actuales del archivo.
     *
     * @return int
     */
    private function calcularSiguienteId(array $filas): int
    {
        $idMaximo = 0;

        foreach ($filas as $fila) {
            $idMaximo = max($idMaximo, (int) ($fila['id'] ?? 0));
        }

        return $idMaximo + 1;
    }
}
