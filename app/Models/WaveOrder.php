<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class WaveOrder extends Model
{
    use HasFactory;

    public $connection = 'mysql3';
    public $timestamps = false;
    // public $primaryKey = 'id';
    protected $table = 'wave_orders';

    protected $fillable = [
        'order_id',
        'wave_session_id',
        'amount',
        'currency',
        'status',
        'wave_response',
        'paid_at',
        'payment_type',
        'contract_id',
        'number_of_primes',
        'selected_invoices',
        'preparation_context',
    ];




    protected $casts = [
        'wave_response'       => 'array',
        'selected_invoices'   => 'array',
        'preparation_context' => 'array',
        'paid_at'             => 'datetime',
    ];


    public function markAsPaid(): void
    {
        if ($this->status === 'succeeded') {
            return; // idempotence
        }

        $this->update([
            'status'  => 'succeeded',
            'paid_at' => now(),
        ]);
    }
}
