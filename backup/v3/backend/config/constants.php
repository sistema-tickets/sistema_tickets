<?php

// Roles (tabla roles)
const ROL_USUARIO    = 1;
const ROL_ADMIN      = 2;
const ROL_SUPERADMIN = 3;

// Estados (tabla estados)
const ESTADO_EN_ESPERA           = 1;
const ESTADO_EN_PROCESO          = 2;
const ESTADO_ESPERANDO_RESPUESTA = 3;
const ESTADO_CONTESTADO          = 4;
const ESTADO_REABIERTO           = 5;
const ESTADO_REPROGRAMADO        = 6;
const ESTADO_ATENDIDO            = 7;
const ESTADO_CERRADO             = 8;
const ESTADO_RECHAZADO           = 9;

// Prioridades (tabla prioridades)
const PRIORIDAD_BAJA     = 1;
const PRIORIDAD_MEDIA    = 2;
const PRIORIDAD_ALTA     = 3;

// Colores por estado (para el frontend)
const ESTADO_COLORES = [
    ESTADO_EN_ESPERA           => 'gray',
    ESTADO_EN_PROCESO          => 'blue',
    ESTADO_ESPERANDO_RESPUESTA => 'yellow',
    ESTADO_CONTESTADO          => 'green',
    ESTADO_REABIERTO           => 'orange',
    ESTADO_REPROGRAMADO        => 'purple',
    ESTADO_ATENDIDO            => 'teal',
    ESTADO_CERRADO             => 'dark',
    ESTADO_RECHAZADO           => 'red',
];

// Colores por prioridad
const PRIORIDAD_COLORES = [
    PRIORIDAD_BAJA    => 'green',
    PRIORIDAD_MEDIA   => 'yellow',
    PRIORIDAD_ALTA    => 'orange',
];

// Tamaño máximo de adjuntos (5 MB)
const MAX_UPLOAD_SIZE = 5 * 1024 * 1024;
const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'];
