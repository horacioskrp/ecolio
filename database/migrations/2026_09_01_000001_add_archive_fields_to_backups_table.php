<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            // Archive d'année scolaire : snapshot verrouillé, exclu de la rétention automatique.
            $table->foreignUuid('academic_year_id')->nullable()->after('created_by')
                ->constrained('academic_years')->nullOnDelete();
            $table->string('label')->nullable()->after('academic_year_id');
            $table->boolean('locked')->default(false)->after('label');

            $table->index('locked');
        });
    }

    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_year_id');
            $table->dropColumn(['label', 'locked']);
        });
    }
};
