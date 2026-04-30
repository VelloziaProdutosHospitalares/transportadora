<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->unsignedBigInteger('octalog_id')->nullable()->after('url_etiqueta');
            $table->unsignedInteger('octalog_status_id')->nullable()->after('octalog_id');
            $table->string('octalog_status_text')->nullable()->after('octalog_status_id');
            $table->timestamp('octalog_status_at')->nullable()->after('octalog_status_text');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['octalog_id', 'octalog_status_id', 'octalog_status_text', 'octalog_status_at']);
        });
    }
};
