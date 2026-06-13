<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_path')->nullable()->after('poste');
            $table->date('hire_date')->nullable()->after('avatar_path');
            $table->string('phone', 30)->nullable()->after('hire_date');
            $table->string('personal_email')->nullable()->after('phone');
            $table->string('address')->nullable()->after('personal_email');
            $table->text('bio')->nullable()->after('address');
            $table->string('cv_path')->nullable()->after('bio');
            $table->json('preferences')->nullable()->after('cv_path');
            $table->unsignedSmallInteger('inactivity_timeout_minutes')->nullable()->after('preferences');
            $table->timestamp('last_password_changed_at')->nullable()->after('inactivity_timeout_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'avatar_path',
                'hire_date',
                'phone',
                'personal_email',
                'address',
                'bio',
                'cv_path',
                'preferences',
                'inactivity_timeout_minutes',
                'last_password_changed_at',
            ]);
        });
    }
};
