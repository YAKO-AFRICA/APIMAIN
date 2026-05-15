<?php

namespace App\Models;

use App\Models\Membre;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contrat extends Model
{
    use HasFactory;

    protected $connection = 'mysql';

    protected $table = 'tblcontrat';
    public $timestamps = false;


    protected $fillable = [
        'id',
        'dateeffet',
        'modepaiement',
        'organisme',
        'prime',
        'primepricipale',
        'surprime',
        'capital',
        'etape',
        'numerocompte',

        'agence',
        'saisiele',
        'saisiepar',
        'codeConseiller',
        'nomagent',
        'duree',
        'periodicite',
        'codeadherent',
        'estMigre',
        'codeproduit',

        'transmisle',
        'annulerle',
        'accepterle',
        'modifierle',
        'modifierpar',
        'motifrejet',
        'libelleproduit',
        'montantrente',
        'periodiciterente',
        'dureerente',

        'mode_reserversement',
        'echeance_reversement',
        'duree_reversement',

        'personneressource',
        'contactpersonneressource',
        'beneficiaireauterme',
        'beneficiaireaudeces',
        'accepterpar',
        'rejeterpar',
        'transmispar',
        'personneressource2',
        'contactpersonneressource2',
        'codebanque',
        'codeguichet',
        'rib',
        'idproposition',
        'codeproposition',
        'branche',

        'partenaire',
        'nomaccepterpar',
        'refcontratsource',
        'cleintegration',
        'codeoperation',
        'numeropolice',
        'fraisadhesion',
        'estpaye',
        'pretconnexe',
        'details',
        'nomsouscipteur',
        'typesouscipteur',
        'numBullettin',
        'Formule'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'saisiepar', 'idmembre');
    }
    public function membre()
    {
        return $this->belongsTo(Membre::class, 'saisiepar', 'idmembre');
    }
}
