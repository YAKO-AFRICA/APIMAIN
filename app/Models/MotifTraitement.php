<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotifTraitement extends Model
{
    use HasFactory;

    protected $table = "motif_traitements";

    protected $fillable = [
        'uuid',
        'code',
        'libelle',
        'systeme_used',
        'description',
        'etat'
    ];
}
