<?php
/**
 * Vista: solicitudes/form.php
 *
 * Formulario compartido tanto para crear una solicitud nueva como
 * para editar una existente. Cuál de los dos modos está activo se
 * decide con la variable $modo ('crear' | 'editar').
 *
 * Variables recibidas desde SolicitudCreditoController:
 *
 * @var string                                        $modo           'crear' o 'editar'.
 * @var \App\Domain\Entity\SolicitudCredito|null       $solicitud      La solicitud a editar (null en modo 'crear').
 * @var array<string, string>                          $errores        Errores de validacion, indexados por campo.
 * @var array<string, mixed>                            $valoresPrevios Valores que el usuario ya habia escrito
 *                                                                       antes de que la validacion fallara.
 */

use App\Domain\Entity\SolicitudCredito;

/**
 * Obtiene el valor que debe mostrarse en un campo del formulario,
 * respetando este orden de prioridad:
 *
 *   1. Lo que el usuario acababa de escribir (si la validacion falló).
 *   2. Lo que ya tenía guardado la solicitud (si se está editando).
 *   3. Una cadena vacía, si no aplica ninguno de los casos anteriores.
 *
 * Nota de diseño: $valoresPrevios y $solicitud se reciben como
 * parámetros explícitos (no con la palabra clave "global") porque el
 * controlador las inyecta en la vista mediante extract(), lo que las
 * deja disponibles solo en el ámbito local de este archivo, no en el
 * ámbito global real de PHP. Pedirlas como parámetros es, además,
 * más claro y evita depender de "global", que suele considerarse
 * mala práctica.
 *
 * @param string                     $campo          Nombre del campo dentro del formulario.
 * @param array<string, mixed>       $valoresPrevios  Valores escritos previamente por el usuario.
 * @param SolicitudCredito|null      $solicitud       Solicitud en edición, o null si se está creando.
 *
 * @return string
 */
if (!function_exists('valorDelCampo')) {
    // La declaracion se protege con function_exists() porque, con un
    // servidor persistente (PHP-FPM, Apache + mod_php), un mismo
    // proceso puede atender varias peticiones seguidas y este archivo
    // se vuelve a incluir en cada una; sin este guard, la segunda
    // peticion fallaria con un error de "no se puede redeclarar la
    // funcion". Con el servidor embebido de desarrollo esto no pasa
    // (cada peticion es un proceso nuevo), pero se protege igual para
    // que el codigo sea seguro en cualquier entorno.
    function valorDelCampo(string $campo, array $valoresPrevios, ?SolicitudCredito $solicitud): string
    {
        if (array_key_exists($campo, $valoresPrevios)) {
            return (string) $valoresPrevios[$campo];
        }

        if ($solicitud !== null) {
            return match ($campo) {
                'nombreCompleto' => $solicitud->nombreCompleto(),
                'cedula' => $solicitud->cedula(),
                'correo' => $solicitud->correo(),
                'telefono' => $solicitud->telefono(),
                'montoSolicitado' => (string) $solicitud->montoSolicitado(),
                'plazoMeses' => (string) $solicitud->plazoMeses(),
                'estado' => $solicitud->estado(),
                default => '',
            };
        }

        return '';
    }
}
?>

<div class="encabezado-seccion">
    <div>
        <p class="etiqueta-superior">VOPM &middot; Ecosistema de credito</p>
        <h1><?= $modo === 'crear' ? 'Nueva solicitud de credito' : 'Editar solicitud de credito' ?></h1>
        <p class="texto-secundario">
            Todos los campos son obligatorios. La cedula y el telefono se
            validan automaticamente.
        </p>
    </div>
    <a href="index.php" class="boton boton--secundario">Volver al listado</a>
</div>

