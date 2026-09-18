<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\SolicitudCredito;

/**
 * SolicitudCreditoRepositoryInterface.
 *
 * Contrato (puerto) que define cómo se persiste y se consulta la
 * entidad SolicitudCredito, sin especificar el mecanismo real de
 * almacenamiento.
 *
 * Esta interfaz es la pieza clave de la "arquitectura limpia" en este
 * proyecto: la capa de aplicación (SolicitudCreditoService) depende
 * únicamente de esta interfaz, nunca de la implementación concreta.
 * Gracias a eso, hoy la implementación guarda los datos en un archivo
 * JSON (JsonSolicitudCreditoRepository), pero el día de mañana se
 * podría escribir una implementación que use MySQL, SQLite o
 * cualquier otro motor, sin tener que tocar ni una línea del
 * servicio ni de los controladores.
 */
interface SolicitudCreditoRepositoryInterface
{
    /**
     * Devuelve todas las solicitudes de crédito registradas.
     *
     * @return SolicitudCredito[] Lista de solicitudes, puede venir vacía.
     */
    public function listarTodas(): array;

    /**
     * Busca una solicitud puntual por su identificador.
     *
     * @param int $id Identificador de la solicitud.
     *
     * @return SolicitudCredito|null La solicitud encontrada, o null si no existe.
     */
    public function buscarPorId(int $id): ?SolicitudCredito;

    /**
     * Guarda una solicitud nueva y le asigna un identificador único.
     *
     * @param SolicitudCredito $solicitud Solicitud sin id todavía.
     *
     * @return SolicitudCredito La misma solicitud, ya con su id asignado.
     */
    public function crear(SolicitudCredito $solicitud): SolicitudCredito;

    /**
     * Reemplaza los datos de una solicitud existente.
     *
     * @param SolicitudCredito $solicitud Solicitud con el id de un registro existente.
     *
     * @return bool true si se encontró y actualizó, false si el id no existía.
     */
    public function actualizar(SolicitudCredito $solicitud): bool;

    /**
     * Elimina una solicitud por su identificador.
     *
     * @param int $id Identificador de la solicitud a eliminar.
     *
     * @return bool true si se encontró y eliminó, false si el id no existía.
     */
    public function eliminar(int $id): bool;
}
