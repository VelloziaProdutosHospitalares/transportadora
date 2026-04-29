<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20);
            $table->text('external_url')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();

            $table->index(['pedido_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_labels');
    }
};
