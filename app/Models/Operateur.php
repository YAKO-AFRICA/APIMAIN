<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Operateur extends Model
{
    use HasFactory;

    protected $table = 'operateurs';

    protected $connection = 'mysql2';

    protected $fillable = [
        'uuid',
        'code',
        'libelle',
        'etat',
    ];

     protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
