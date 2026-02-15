<?php

if (!function_exists('money')) {
    function money($value): string
    {
        $v = (int)($value ?? 0);
        return number_format($v, 0, '.', ' ');
    }
}
