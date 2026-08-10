<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZoneByUser extends Model
{
    use HasUuids;

    use HasFactory;

    protected $table = 'zone_by_users';

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uuid',
        'libelle',
        'code',
        'responsable_uuid',
        'agence_codes'
    ];

    protected $casts = [
        'agence_codes' => 'array',
    ];

    // Relation avec l'utilisateur
    public function user()
    {
        return $this->belongsTo(User::class, 'responsable_uuid', 'idmembre');
    }



}