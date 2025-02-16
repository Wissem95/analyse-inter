<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->date('date_intervention');
            $table->string('technicien');
            $table->string('type_intervention');  // SAV, RACC, PRESTA
            $table->string('type_operation')->nullable();  // raccordement, reconnexion
            $table->string('type_habitation')->nullable(); // immeuble, pavillon
            $table->decimal('prix', 10, 2);
            $table->decimal('revenus_percus', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interventions');
    }
};
