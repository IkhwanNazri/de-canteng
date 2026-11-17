<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('sizes')->nullable()->after('stock');
            $table->json('colors')->nullable()->after('sizes');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('size', 20)->nullable()->after('product_id');
            $table->string('color', 30)->nullable()->after('size');
            // Tambah indeks baharu dulu supaya foreign key user_id sentiasa ada indeks (MySQL).
            $table->unique(['user_id', 'product_id', 'size', 'color'], 'cart_items_variant_unique');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->string('size', 20)->nullable()->after('product_name');
            $table->string('color', 30)->nullable()->after('size');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn(['size', 'color']));

        Schema::table('cart_items', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_variant_unique');
            $table->dropColumn(['size', 'color']);
        });

        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['sizes', 'colors']));
    }
};
