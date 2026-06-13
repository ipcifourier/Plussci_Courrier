<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('report_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->foreignId('report_category_id')->nullable()->constrained('report_categories')->nullOnDelete();
            $table->text('description')->nullable();
            $table->longText('content_template')->nullable();
            $table->boolean('is_validated')->default(true);
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('report_category_id')->constrained('report_categories')->restrictOnDelete();
            $table->foreignId('report_template_id')->nullable()->constrained('report_templates')->nullOnDelete();
            $table->string('objet');
            $table->string('lieu')->nullable();
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->foreignId('organizer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('participants_json')->nullable();
            $table->foreignId('mission_courrier_id')->nullable()->constrained('courriers')->nullOnDelete();
            $table->foreignId('tdr_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->json('metadata_json')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['report_category_id', 'status']);
            $table->index('mission_courrier_id');
            $table->index('tdr_document_id');
        });

        $now = now();

        DB::table('report_categories')->insert([
            ['name' => 'Rapport de mission', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rapport d\'atelier', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rapport de reunion', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Compte rendu', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('report_templates');
        Schema::dropIfExists('report_categories');
    }
};
