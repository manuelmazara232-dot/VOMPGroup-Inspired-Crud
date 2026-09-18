<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Exception\ValidationException;
use App\Domain\Entity\SolicitudCredito;
use App\Domain\Repository\SolicitudCreditoRepositoryInterface;

/**
 * SolicitudCreditoService.
 *
 * Capa de aplicación: contiene las reglas de negocio del CRUD
 * (qué datos son obligatorios, qué formato deben tener, qué rangos
 * son válidos) y orquesta las llamadas al repositorio.
 *
 * Ni el controlador ni las vistas conocen estas reglas directamente;
 * todas pasan siempre por este servicio antes de llegar al
 * repositorio. Esto permite, por ejemplo, reutilizar exactamente las
 * mismas reglas de validación si mañana se agrega una API además del
 * formulario HTML, sin duplicar código.
 *
 * El servicio depende únicamente de la interfaz de repositorio
 * (SolicitudCreditoRepositoryInterface), nunca de la implementación
 * concreta basada en JSON. Esa es la esencia de la arquitectura
 * limpia aplicada aquí: las capas internas (dominio y aplicación) no
 * saben nada sobre archivos, JSON, ni HTTP.
 */
final class SolicitudCreditoService
{
    /**
     * Monto mínimo permitido para una solicitud de crédito, en pesos
     * dominicanos.
     */
    private const MONTO_MINIMO = 1000.0;

    /**
     * Monto máximo permitido para una solicitud de crédito, en pesos
     * dominicanos.
     */
    private const MONTO_MAXIMO = 5000000.0;

    /**
     * Plazo mínimo permitido, en meses.
     */
    private const PLAZO_MINIMO = 1;

    /**
     * Plazo máximo permitido, en meses.
     */
    private const PLAZO_MAXIMO = 360;

    /**
     * @var SolicitudCreditoRepositoryInterface
     */
    private SolicitudCreditoRepositoryInterface $repositorio;

    /**
     * @param SolicitudCreditoRepositoryInterface $repositorio Repositorio concreto
     *                                                          a utilizar (inyectado
     *                                                          desde el front controller).
     */
    public function __construct(SolicitudCreditoRepositoryInterface $repositorio)
    {
        $this->repositorio = $repositorio;
    }

    /**
     * Devuelve todas las solicitudes registradas, ordenadas de la más
     * reciente a la más antigua según su identificador.
     *
     * @return SolicitudCredito[]
     */
    public function listar(): array
    {
        $solicitudes = $this->repositorio->listarTodas();

        usort(
            $solicitudes,
            static fn (SolicitudCredito $a, SolicitudCredito $b): int => $b->id() <=> $a->id()
        );

        return $solicitudes;
    }

    /**
     * Busca una solicitud por su identificador.
     *
     * @param int $id Identificador de la solicitud.
     *
     * @return SolicitudCredito|null
     */
    public function buscar(int $id): ?SolicitudCredito
    {
        return $this->repositorio->buscarPorId($id);
    }

    /**
     * Valida y registra una nueva solicitud de crédito.
     *
     * @param array<string, mixed> $datosDelFormulario Datos crudos provenientes de $_POST.
     *
     * @return SolicitudCredito La solicitud ya creada, con su id asignado.
     *
     * @throws ValidationException Si alguno de los datos no cumple las reglas de negocio.
     */
    public function registrar(array $datosDelFormulario): SolicitudCredito
    {
        $datosLimpios = $this->validar($datosDelFormulario, esCreacion: true);

        $solicitud = new SolicitudCredito(
            null,
            $datosLimpios['nombreCompleto'],
            $datosLimpios['cedula'],
            $datosLimpios['correo'],
            $datosLimpios['telefono'],
            $datosLimpios['montoSolicitado'],
            $datosLimpios['plazoMeses'],
            SolicitudCredito::ESTADO_PENDIENTE,
            date('Y-m-d H:i:s')
        );

        return $this->repositorio->crear($solicitud);
    }

    /**
     * Valida y actualiza una solicitud de crédito existente.
     *
     * @param int                   $id                  Identificador de la solicitud a modificar.
     * @param array<string, mixed>  $datosDelFormulario  Datos crudos provenientes de $_POST.
     *
     * @return void
     *
     * @throws ValidationException Si alguno de los datos no cumple las reglas de negocio.
     * @throws \RuntimeException   Si no existe ninguna solicitud con ese id.
     */
    public function actualizar(int $id, array $datosDelFormulario): void
    {
        $solicitudExistente = $this->repositorio->buscarPorId($id);

        if ($solicitudExistente === null) {
            throw new \RuntimeException(sprintf('No existe ninguna solicitud con el id %d.', $id));
        }

        $datosLimpios = $this->validar($datosDelFormulario, esCreacion: false);

        $solicitudActualizada = new SolicitudCredito(
            $id,
            $datosLimpios['nombreCompleto'],
            $datosLimpios['cedula'],
            $datosLimpios['correo'],
            $datosLimpios['telefono'],
            $datosLimpios['montoSolicitado'],
            $datosLimpios['plazoMeses'],
            $datosLimpios['estado'],
            $solicitudExistente->fechaSolicitud()
        );

        $this->repositorio->actualizar($solicitudActualizada);
    }

    /**
     * Elimina una solicitud de crédito.
     *
     * @param int $id Identificador de la solicitud a eliminar.
     *
     * @return bool true si se eliminó, false si no existía.
     */
    public function eliminar(int $id): bool
    {
        return $this->repositorio->eliminar($id);
    }

