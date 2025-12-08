<?php

namespace App\Models;

use App\Models\Rapport;
use App\Models\TypeOperation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RapportOperation extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';

    protected $fillable = [
        'uuid',
        'code',
        'rapport_uuid',
        'type_operation_uuid',
        'quantite',
        'montant_unitaire',
        'montant_total',
        'nature',
        'produit_assurance',
        'prime_souhaitee',
        'code_contrat',
        'client_a_paye',

        'type_category',
        'type_mouvement',
        'nb_agents_terrain',
        'nb_agents_commerciaux',
        'nb_souscriptions_hors_agence',
        'nb_souscriptions_en_agence',
        'nb_souscriptions',
        'nb_personnes',
        'taux_satisfaction',
        'description',
        'isActive'
    ];

    protected $casts = [
        'montant_unitaire' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'prime_souhaitee' => 'decimal:2',
        'client_a_paye' => 'boolean',
        'isActive' => 'boolean',
    ];

    // Corriger la relation pour utiliser la bonne connexion
    public function typeOperation(): BelongsTo
    {
        return $this->belongsTo(TypeOperation::class, 'type_operation_uuid', 'uuid');
    }

    public function rapport(): BelongsTo
    {
        return $this->belongsTo(Rapport::class, 'rapport_uuid', 'uuid');
    }
}