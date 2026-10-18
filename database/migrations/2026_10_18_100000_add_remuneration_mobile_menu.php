<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('mobilemenu')->updateOrInsert(
            ['idmenu' => 15],
            [
                'namamenu' => 'Remunerasi',
                'link' => '/remunerasi',
                'icon' => 'cash-outline',
                'color' => '',
                'level' => 'all',
                'status' => 'drawer',
                'user' => 129,
            ]
        );
    }

    public function down(): void
    {
        DB::table('mobilemenu')
            ->where('idmenu', 15)
            ->where('link', '/remunerasi')
            ->delete();
    }
};
