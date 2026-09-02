<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            // Empreinte SHA-256 du fichier généré : détection de corruption / bit-rot.
            $table->string('checksum', 64)->nullable()->after('size');
            // Le fichier embarque-t-il les médias (documents, photos) en plus de la base ?
            $table->boolean('includes_media')->default(false)->after('checksum');
        });
    }

    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropColumn(['checksum', 'includes_media']);
        });
    }
};
