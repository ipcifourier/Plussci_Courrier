<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intervention_domains', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('intervention_subdomains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('intervention_domain_id')
                ->constrained('intervention_domains')
                ->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['intervention_domain_id', 'name']);
        });

        Schema::create('gtts', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignId('intervention_domain_id')
                ->nullable()
                ->after('type_document')
                ->constrained('intervention_domains')
                ->nullOnDelete();

            $table->foreignId('intervention_subdomain_id')
                ->nullable()
                ->after('intervention_domain_id')
                ->constrained('intervention_subdomains')
                ->nullOnDelete();

            $table->foreignId('gtt_id')
                ->nullable()
                ->after('intervention_subdomain_id')
                ->constrained('gtts')
                ->nullOnDelete();
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('gtt_id');
            $table->dropConstrainedForeignId('intervention_subdomain_id');
            $table->dropConstrainedForeignId('intervention_domain_id');
        });

        Schema::dropIfExists('gtts');
        Schema::dropIfExists('intervention_subdomains');
        Schema::dropIfExists('intervention_domains');
    }

    private function seedDefaults(): void
    {
        $domainMap = [
            'PREVENTION' => [
                'Sensibilisation',
                'Formation',
                'Planification preventive',
            ],
            'DETECTION' => [
                'Surveillance',
                'Alerte precoce',
                'Analyse et verification',
            ],
            'RIPOSTE' => [
                'Coordination operationnelle',
                'Intervention terrain',
                'Suivi post-intervention',
            ],
            'AUTRES RISQUES' => [
                'Evaluation transversale',
                'Risque emergent',
                'Appui multisectoriel',
            ],
        ];

        foreach ($domainMap as $domainName => $subdomains) {
            $domainId = DB::table('intervention_domains')->insertGetId([
                'name' => $domainName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($subdomains as $subdomainName) {
                DB::table('intervention_subdomains')->insert([
                    'intervention_domain_id' => $domainId,
                    'name' => $subdomainName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $defaultGtts = [
            'GTT 1',
            'GTT 2',
            'GTT 3',
            'GTT 4',
            'GTT 5',
            'GTT 6',
            'GTT 7',
            'GTT 8',
            'GTT 9',
            'GTT 10',
        ];

        foreach ($defaultGtts as $gttName) {
            DB::table('gtts')->insert([
                'name' => $gttName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
