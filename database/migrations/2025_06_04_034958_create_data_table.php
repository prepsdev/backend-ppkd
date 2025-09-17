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
        Schema::create('data', function (Blueprint $table) {
            $table->id();
            $table->string('tema');
            $table->string('topik');
            $table->string('topik_uri');
            $table->string('indikator');
            $table->string('indikator_uri');
            $table->string('regional');
            $table->string('provinsi');
            $table->string('kota')->nullable();
            $table->string('fieldName');
            $table->string('dataValue');
            $table->string('satuan')->nullable();
            $table->string('sumber')->nullable();;
            $table->string('lastupdate')->nullable();
            $table->longtext('deskripsi')->nullable();
            $table->year('tahun');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data');
    }
};
