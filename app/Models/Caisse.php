<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caisse extends Model
{
    use HasFactory;

    protected $connection = 'mysql2';
    protected $table = 'caisses';


    protected $fillable = [
        'uuid',
        'code',
        'libelle',
        'type',
        'solde',
        'solde_alert',
        'solde_theorique',
        'solde_physique',
        'last_transaction_at',
        'description',
        'isActive',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
