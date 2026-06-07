<?php

namespace App\Models;

use App\Models\Caisse;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CaisseEtat extends Model
{
    use SoftDeletes;
    
    protected $connection = 'mysql2';
    protected $table = 'caisse_etats';
    
    protected $fillable = [
        'uuid', 'caisse_uuid', 'date_journee', 'statut',
        'solde_initial', 'solde_theorique', 'solde_physique', 'ecart',
        'justification_ecart', 'date_ouverture', 'date_fermeture',
        'ouverte_par', 'fermee_par', 'verrouille_par',
        'est_verrouille', 'date_verrouillage', 'motif_verrouillage',
        'metadatas', 'notes'
    ];
    
    protected $casts = [
        'solde_initial' => 'decimal:2',
        'solde_theorique' => 'decimal:2',
        'solde_physique' => 'decimal:2',
        'ecart' => 'decimal:2',
        'date_ouverture' => 'datetime',
        'date_fermeture' => 'datetime',
        'date_verrouillage' => 'datetime',
        'est_verrouille' => 'boolean',
        'metadatas' => 'array'
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
    
    public function caisse()
    {
        return $this->belongsTo(Caisse::class, 'caisse_uuid', 'uuid');
    }
    
    public function ouvertePar()
    {
        return $this->belongsTo(User::class, 'ouverte_par', 'uuid');
    }
    
    public function fermeePar()
    {
        return $this->belongsTo(User::class, 'fermee_par', 'uuid');
    }
    
    public function verrouillePar()
    {
        return $this->belongsTo(User::class, 'verrouille_par', 'uuid');
    }
    
    public function getSoldeTheoriqueActuel()
    {
        // Calculer le solde théorique basé sur les transactions
        $totalEntrees = Transaction::where('caisse_uuid', $this->caisse_uuid)
            ->whereDate('created_at', $this->date_journee)
            ->where('statut', 'VALIDEE')
            ->where('sens', 'ENTREE')
            ->sum('montant_total');
            
        $totalSorties = Transaction::where('caisse_uuid', $this->caisse_uuid)
            ->whereDate('created_at', $this->date_journee)
            ->where('statut', 'VALIDEE')
            ->where('sens', 'SORTIE')
            ->sum('montant_total');
            
        return $this->solde_initial + $totalEntrees - $totalSorties;
    }
}
