<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TblReseauProduct extends Model
{
    use HasFactory;

    protected $table = 'tblreseauproduit';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'codereseau',
        'codeproduit',
        'codeproduitformule',
        'estactif',
        'libelleproduit',
    ];
}
