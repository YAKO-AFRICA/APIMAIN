<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rapport extends Model
{
    use HasFactory;

    protected $connection = 'mysql2';

    protected $fillable = [
        'uuid',
        'code',
        'date_rapport',
        'total_entrees',
        'total_sorties',
        'solde',
        'observations',
        'isActive',
        'user_id'
    ];

    protected $casts = [
        'date_rapport' => 'date',
        'total_entrees' => 'decimal:2',
        'total_sorties' => 'decimal:2',
        'solde' => 'decimal:2',
        'isActive' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(RapportOperation::class, 'rapport_uuid', 'uuid');
    }

    public function calculateTotals(): void
    {
        $entrees = $this->operations()
            ->where('nature', 'entree')
            ->where('isActive', true)
            ->sum('montant_total');

        $sorties = $this->operations()
            ->where('nature', 'sortie')
            ->where('isActive', true)
            ->sum('montant_total');

        $this->total_entrees = $entrees;
        $this->total_sorties = $sorties;
        $this->solde = $entrees - $sorties;
        $this->save();
    }
}