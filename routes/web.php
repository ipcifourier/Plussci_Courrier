<?php

use App\Http\Controllers\CourrierRegistrePdfController;
use App\Http\Controllers\CourrierExportController;
use App\Http\Controllers\GedExportController;
use App\Http\Controllers\AgendaWeeklySummaryPdfController;
use App\Http\Controllers\AgendaExportController;
use App\Http\Controllers\AuditLogExportController;
use App\Http\Controllers\ChatAttachmentController;
use App\Http\Controllers\GttBureauMemberExportController;
use App\Http\Controllers\DocumentPresenceController;
use App\Http\Controllers\DocumentShareController;
use App\Http\Controllers\OnlyOfficeController;
use App\Http\Controllers\ProfileDownloadController;
use App\Http\Controllers\FileManagerSyncController;
use App\Http\Controllers\FileManagerOfflineTaskController;
use App\Http\Middleware\RefreshPermissionCache;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route export-test sans préfixe pour diagnostic
Route::get('/export-test', CourrierExportController::class)->name('export.test');

// Route export sans préfixe admin pour test
Route::get('/courriers/export', CourrierExportController::class)->name('courriers.export.test');

// Route d'export test sans middleware pour diagnostic
Route::get('/test-export', CourrierExportController::class);

// Partage de documents — accès public par token (sans authentification)

Route::prefix('share')->name('share.')->group(function (): void {
    Route::get('/{token}', [DocumentShareController::class, 'show'])
        ->name('show')
        ->middleware('throttle:30,1');

    Route::get('/{token}/download/{mediaId}', [DocumentShareController::class, 'download'])
        ->name('download')
        ->middleware('throttle:10,1');
});

// Export routes — throttled to 10 requests/minute per authenticated user

Route::middleware(['auth', 'throttle:60,1'])
    ->group(function (): void {
        Route::get('/admin/courriers/registre-pdf', CourrierRegistrePdfController::class)
            ->name('courriers.registre.pdf');

// Route export sans middleware pour test
Route::get('/admin/courriers/export', CourrierExportController::class)
    ->name('courriers.export');

        Route::get('/admin/ged/{resource}/export', GedExportController::class)
            ->whereIn('resource', ['documents', 'dossiers'])
            ->name('ged.export');

        Route::get('/admin/audit-logs/actions/export', AuditLogExportController::class)
            ->middleware(RefreshPermissionCache::class)
            ->name('audit.logs.export');

        Route::get('/admin/audit-logs/actions/export-pdf', \App\Http\Controllers\AuditLogPdfExportController::class)
            ->middleware(RefreshPermissionCache::class)
            ->name('audit.logs.export.pdf');

        Route::get('/admin/agenda/{resource}/export', AgendaExportController::class)
            ->whereIn('resource', ['appointments', 'meetings', 'meeting-tasks', 'visits'])
            ->name('agenda.export');

        Route::get('/admin/agenda/ical', \App\Http\Controllers\AgendaIcalExportController::class)
            ->name('agenda.ical');

        // C3 — Feuille de signature courrier
        Route::get('/admin/courriers/{courrier}/signature-sheet', \App\Http\Controllers\CourrierSignatureSheetController::class)
            ->name('courriers.signature.pdf');

        Route::get('/admin/agenda/synthese-hebdo.pdf', AgendaWeeklySummaryPdfController::class)
            ->name('agenda.synthese.pdf');

        Route::get('/admin/gtts/{gtt}/bureau-members/actions/export', GttBureauMemberExportController::class)
            ->middleware(RefreshPermissionCache::class)
            ->name('gtts.bureau-members.export');

        Route::get('/admin/reunions-planning/export/{year}', \App\Http\Controllers\MeetingsPlanningExportController::class)
            ->name('planning.export')
            ->where('year', '[0-9]{4}');

        Route::post('/admin/reunions-planning/upload', \App\Http\Controllers\MeetingDocumentUploadController::class)
            ->name('planning.upload');

        Route::get('/admin/reunions-planning/download/{meeting}/{type}', \App\Http\Controllers\MeetingDocumentDownloadController::class)
            ->name('planning.download')
            ->where('type', 'tdr|rapport');
    });

// Présence co-édition documents

Route::middleware(['auth', 'throttle:60,1'])->group(function (): void {
    Route::get('/admin/documents/{document}/office-editor', [OnlyOfficeController::class, 'editor'])
        ->name('onlyoffice.editor');

    Route::post('/admin/documents/{document}/presence/heartbeat', [DocumentPresenceController::class, 'heartbeat'])
        ->name('documents.presence.heartbeat');

    Route::post('/admin/documents/{document}/presence/leave', [DocumentPresenceController::class, 'leave'])
        ->name('documents.presence.leave');
});

Route::post('/onlyoffice/callback/{document}/{media}', [OnlyOfficeController::class, 'callback'])
    ->name('onlyoffice.callback')
    ->middleware(['signed', 'throttle:120,1'])
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::middleware(['auth', 'throttle:20,1'])->group(function (): void {
    Route::get('/profile/downloads/{asset}', ProfileDownloadController::class)
        ->name('profile.downloads.asset');

    Route::get('/admin/chat-messages/{chatMessage}/attachments/{media}', ChatAttachmentController::class)
        ->name('chat.attachments.show');

    Route::post('/admin/file-manager/sync-ops', FileManagerSyncController::class)
        ->name('file-manager.sync-ops');

    Route::get('/admin/file-manager/offline-tasks', [FileManagerOfflineTaskController::class, 'index'])
        ->name('file-manager.offline-tasks.index');

    Route::post('/admin/file-manager/offline-tasks', [FileManagerOfflineTaskController::class, 'store'])
        ->name('file-manager.offline-tasks.store');

    Route::patch('/admin/file-manager/offline-tasks/{task}', [FileManagerOfflineTaskController::class, 'update'])
        ->name('file-manager.offline-tasks.update');

    Route::delete('/admin/file-manager/offline-tasks/{task}', [FileManagerOfflineTaskController::class, 'destroy'])
        ->name('file-manager.offline-tasks.destroy');

    Route::post('/admin/file-manager/offline-tasks/sync', [FileManagerOfflineTaskController::class, 'sync'])
        ->name('file-manager.offline-tasks.sync');
    
    // Route de test pour diagnostics (à supprimer)
    
    Route::get('/debug-menu', function () {
    return view('filament.pages.dynamic-menu-page');
});
});
