<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\Report;
use App\Models\ReportCategory;
use App\Models\ReportRecommendation;
use App\Models\ReportTemplate;
use App\Models\Courrier;
use App\Models\Dossier;
use App\Models\AuditLog;
use App\Models\User;
use App\Observers\MediaStorageObserver;
use App\Observers\DocumentWorkflowObserver;
use App\Policies\AuditLogPolicy;
use App\Policies\CourrierPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\DossierPolicy;
use App\Policies\ReportCategoryPolicy;
use App\Policies\ReportRecommendationPolicy;
use App\Policies\ReportPolicy;
use App\Policies\ReportTemplatePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Courrier::class, CourrierPolicy::class);
        Gate::policy(Dossier::class, DossierPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Report::class, ReportPolicy::class);
        Gate::policy(ReportCategory::class, ReportCategoryPolicy::class);
        Gate::policy(ReportRecommendation::class, ReportRecommendationPolicy::class);
        Gate::policy(ReportTemplate::class, ReportTemplatePolicy::class);

        Media::observe(MediaStorageObserver::class);
        Document::observe(DocumentWorkflowObserver::class);

        // AD4 — Journal des connexions/déconnexions
        Event::listen(
            \Illuminate\Auth\Events\Login::class,
            [\App\Listeners\LogAuthEventListener::class, 'handleLogin'],
        );
        Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            [\App\Listeners\LogAuthEventListener::class, 'handleLogout'],
        );

        // Rate limiter: max 10 export requests per minute per user
        RateLimiter::for('exports', function (Request $request): Limit {
            return $request->user()
                ? Limit::perMinute(10)->by($request->user()->id)
                : Limit::perMinute(3)->by($request->ip());
        });
    }
}
