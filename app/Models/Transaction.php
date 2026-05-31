<?php

namespace App\Models;

use App\Models\Caisse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql2';
    protected $table = 'transactions';

    protected $fillable = [
        'uuid', 'reference', 'type', 'sens', 'montant', 'frais', 'montant_total',
        'caisse_uuid', 'operator_uuid', 'user_uuid', 'client_uuid',
        'numero_telephone', 'numero_carte', 'reference_transaction',
        'beneficiaire_nom', 'beneficiaire_telephone', 'beneficiaire_pays',
        'statut', 'justification_annulation', 'validated_by', 'validated_at', 'notes'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'frais' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'validated_at' => 'datetime'
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
        $prefix = 'TRX';
        $date = date('Ymd');
        $random = Str::upper(Str::random(6));
        return "{$prefix}-{$date}-{$random}";
    }

    public function caisse()
    {
        return $this->belongsTo(Caisse::class, 'caisse_uuid', 'uuid');
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_uuid', 'uuid');
    }

    public function updateCaisseSolde()
    {

    Log::info("debut function updateCaisseSolde");
        $caisse = Caisse::where('uuid', $this->caisse_uuid)->first();
        Log::info("fin function updateCaisseSolde");

        Log::info($caisse);

        if ($caisse) {
            if ($this->sens === 'ENTREE') {
                $caisse->solde_theorique += $this->montant_total;
            } else {
                $caisse->solde_theorique -= $this->montant_total;
            }

            $caisse->last_transaction_at = now();
            $caisse->save();
        }
    }
}
