<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caisse extends Model
{
    use HasFactory;

    protected $table = 'caisses';

    protected $connection = 'mysql2';

    protected $fillable = [
        'uuid',
        'code',
        'libelle',
        'type',
        'solde',
        'solde_alert',
        'description',
        'isActive',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
