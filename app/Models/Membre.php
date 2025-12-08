<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membre extends Model
{
    use HasFactory;

    protected $table = 'membre';

    protected $primaryKey = 'idmembre';

    public $incrementing = true;

    protected $fillable = [
        'idmembre',
        'id_session',
        'nom',
        'prenom',
        'cel',
        'tel',
        'pays',
        'ville',
        'email',
        'login',
        'pass',
        'date',
        'datemodif',
        'token',
        'enligne',
        'lastvisite',
        'nbrevisite',
        'memberok',
        'droits',
        'navigation_securise',
        'photo',
        'codeagent',
        'typ_membre',
        'activer',
        'branche',
        'partenaire',
        'codepartenaire',
        'agence',
        'datenaissance',
        'lieuresidence',
        'lieunaissance',
        'profession',
        'codereseau',
        'codezone',
        'codeequipe',
        'role',
        'coderole',
        'sexe',
        'cel2',
        'nomagence',
        'passmodifier',
        'passmodifierle',
        'estajour',
        'datevalidite',
        'paiementok',
        'lastpaiement',
        'devis',
        'isemploye',
        'isbranmaster',
        'ispartmaster',
        'isadmin',
        'user_parent',
        'updated_by',
        'created_by'
    ];
}
