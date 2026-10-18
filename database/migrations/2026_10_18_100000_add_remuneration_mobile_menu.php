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

        $db = DB::connection('hris');
        $menu = $db->table('mobilemenu')->where('link', '/remunerasi')->first();

        if ($menu) {
            $db->table('mobilemenu')->where('idmenu', $menu->idmenu)->update($data);

            return;
        }

        $data['idmenu'] = ((int) $db->table('mobilemenu')->max('idmenu')) + 1;
        $db->table('mobilemenu')->insert($data);
    }

    public function down(): void
    {
        DB::connection('hris')->table('mobilemenu')->where('link', '/remunerasi')->delete();
    }
};
