<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courriers', function (Blueprint $table): void {
            $table->foreignId('signed_by')->nullable()->after('current_approval_level')->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable()->after('signed_by');
            $table->text('signature_comment')->nullable()->after('signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('courriers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('signed_by');
            $table->dropColumn(['signed_at', 'signature_comment']);
        });
    }
};
