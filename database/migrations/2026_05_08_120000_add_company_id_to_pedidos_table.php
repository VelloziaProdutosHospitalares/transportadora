<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        $companyId = DB::table('companies')->orderBy('id')->value('id');

        if ($companyId === null && DB::table('pedidos')->exists()) {
            $now = now();
            $companyId = DB::table('companies')->insertGetId([
                'legal_name' => 'Empresa (cadastro inicial na migração)',
                'trade_name' => 'Empresa',
                'cnpj' => '00000000000191',
                'state_registration' => null,
                'phone' => '(11) 99999-9999',
                'email' => 'contato@exemplo.invalid',
                'postal_code' => '01310100',
                'street' => 'A conferir',
                'number' => 'S/N',
                'complement' => null,
                'district' => 'Centro',
                'city' => 'São Paulo',
                'state' => 'SP',
                'logo_path' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if ($companyId !== null) {
            DB::table('pedidos')->whereNull('company_id')->update(['company_id' => $companyId]);
        }

        Schema::table('pedidos', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
