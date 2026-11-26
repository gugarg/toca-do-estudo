<?php

namespace App\Providers;

use App\Models\Grupo;
use App\Models\SessaoEstudo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use App\Policies\GrupoPolicy;
use App\Policies\SessaoEstudoPolicy;
use Illuminate\Support\ServiceProvider;

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
        Gate::policy(Grupo::class, GrupoPolicy::class);
        Gate::policy(SessaoEstudo::class, SessaoEstudoPolicy::class);

        // Mapeamentos de modelos para compatibilidade de rotas web
        Route::model('group', Grupo::class);
        Route::model('grupo', Grupo::class);

        // Configuração de localização nativa para Carbon e PHP em português
        Carbon::setLocale('pt_BR');
        setlocale(LC_TIME, 'pt_BR.utf8', 'pt_BR', 'portuguese');
    }
}
