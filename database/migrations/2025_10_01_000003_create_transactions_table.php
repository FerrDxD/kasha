<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('account_id');
            $table->uuid('category_id')->nullable();
            $table->enum('type', ['income', 'expense', 'transfer'])->default('expense');
            $table->bigInteger('amount');
            $table->date('occurred_on');
            $table->string('payee')->nullable();
            $table->text('note')->nullable();
            $table->uuid('transfer_group_id')->nullable();
            $table->uuid('recurring_id')->nullable();
            $table->uuid('import_batch_id')->nullable();
            $table->string('external_hash', 64)->nullable();
            $table->boolean('is_reconciled')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('account_id')->references('id')->on('accounts');
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->index(['user_id', 'occurred_on']);
            $table->index(['user_id', 'account_id', 'occurred_on']);
            $table->index(['user_id', 'category_id', 'occurred_on']);
            // ponytail: partial unique index on external_hash; null rows excluded by DB
            $table->unique(['user_id', 'external_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
