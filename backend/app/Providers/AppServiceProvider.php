<?php

namespace App\Providers;

use App\Models\Comentario;
use App\Models\Grupo;
use App\Models\SessaoEstudo;
use App\Models\TokenAcessoPessoal;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use App\Policies\GrupoPolicy;
use App\Policies\SessaoEstudoPolicy;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

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
        Sanctum::usePersonalAccessTokenModel(TokenAcessoPessoal::class);
        Gate::policy(Grupo::class, GrupoPolicy::class);
        Gate::policy(SessaoEstudo::class, SessaoEstudoPolicy::class);

        // Mapeamentos de modelos para compatibilidade de rotas
        Route::model('group', Grupo::class);
        Route::model('grupo', Grupo::class);
        Route::model('study_session', SessaoEstudo::class);
        Route::model('sessao_estudo', SessaoEstudo::class);
        Route::model('comment', Comentario::class);
        Route::model('comentario', Comentario::class);
        Route::model('user', Usuario::class);
        Route::model('usuario', Usuario::class);

        // Configuração de localização nativa para Carbon e PHP em português
        Carbon::setLocale('pt_BR');
        setlocale(LC_TIME, 'pt_BR.utf8', 'pt_BR', 'portuguese');
    }
}
