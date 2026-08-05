<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuggestionTreatment extends Model
{
    use HasFactory;

    protected $table = 'suggestion_treatments';

    protected $primaryKey = 'uuid';
    public $incrementing = false;

    protected $fillable = [
        'uuid',
        'code',
        'uuid_suggestion',
        'code_responsable',
        'action',
        'assigned_by',
        'etat',
    ];
}