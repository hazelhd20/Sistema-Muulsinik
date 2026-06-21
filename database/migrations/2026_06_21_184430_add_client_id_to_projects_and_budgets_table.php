<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Client;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add client_id columns
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('client')->constrained('clients')->nullOnDelete();
        });

        Schema::table('quick_budgets', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('client')->constrained('clients')->nullOnDelete();
        });

        // 2. Migrate existing text data to the clients table
        $projects = DB::table('projects')->whereNotNull('client')->where('client', '!=', '')->get();
        foreach ($projects as $project) {
            $client = Client::firstOrCreate(['name' => trim($project->client)]);
            DB::table('projects')->where('id', $project->id)->update(['client_id' => $client->id]);
        }

        $budgets = DB::table('quick_budgets')->whereNotNull('client')->where('client', '!=', '')->get();
        foreach ($budgets as $budget) {
            $client = Client::firstOrCreate(['name' => trim($budget->client)]);
            DB::table('quick_budgets')->where('id', $budget->id)->update(['client_id' => $client->id]);
        }

        // 3. Drop the old string columns
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('client');
        });

        Schema::table('quick_budgets', function (Blueprint $table) {
            $table->dropColumn('client');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add string columns back
        Schema::table('projects', function (Blueprint $table) {
            $table->string('client')->nullable()->after('client_id');
        });

        Schema::table('quick_budgets', function (Blueprint $table) {
            $table->string('client')->nullable()->after('client_id');
        });

        // Migrate data back
        $projects = DB::table('projects')->whereNotNull('client_id')->get();
        foreach ($projects as $project) {
            $client = DB::table('clients')->where('id', $project->client_id)->first();
            if ($client) {
                DB::table('projects')->where('id', $project->id)->update(['client' => $client->name]);
            }
        }

        $budgets = DB::table('quick_budgets')->whereNotNull('client_id')->get();
        foreach ($budgets as $budget) {
            $client = DB::table('clients')->where('id', $budget->client_id)->first();
            if ($client) {
                DB::table('quick_budgets')->where('id', $budget->id)->update(['client' => $client->name]);
            }
        }

        // Drop relations
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });

        Schema::table('quick_budgets', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};
