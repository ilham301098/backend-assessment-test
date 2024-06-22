<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::factory(40)->create();
        \App\Models\DebitCard::factory(50)->create();
        \App\Models\DebitCardTransaction::factory(50)->create();
        \App\Models\Loan::factory(50)->create();
        \App\Models\ReceivedRepayment::factory(50)->create();
        \App\Models\ScheduledRepayment::factory(50)->create();
    }
}
