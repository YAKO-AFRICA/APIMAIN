<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Operator;
use Illuminate\Database\Seeder;

class OperatorSeeder  extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
     {
        $operators = [
            // Mobile Money
            ['code' => 'MTN', 'name' => 'MTN Mobile Money', 'category' => 'mobile_money'],
            ['code' => 'MOOV', 'name' => 'Moov Money', 'category' => 'mobile_money'],
            ['code' => 'DJAMO', 'name' => 'Djamo', 'category' => 'mobile_money'],
            ['code' => 'ORANGE', 'name' => 'Orange Money', 'category' => 'mobile_money'],
            ['code' => 'TRESOR', 'name' => 'Trésor Money', 'category' => 'mobile_money'],

            // MTO
            ['code' => 'MG', 'name' => 'MoneyGram', 'category' => 'mto'],
            ['code' => 'WU', 'name' => 'Western Union', 'category' => 'mto'],
            ['code' => 'RIA', 'name' => 'Ria Money Transfer', 'category' => 'mto'],
        ];

        foreach ($operators as $operator) {
            Operator::create($operator);
        }
    }
}
