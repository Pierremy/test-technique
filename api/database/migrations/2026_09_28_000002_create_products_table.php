<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Une catégorie contenant des produits ne peut pas être supprimée.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name')->index();
            $table->string('sku', 64)->unique();
            // Decimal plutôt que float pour éviter les erreurs d'arrondi sur les prix.
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