<div class="tarjeta tarjeta--formulario">
    <form
        method="post"
        action="<?= $modo === 'crear'
            ? 'index.php?action=store'
            : 'index.php?action=update&id=' . (int) $solicitud->id() ?>"
    >
        <div class="rejilla-formulario">

            <div class="campo">
                <label for="nombreCompleto">Nombre completo</label>
                <input
                    type="text"
                    id="nombreCompleto"
                    name="nombreCompleto"
                    value="<?= htmlspecialchars(valorDelCampo('nombreCompleto', $valoresPrevios, $solicitud), ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Ej. Maria Fernandez Reyes"
                >
                <?php if (isset($errores['nombreCompleto'])): ?>
                    <p class="mensaje-error"><?= htmlspecialchars($errores['nombreCompleto'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>

            <div class="campo">
                <label for="cedula">Cedula</label>
                <input
                    type="text"
                    id="cedula"
                    name="cedula"
                    value="<?= htmlspecialchars(valorDelCampo('cedula', $valoresPrevios, $solicitud), ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="000-0000000-0"
                >
                <?php if (isset($errores['cedula'])): ?>
                    <p class="mensaje-error"><?= htmlspecialchars($errores['cedula'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>

            <div class="campo">
                <label for="correo">Correo electronico</label>
                <input
                    type="email"
                    id="correo"
                    name="correo"
                    value="<?= htmlspecialchars(valorDelCampo('correo', $valoresPrevios, $solicitud), ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="cliente@correo.com"
                >
                <?php if (isset($errores['correo'])): ?>
                    <p class="mensaje-error"><?= htmlspecialchars($errores['correo'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>

            <div class="campo">
                <label for="telefono">Telefono</label>
                <input
                    type="text"
                    id="telefono"
                    name="telefono"
                    value="<?= htmlspecialchars(valorDelCampo('telefono', $valoresPrevios, $solicitud), ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="809-000-0000"
                >
                <?php if (isset($errores['telefono'])): ?>
                    <p class="mensaje-error"><?= htmlspecialchars($errores['telefono'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>

            <div class="campo">
                <label for="montoSolicitado">Monto solicitado (RD$)</label>
                <input
                    type="number"
                    step="0.01"
                    id="montoSolicitado"
                    name="montoSolicitado"
                    value="<?= htmlspecialchars(valorDelCampo('montoSolicitado', $valoresPrevios, $solicitud), ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="50000"
                >
                <?php if (isset($errores['montoSolicitado'])): ?>
                    <p class="mensaje-error"><?= htmlspecialchars($errores['montoSolicitado'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>

            <div class="campo">
                <label for="plazoMeses">Plazo (meses)</label>
                <input
                    type="number"
                    id="plazoMeses"
                    name="plazoMeses"
                    value="<?= htmlspecialchars(valorDelCampo('plazoMeses', $valoresPrevios, $solicitud), ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="12"
                >
                <?php if (isset($errores['plazoMeses'])): ?>
                    <p class="mensaje-error"><?= htmlspecialchars($errores['plazoMeses'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>

            <?php
            // El estado de la solicitud solo se puede elegir cuando se
            // esta editando; al crear, siempre inicia en "Pendiente"
            // por decision del servicio de aplicacion.
            if ($modo === 'editar'): ?>
                <div class="campo">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <?php foreach (SolicitudCredito::ESTADOS_VALIDOS as $estadoDisponible): ?>
                            <option
                                value="<?= htmlspecialchars($estadoDisponible, ENT_QUOTES, 'UTF-8') ?>"
                                <?= valorDelCampo('estado', $valoresPrevios, $solicitud) === $estadoDisponible ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($estadoDisponible, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errores['estado'])): ?>
                        <p class="mensaje-error"><?= htmlspecialchars($errores['estado'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>

        <div class="acciones-formulario">
            <a href="index.php" class="boton boton--secundario">Cancelar</a>
            <button type="submit" class="boton boton--primario">
                <?= $modo === 'crear' ? 'Registrar solicitud' : 'Guardar cambios' ?>
            </button>
        </div>
    </form>
</div>
