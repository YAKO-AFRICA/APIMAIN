<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblPaiement extends Model
{
    use HasFactory;
    public $connection = 'mysql3';
    public $timestamps = false;
    public $primaryKey = 'idPaiment';
    protected $table = 'tblpaiement';
    protected $fillable = [
        'idPaiment',
        'codePaiement',
        'montant',
        'telpaiement',
        'etat',
        'datepaiement',
        'payment_mode',
        'paid_sum',
        'paid_amount',
        'payment_token',
        'payment_status',
        'command_number',
        'payment_validation_date',
        'typePaiement',
        'typeReglement',
        'idproposition',
        'idContrat',
        'referenceSource',
        'nombreDePrime',
        'num_souscripteur',
        'frais_adhesion',
        'code_produit',
        'idmembre',
        'emailpayeur',
        'saisiele',
        'estMigre',
        'reponse_webhook'

    ];
}
