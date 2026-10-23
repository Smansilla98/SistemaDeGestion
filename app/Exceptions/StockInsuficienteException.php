<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Falta stock para vender. Es un RuntimeException a propósito: los controladores
 * muestran el mensaje tal cual al mozo ("Stock insuficiente para 'Coca Cola'...").
 * Con un \Exception genérico el mensaje quedaba oculto detrás de un "reintentá".
 */
class StockInsuficienteException extends RuntimeException {}
