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
        Schema::table('requisitions', function (Blueprint $table) {
            $table->string('number')->nullable()->after('id');
            $table->renameColumn('description', 'annotations');
        });

        // After renaming, we can change it to be nullable in a separate closure to avoid driver issues if sqlite is used
        Schema::table('requisitions', function (Blueprint $table) {
            $table->text('annotations')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requisitions', function (Blueprint $table) {
            $table->text('annotations')->nullable(false)->change();
        });

        Schema::table('requisitions', function (Blueprint $table) {
            $table->renameColumn('annotations', 'description');
            $table->dropColumn('number');
        });
    }
};
