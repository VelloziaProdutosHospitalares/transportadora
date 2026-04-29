<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_pedido')->unique();
            $table->string('chave_nf', 44)->nullable();
            $table->string('numero_nf', 20);
            $table->string('serie_nf', 3);
            $table->decimal('valor_total', 10, 2);
            $table->unsignedInteger('total_volumes')->default(1);
            $table->unsignedInteger('id_prazo_entrega');
            $table->enum('status', ['pendente', 'enviado', 'erro'])->default('pendente');
            $table->json('octalog_response')->nullable();
            $table->text('url_etiqueta')->nullable();
            $table->text('erro_mensagem')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
