<?php
/**
 * Vista: solicitudes/index.php
 *
 * Lista todas las solicitudes de crédito registradas en el archivo
 * JSON, en una tabla, con enlaces para editar o eliminar cada una.
 *
 * Variables recibidas desde SolicitudCreditoController::index():
 *
 * @var \App\Domain\Entity\SolicitudCredito[] $solicitudes Lista completa a mostrar.
 */
?>

<div class="encabezado-seccion">
    <div>
        <p class="etiqueta-superior">VOPM &middot; Ecosistema de credito</p>
        <h1>Solicitudes de credito</h1>
        <p class="texto-secundario">
            Listado completo de solicitudes registradas. Los datos se
            guardan en <code>storage/solicitudes.json</code>.
        </p>
    </div>
    <a href="index.php?action=create" class="boton boton--primario">
        Registrar nueva solicitud
    </a>
</div>

<?php if (count($solicitudes) === 0): ?>

    <!-- Estado vacio: todavia no existe ninguna solicitud registrada. -->
    <div class="tarjeta estado-vacio">
        <p>Aun no hay solicitudes de credito registradas.</p>
        <a href="index.php?action=create" class="boton boton--primario">
            Registrar la primera solicitud
        </a>
    </div>

<?php else: ?>

    <div class="tarjeta tabla-envoltorio">
        <table class="tabla">
            <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Cedula</th>
                <th>Contacto</th>
                <th>Monto solicitado</th>
                <th>Plazo</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th class="columna-acciones">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($solicitudes as $solicitud): ?>
                <tr>
                    <td>#<?= (int) $solicitud->id() ?></td>
                    <td><?= htmlspecialchars($solicitud->nombreCompleto(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php
                        // Se formatea la cedula (que se guarda solo con
                        // digitos) al formato dominicano 000-0000000-0
                        // unicamente para mostrarla en pantalla.
                        $cedula = $solicitud->cedula();
                        echo htmlspecialchars(
                            substr($cedula, 0, 3) . '-' . substr($cedula, 3, 7) . '-' . substr($cedula, 10, 1),
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                    </td>
                    <td>
                        <div><?= htmlspecialchars($solicitud->correo(), ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="texto-secundario"><?= htmlspecialchars($solicitud->telefono(), ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                    <td>RD$ <?= number_format($solicitud->montoSolicitado(), 2) ?></td>
                    <td><?= (int) $solicitud->plazoMeses() ?> meses</td>
                    <td>
                        <?php
                        // Clase CSS del badge segun el estado, para
                        // pintarlo con un color distinto en cada caso.
                        $claseEstado = match ($solicitud->estado()) {
                            \App\Domain\Entity\SolicitudCredito::ESTADO_APROBADA => 'insignia--aprobada',
                            \App\Domain\Entity\SolicitudCredito::ESTADO_RECHAZADA => 'insignia--rechazada',
                            default => 'insignia--pendiente',
                        };
                        ?>
                        <span class="insignia <?= $claseEstado ?>">
                            <?= htmlspecialchars($solicitud->estado(), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="texto-secundario"><?= htmlspecialchars($solicitud->fechaSolicitud(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="columna-acciones">
                        <a href="index.php?action=edit&id=<?= (int) $solicitud->id() ?>" class="enlace-accion">
                            Editar
                        </a>

                        <form
                            method="post"
                            action="index.php?action=destroy&id=<?= (int) $solicitud->id() ?>"
                            class="formulario-en-linea"
                            data-confirmar-eliminacion="true"
                        >
                            <button type="submit" class="enlace-accion enlace-accion--peligro">
                                Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>
