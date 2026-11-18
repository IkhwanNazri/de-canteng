<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 12)->nullable()->unique()->after('avatar');
            $table->foreignId('referred_by_id')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
            $table->timestamp('referral_rewarded_at')->nullable()->after('referred_by_id');
            $table->unsignedInteger('stamps')->default(0)->after('referral_rewarded_at');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->unsignedInteger('commission_free_orders')->default(0)->after('is_verified');
            $table->foreignId('referred_by_shop_id')->nullable()->after('commission_free_orders')->constrained('shops')->nullOnDelete();
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->cascadeOnDelete(); // null = baucar platform
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); // baucar peribadi (cth ganjaran cop)
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('scope', 20);
            $table->string('discount_type', 10);
            $table->decimal('discount_value', 10, 2);
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->decimal('min_spend', 10, 2)->default(0);
            $table->unsignedInteger('quantity')->nullable(); // null = tiada had
            $table->unsignedInteger('claimed_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_voucher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unique(['voucher_id', 'product_id']);
        });

        Schema::create('user_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'voucher_id']);
        });

        Schema::create('stamp_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('stamps');
            $table->string('reason');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shop_discount', 10, 2)->default(0)->after('shipping_fee');
            $table->decimal('platform_discount', 10, 2)->default(0)->after('shop_discount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['shop_discount', 'platform_discount']));
        Schema::dropIfExists('stamp_entries');
        Schema::dropIfExists('user_vouchers');
        Schema::dropIfExists('product_voucher');
        Schema::dropIfExists('vouchers');
        Schema::table('shops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_shop_id');
            $table->dropColumn('commission_free_orders');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_id');
            $table->dropColumn(['referral_code', 'referral_rewarded_at', 'stamps']);
        });
    }
};
