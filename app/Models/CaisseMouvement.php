<?php

namespace App\Models;

use App\Models\Caisse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CaisseMouvement extends Model
{
    use SoftDeletes;
    
    protected $connection = 'mysql2';
    protected $table = 'caisse_mouvements';
    
    protected $fillable = [
        'uuid', 'reference', 'type', 'statut',
        'caisse_source_uuid', 'caisse_destination_uuid',
        'montant_envoye', 'frais', 'montant_recu',
        'date_envoi', 'date_reception',
        'envoye_par', 'recu_par',
        'confirmation_recu', 'justification_annulation', 'notes'
    ];
    
    protected $casts = [
        'montant_envoye' => 'decimal:2',
        'frais' => 'decimal:2',
        'montant_recu' => 'decimal:2',
        'date_envoi' => 'datetime',
        'date_reception' => 'datetime',
        'confirmation_recu' => 'boolean'
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
            $model->reference = self::generateReference();
        });
    }
    
    public static function generateReference()
    {
        $prefix = 'MVMT';
        $date = date('Ymd');
        $random = Str::upper(Str::random(6));
        return "{$prefix}-{$date}-{$random}";
    }
    
    public function caisseSource()
    {
        return $this->belongsTo(Caisse::class, 'caisse_source_uuid', 'uuid');
    }
    
    public function caisseDestination()
    {
        return $this->belongsTo(Caisse::class, 'caisse_destination_uuid', 'uuid');
    }
    
    public function envoyePar()
    {
        return $this->belongsTo(User::class, 'envoye_par', 'uuid');
    }
    
    public function recuPar()
    {
        return $this->belongsTo(User::class, 'recu_par', 'uuid');
    }
    
    public function updateCaissesSolde()
    {
        $caisseSource = Caisse::where('uuid', $this->caisse_source_uuid)->first();
        $caisseDestination = Caisse::where('uuid', $this->caisse_destination_uuid)->first();
        
        if ($caisseSource && $this->statut === 'EN_TRANSIT') {
            // Déduire de la source
            $caisseSource->solde_theorique -= $this->montant_envoye;
            $caisseSource->save();
        }
        
        if ($caisseDestination && $this->statut === 'RECU') {
            // Ajouter à la destination
            $montantRecu = $this->montant_recu ?? $this->montant_envoye;
            $caisseDestination->solde_theorique += $montantRecu;
            $caisseDestination->save();
        }
    }
}
