<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $data = [
            'namamenu' => 'Remunerasi',
            'link' => '/remunerasi',
            'icon' => 'cash-outline',
            'color' => '',
            'level' => 'all',
            'status' => 'drawer',
            'user' => 129,
        ];

        $menu = DB::table('mobilemenu')->where('link', '/remunerasi')->first();

        if ($menu) {
            DB::table('mobilemenu')->where('idmenu', $menu->idmenu)->update($data);

            return;
        }

        $data['idmenu'] = ((int) DB::table('mobilemenu')->max('idmenu')) + 1;
        DB::table('mobilemenu')->insert($data);
    }

    public function down(): void
    {
        DB::table('mobilemenu')->where('link', '/remunerasi')->delete();
    }
};
