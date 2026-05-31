<?php

if (!function_exists('Refgenerate')) {
        function Refgenerate($table, $prefix, $key)
        {
            $latest = $table::orderBy('id', 'desc')->first();
            if (!$latest || !isset($latest->$key)) {
                return $prefix . '-00001';
            }

            $number = preg_replace("/[^0-9]/", '', $latest->$key);

            if (empty($number)) {
                $number = 0;
            }

            return $prefix . '-' . sprintf('%05d', $number + 1);
        }
    }

?>
