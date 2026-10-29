<?php

use Illuminate\Database\Eloquent\SoftDeletes;

if (!function_exists('Refgenerate')) {
        // function Refgenerate($table, $prefix, $key)
        // {
        //     $latest = $table::orderBy('created_at', 'desc')->first();
        //     if (!$latest || !isset($latest->$key)) {
        //         return $prefix . '-00001';
        //     }

        //     $number = preg_replace("/[^0-9]/", '', $latest->$key);

        //     if (empty($number)) {
        //         $number = 0;
        //     }

        //     return $prefix . '-' . sprintf('%05d', $number + 1);
        // }

        // function Refgenerate($table, $prefix, $key)
        // {
        //     // Récupérer le plus grand numéro existant
        //     $latest = $table::where($key, 'like', $prefix . '-%')
        //         ->select($key)
        //         ->get()
        //         ->map(function ($item) use ($key) {
        //             return (int) preg_replace(
        //                 '/\D/',
        //                 '',
        //                 $item->$key
        //             );
        //         })
        //         ->max() ?? 0;

        //     $number = $latest + 1;

        //     // Vérifier que le code n'existe pas déjà
        //     do {
        //         $reference = $prefix . '-' . sprintf('%05d', $number);
        //         $number++;
        //     } while ($table::where($key, $reference)->exists());

        //     return $reference;
        // }

        function Refgenerate($model, $prefix, $key)
        {
            $query = $model::query();

            // Inclure les enregistrements supprimés logiquement
            if (in_array(
                SoftDeletes::class,
                class_uses_recursive($model)
            )) {
                $query->withTrashed();
            }

            // Rechercher le numéro le plus élevé existant
            $numbers = $query
                ->where($key, 'like', $prefix . '-%')
                ->pluck($key)
                ->map(function ($value) {
                    return (int) preg_replace('/[^0-9]/', '', $value);
                });

            $number = ($numbers->max() ?? 0) + 1;

            // Vérifier que la référence n'existe pas déjà
            do {
                $reference = $prefix . '-' . sprintf('%05d', $number);

                $existsQuery = $model::query();

                if (in_array(
                    SoftDeletes::class,
                    class_uses_recursive($model)
                )) {
                    $existsQuery->withTrashed();
                }

                $exists = $existsQuery
                    ->where($key, $reference)
                    ->exists();

                if ($exists) {
                    $number++;
                }
            } while ($exists);

            return $reference;
        }
    }

?>
