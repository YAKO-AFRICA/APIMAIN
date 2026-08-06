<?php

namespace App\Models;

use App\Models\QrCode;
use App\Models\SuggestionTreatment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESuggestion extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'e_suggestions';


    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'code',
        'uuid_qrcode',
        'note',
        'uuid_category',
        'comment',
        'nom_client',
        'prenom_client',
        'tel_client',
        'email_client',
        'statut',
        'etat',
        'deleted_at',
        'deleted_by',
    ];

    public function treatments()
    {
        return $this->hasMany(SuggestionTreatment::class, 'uuid_suggestion', 'uuid');
    }

    public function qrCode()
    {
        return $this->belongsTo(QrCode::class, 'uuid_qrcode', 'uuid');
    }

}