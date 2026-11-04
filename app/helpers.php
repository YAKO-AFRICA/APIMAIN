<?php

use Illuminate\Database\Eloquent\SoftDeletes;

if (!function_exists('Refgenerate')) {
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
