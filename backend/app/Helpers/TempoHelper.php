<?php

declare(strict_types=1);

namespace App\Helpers;

class TempoHelper
{
    /**
     * Converte minutos em representação textual humanizada (ex: "2h 30min", "1h", "45min").
     */
    public static function minutosParaTexto(int $minutos): string
    {
        $h = intdiv($minutos, 60);
        $m = $minutos % 60;

        if ($h > 0 && $m > 0) {
            return "{$h}h {$m}min";
        }
        if ($h > 0) {
            return "{$h}h";
        }
        return "{$m}min";
    }

    /**
     * Converte minutos em horas decimais com precisão configurável (ex: 90 min -> 1.5).
     */
    public static function minutosParaHorasDecimais(int $minutos, int $casasDecimais = 1): float
    {
        return round($minutos / 60.0, $casasDecimais);
    }
}
