<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * SolicitudCredito.
 *
 * Entidad de dominio que representa una solicitud de crédito hecha por
 * un cliente. Es la entidad principal sobre la que trabaja este CRUD.
 *
 * Se modela como un objeto plano (sin lógica de persistencia dentro):
 * la entidad solo conoce sus propios datos y sabe convertirse hacia y
 * desde un arreglo asociativo, que es el formato en el que finalmente
 * se guarda dentro del archivo JSON. La responsabilidad de leer o
 * escribir en disco vive en la capa de infraestructura, no aquí.
 */
final class SolicitudCredito
{
    /**
     * Estados posibles de una solicitud de crédito.
     *
     * Se definen como constantes públicas para evitar "strings mágicos"
     * repetidos en el resto del código y para que el formulario y las
     * validaciones compartan siempre la misma lista de valores permitidos.
     */
    public const ESTADO_PENDIENTE = 'Pendiente';
    public const ESTADO_APROBADA = 'Aprobada';
    public const ESTADO_RECHAZADA = 'Rechazada';

    /**
     * Lista de todos los estados válidos para una solicitud.
     *
     * @var string[]
     */
    public const ESTADOS_VALIDOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_APROBADA,
        self::ESTADO_RECHAZADA,
    ];

    /**
     * Identificador único de la solicitud. Es "null" mientras la
     * solicitud todavía no ha sido guardada por primera vez.
     *
     * @var int|null
     */
    private ?int $id;

    /** @var string Nombre completo del cliente que solicita el crédito. */
    private string $nombreCompleto;

    /** @var string Número de cédula del cliente (formato dominicano). */
    private string $cedula;

    /** @var string Correo electrónico de contacto del cliente. */
    private string $correo;

    /** @var string Número de teléfono de contacto del cliente. */
    private string $telefono;

    /** @var float Monto de dinero solicitado, en pesos dominicanos. */
    private float $montoSolicitado;

    /** @var int Plazo en meses en el que el cliente propone pagar el crédito. */
    private int $plazoMeses;

    /** @var string Estado actual de la solicitud (ver ESTADOS_VALIDOS). */
    private string $estado;

    /** @var string Fecha en la que se registró la solicitud (formato Y-m-d H:i:s). */
    private string $fechaSolicitud;

    /**
     * Construye una nueva instancia de SolicitudCredito.
     *
     * El constructor es intencionalmente "tonto": no valida reglas de
     * negocio (eso es responsabilidad de la capa de aplicación, en
     * SolicitudCreditoService), solo asigna los valores recibidos.
     * Esto mantiene la entidad simple y fácil de reconstruir tanto
     * desde un formulario HTML como desde una fila leída del JSON.
     *
     * @param int|null $id              Identificador único, o null si aún no existe.
     * @param string   $nombreCompleto  Nombre completo del cliente.
     * @param string   $cedula          Cédula del cliente.
     * @param string   $correo          Correo electrónico del cliente.
     * @param string   $telefono        Teléfono del cliente.
     * @param float    $montoSolicitado Monto solicitado en pesos dominicanos.
     * @param int      $plazoMeses      Plazo propuesto, en meses.
     * @param string   $estado          Estado actual de la solicitud.
     * @param string   $fechaSolicitud  Fecha de registro, formato Y-m-d H:i:s.
     */
    public function __construct(
        ?int $id,
        string $nombreCompleto,
        string $cedula,
        string $correo,
        string $telefono,
        float $montoSolicitado,
        int $plazoMeses,
        string $estado,
        string $fechaSolicitud
    ) {
        $this->id = $id;
        $this->nombreCompleto = $nombreCompleto;
        $this->cedula = $cedula;
        $this->correo = $correo;
        $this->telefono = $telefono;
        $this->montoSolicitado = $montoSolicitado;
        $this->plazoMeses = $plazoMeses;
        $this->estado = $estado;
        $this->fechaSolicitud = $fechaSolicitud;
    }

    /**
     * Crea una entidad SolicitudCredito a partir de un arreglo asociativo,
     * tal como viene una fila directamente desde el archivo JSON.
     *
     * @param array<string, mixed> $datos Fila cruda proveniente del JSON.
     *
     * @return self
     */
    public static function desdeArreglo(array $datos): self
    {
        return new self(
            isset($datos['id']) ? (int) $datos['id'] : null,
            (string) ($datos['nombreCompleto'] ?? ''),
            (string) ($datos['cedula'] ?? ''),
            (string) ($datos['correo'] ?? ''),
            (string) ($datos['telefono'] ?? ''),
            (float) ($datos['montoSolicitado'] ?? 0),
            (int) ($datos['plazoMeses'] ?? 0),
            (string) ($datos['estado'] ?? self::ESTADO_PENDIENTE),
            (string) ($datos['fechaSolicitud'] ?? date('Y-m-d H:i:s'))
        );
    }

    /**
     * Convierte la entidad a un arreglo asociativo, listo para ser
     * serializado a JSON por la capa de infraestructura.
     *
     * @return array<string, mixed>
     */
    public function haciaArreglo(): array
    {
        return [
            'id' => $this->id,
            'nombreCompleto' => $this->nombreCompleto,
            'cedula' => $this->cedula,
            'correo' => $this->correo,
            'telefono' => $this->telefono,
            'montoSolicitado' => $this->montoSolicitado,
            'plazoMeses' => $this->plazoMeses,
            'estado' => $this->estado,
            'fechaSolicitud' => $this->fechaSolicitud,
        ];
    }

    // -----------------------------------------------------------------
    // Getters. La entidad es de solo lectura hacia afuera; los únicos
    // que pueden "modificarla" son el repositorio (para asignar el id)
    // y el servicio (que construye una entidad nueva con los datos
    // actualizados en lugar de mutar la existente).
    // -----------------------------------------------------------------

    public function id(): ?int
    {
        return $this->id;
    }

    public function nombreCompleto(): string
    {
        return $this->nombreCompleto;
    }

    public function cedula(): string
    {
        return $this->cedula;
    }

    public function correo(): string
    {
        return $this->correo;
    }

    public function telefono(): string
    {
        return $this->telefono;
    }

    public function montoSolicitado(): float
    {
        return $this->montoSolicitado;
    }

    public function plazoMeses(): int
    {
        return $this->plazoMeses;
    }

    public function estado(): string
    {
        return $this->estado;
    }

    public function fechaSolicitud(): string
    {
        return $this->fechaSolicitud;
    }

    /**
     * Devuelve una copia de esta entidad con un identificador asignado.
     *
     * Se usa una sola vez, justo cuando el repositorio guarda por
     * primera vez una solicitud nueva y necesita "sellarla" con el
     * siguiente id disponible.
     *
     * @param int $id Identificador que se le asignará a la copia.
     *
     * @return self Nueva instancia con el id ya asignado.
     */
    public function conId(int $id): self
    {
        return new self(
            $id,
            $this->nombreCompleto,
            $this->cedula,
            $this->correo,
            $this->telefono,
            $this->montoSolicitado,
            $this->plazoMeses,
            $this->estado,
            $this->fechaSolicitud
        );
    }
}
