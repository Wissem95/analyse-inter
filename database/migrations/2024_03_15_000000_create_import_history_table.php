<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_history', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->integer('records_count');
            $table->timestamp('import_date');
            $table->string('status'); // 'success', 'partial', 'failed'
            $table->jsonb('errors')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_history');
    }
};
