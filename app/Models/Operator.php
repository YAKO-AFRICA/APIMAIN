<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Operator extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql2';
    protected $table = 'operators';

    protected $fillable = [
        'uuid', 'code', 'name', 'category', 'logo_url', 'settings', 'isActive'
    ];

    protected $casts = [
        'settings' => 'array',
        'isActive' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'operator_uuid', 'uuid');
    }
}