    /**
     * Aplica todas las reglas de validación de negocio sobre los datos
     * crudos recibidos desde el formulario.
     *
     * @param array<string, mixed> $datos       Datos crudos, tal como llegan en $_POST.
     * @param bool                 $esCreacion  true si es un alta nueva (el estado no
     *                                          se valida porque siempre inicia en
     *                                          "Pendiente"); false si es una edición.
     *
     * @return array<string, mixed> Datos ya validados y normalizados.
     *
     * @throws ValidationException Si se encuentra al menos un error.
     */
    private function validar(array $datos, bool $esCreacion): array
    {
        $errores = [];

        // Nombre completo: obligatorio, longitud mínima, solo letras y espacios
        // (se permiten tildes y la letra ñ, comunes en nombres dominicanos).
        $nombreCompleto = trim((string) ($datos['nombreCompleto'] ?? ''));
        if ($nombreCompleto === '') {
            $errores['nombreCompleto'] = 'El nombre completo es obligatorio.';
        } elseif (mb_strlen($nombreCompleto) < 3) {
            $errores['nombreCompleto'] = 'El nombre completo debe tener al menos 3 caracteres.';
        } elseif (preg_match('/^[\p{L}\s]+$/u', $nombreCompleto) !== 1) {
            $errores['nombreCompleto'] = 'El nombre completo solo puede contener letras y espacios.';
        }

        // Cédula: obligatoria, se normaliza a solo dígitos y debe tener
        // exactamente 11 dígitos, tal como una cédula dominicana real.
        $cedulaOriginal = trim((string) ($datos['cedula'] ?? ''));
        $cedulaSoloDigitos = preg_replace('/\D/', '', $cedulaOriginal) ?? '';
        if ($cedulaOriginal === '') {
            $errores['cedula'] = 'La cédula es obligatoria.';
        } elseif (strlen($cedulaSoloDigitos) !== 11) {
            $errores['cedula'] = 'La cédula debe tener 11 dígitos (formato 000-0000000-0).';
        }

        // Correo: obligatorio y con formato de correo electrónico válido.
        $correo = trim((string) ($datos['correo'] ?? ''));
        if ($correo === '') {
            $errores['correo'] = 'El correo electrónico es obligatorio.';
        } elseif (filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            $errores['correo'] = 'El correo electrónico no tiene un formato válido.';
        }

        // Teléfono: obligatorio, se normaliza a solo dígitos y debe tener
        // entre 10 y 11 dígitos (números dominicanos con código de área).
        $telefonoOriginal = trim((string) ($datos['telefono'] ?? ''));
        $telefonoSoloDigitos = preg_replace('/\D/', '', $telefonoOriginal) ?? '';
        if ($telefonoOriginal === '') {
            $errores['telefono'] = 'El teléfono es obligatorio.';
        } elseif (strlen($telefonoSoloDigitos) < 10 || strlen($telefonoSoloDigitos) > 11) {
            $errores['telefono'] = 'El teléfono debe tener 10 dígitos, incluyendo el código de área.';
        }

        // Monto solicitado: obligatorio, numérico y dentro del rango permitido.
        $montoCrudo = $datos['montoSolicitado'] ?? '';
        if ($montoCrudo === '' || !is_numeric($montoCrudo)) {
            $errores['montoSolicitado'] = 'El monto solicitado debe ser un número.';
        } else {
            $monto = (float) $montoCrudo;
            if ($monto < self::MONTO_MINIMO || $monto > self::MONTO_MAXIMO) {
                $errores['montoSolicitado'] = sprintf(
                    'El monto debe estar entre RD$%s y RD$%s.',
                    number_format(self::MONTO_MINIMO, 0),
                    number_format(self::MONTO_MAXIMO, 0)
                );
            }
        }

        // Plazo en meses: obligatorio, entero y dentro del rango permitido.
        $plazoCrudo = $datos['plazoMeses'] ?? '';
        if ($plazoCrudo === '' || !is_numeric($plazoCrudo) || (int) $plazoCrudo != $plazoCrudo) {
            $errores['plazoMeses'] = 'El plazo debe ser un número entero de meses.';
        } else {
            $plazo = (int) $plazoCrudo;
            if ($plazo < self::PLAZO_MINIMO || $plazo > self::PLAZO_MAXIMO) {
                $errores['plazoMeses'] = sprintf(
                    'El plazo debe estar entre %d y %d meses.',
                    self::PLAZO_MINIMO,
                    self::PLAZO_MAXIMO
                );
            }
        }

        // El estado solo se valida (y se permite editar) cuando la
        // operación es una actualización; al crear, siempre inicia
        // como "Pendiente" y el usuario no lo puede elegir.
        $estado = SolicitudCredito::ESTADO_PENDIENTE;
        if (!$esCreacion) {
            $estado = (string) ($datos['estado'] ?? '');
            if (!in_array($estado, SolicitudCredito::ESTADOS_VALIDOS, true)) {
                $errores['estado'] = 'El estado seleccionado no es válido.';
            }
        }

        if (count($errores) > 0) {
            throw new ValidationException($errores);
        }

        return [
            'nombreCompleto' => $nombreCompleto,
            'cedula' => $cedulaSoloDigitos,
            'correo' => $correo,
            'telefono' => $telefonoSoloDigitos,
            'montoSolicitado' => (float) $montoCrudo,
            'plazoMeses' => (int) $plazoCrudo,
            'estado' => $estado,
        ];
    }
}
