<?php
/**
 * Vista: layout/header.php
 *
 * Encabezado HTML compartido por todas las páginas del CRUD.
 * Es incluido por SolicitudCreditoController::renderizar() antes de
 * cada vista concreta, por lo que las variables $titulo y
 * $mensajeFlash siempre están disponibles aquí.
 *
 * El maquetado (barra superior oscura, logo en mayúsculas, botón de
 * acento dorado) está inspirado deliberadamente en la estética de
 * vopm.net: fondo oscuro en la cabecera, tipografía en mayúsculas
 * con espaciado entre letras, y un color de acento cálido para las
 * llamadas a la acción.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Solicitudes de credito', ENT_QUOTES, 'UTF-8') ?> &mdash; VOPM CRUD Demo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="sitio-header">
    <div class="contenedor sitio-header__interior">
        <a href="index.php" class="marca">
            VOPM<span class="marca__acento">.</span>
        </a>

        <nav class="nav-principal">
            <a href="index.php">Solicitudes</a>
            <a href="index.php?action=create" class="boton boton--primario boton--pequeno">Nueva solicitud</a>
        </nav>
    </div>
</header>

<main class="contenido-principal">
    <div class="contenedor">

        <?php
        // Si hay un mensaje flash pendiente (guardado por el
        // controlador justo antes de una redirección), se muestra
        // una sola vez aquí, arriba de cualquier vista.
        if ($mensajeFlash !== null): ?>
            <div class="alerta alerta--<?= htmlspecialchars($mensajeFlash['tipo'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($mensajeFlash['texto'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
