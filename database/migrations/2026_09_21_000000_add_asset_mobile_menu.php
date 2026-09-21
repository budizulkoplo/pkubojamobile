<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('mobilemenu')->updateOrInsert(
            ['idmenu' => 14],
            [
                'namamenu' => 'Aset',
                'link' => '/aset',
                'icon' => 'barcode-outline',
                'color' => '',
                'level' => 'it, radiologi',
                'status' => 'drawer',
                'user' => 129,
            ]
        );
    }

    public function down(): void
    {
        DB::table('mobilemenu')->where('idmenu', 14)->where('link', '/aset')->delete();
    }
};
