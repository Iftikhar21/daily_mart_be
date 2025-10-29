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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelanggan_id')->nullable()->constrained('pelanggan');
            $table->foreignId('petugas_id')->nullable()->constrained('petugas');
            $table->foreignId('branch_id')->constrained('branches');
            $table->boolean('is_online')->default(true);
            $table->decimal('total', 12, 2);
            $table->enum('payment_method', ['cash', 'transfer', 'ewallet'])->nullable();
            $table->enum('status', ['pending', 'paid', 'completed', 'cancelled'])->default('pending');
            $table->string('nama_pembeli')->nullable();
            $table->text('alamat_pembeli')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
