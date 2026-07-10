<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblFacture extends Model
{
    use HasFactory;

    protected $connection = 'mysql3';
    protected $table = 'tblfacture';
    protected $primaryKey = 'idFacture';
    public $timestamps = false;
 
    protected $fillable = [
        'idFacture',
        'idProposition',
        'codePaiement',
        'prime',
        'typeFacture',
        'etat',
        'dateAjout',
        'typePaiement',
        'referenceSource',
        'idcontrat',
        'saisiele',
    ];
}
