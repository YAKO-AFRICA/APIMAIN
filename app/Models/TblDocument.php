<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblDocument extends Model
{
    use HasFactory;
    protected $table = 'tbldocuments';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'codecontrat',
        'filename',
        'libelle',
        'saisiele',
        'saisiepar',
        'source',
        'type',
    ];
}
