<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_sessions', function (Blueprint $table) {
            $table->foreignId('pinned_product_id')->nullable()->after('cover')->constrained('products')->nullOnDelete();
            $table->boolean('auto_reply')->default(true)->after('pinned_product_id');
        });

        Schema::create('live_session_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unique(['live_session_id', 'product_id']);
        });

        Schema::create('live_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('body', 500);
            $table->boolean('is_auto_reply')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_comments');
        Schema::dropIfExists('live_session_product');
        Schema::table('live_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pinned_product_id');
            $table->dropColumn('auto_reply');
        });
    }
};
