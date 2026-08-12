<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evisite extends Model
{
    use HasFactory;

    protected $table = 'evisites';

    protected $fillable = [
        'uuid', 'code', 'nom', 'prenoms', 'mobile', 'email', 'motif_uuid', 'personne_visite', 'date_de_visite', 'nature_piece', 'num_piece', 'notes', 'agence'
    ];
}
