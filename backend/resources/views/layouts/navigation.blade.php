<header style="background-color: var(--bg-surface); border-bottom: 1px solid var(--border-subtle); position: sticky; top: 0; z-index: 40; width: 100%;">
    <div class="container" style="display: flex; align-items: center; justify-content: space-between; height: 56px; width: 100%; max-width: 1480px; margin: 0 auto; padding: 0 32px; box-sizing: border-box;">
        <!-- Logotipo e links de navegação principal da aplicação -->
        <div style="display: flex; align-items: center; gap: 28px;">
            <a href="{{ route('painel') }}" style="display: flex; align-items: center; gap: 8px; text-decoration: none; color: var(--text-primary);">
                <div style="width: 28px; height: 28px; border-radius: var(--radius-sm); background-color: var(--bg-surface-subtle); border: 1px solid var(--border-subtle); display: flex; align-items: center; justify-content: center;">
                    <x-icon name="book-open" size="16" />
                </div>
                <span style="font-size: 15px; font-weight: 600; letter-spacing: -0.01em;">
                    Toca<span style="color: var(--text-secondary);"> do Estudo</span>
                </span>
            </a>

            <!-- Menu de navegação entre as seções: Painel e Comunidades -->
            <nav style="display: flex; align-items: center; gap: 4px;">
                <a href="{{ route('painel') }}"
                   class="btn btn-ghost"
                   style="font-size: 13px; {{ request()->routeIs(['painel', 'dashboard']) ? 'color: var(--text-primary); background-color: var(--bg-surface-subtle);' : '' }}">
                    Painel
                </a>
                <a href="{{ route('comunidades') }}"
                   class="btn btn-ghost"
                   style="font-size: 13px; {{ request()->routeIs(['comunidades*', 'groups.*']) ? 'color: var(--text-primary); background-color: var(--bg-surface-subtle);' : '' }}">
                    Comunidades
                </a>
            </nav>
        </div>

        <!-- Ações do cabeçalho: botão para registrar novo estudo e menu do usuário -->
        <div style="display: flex; align-items: center; gap: 12px;">
            <!-- Botão utilizado para abrir o formulário de registro de estudo -->
            <button type="button"
                    x-data
                    @click="$dispatch('abrir-modal-sessao')"
                    class="btn btn-primary"
                    style="gap: 6px; padding: 6px 12px; font-size: 12.5px;">
                <x-icon name="plus" size="14" />
                <span>Registrar Estudo</span>
            </button>

            <!-- Menu suspenso com as opções do perfil e encerramento de sessão -->
            <div x-data="{ aberto: false }" style="position: relative;">
                <button @click="aberto = !aberto"
                        @click.outside="aberto = false"
                        type="button"
                        style="background: transparent; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; padding: 2px;">
                    <x-avatar :user="auth()->user()" size="sm" style="border-radius: 50%;" />
                </button>

                <div x-show="aberto"
                     x-cloak
                     style="position: absolute; right: 0; top: 100%; margin-top: 6px; width: 190px; background-color: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); box-shadow: var(--shadow-md); padding: 4px; z-index: 50;">
                    <div style="padding: 8px 10px; border-bottom: 1px solid var(--border-subtle); margin-bottom: 4px;">
                        <div style="font-size: 12.5px; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ auth()->user()?->name }}
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ auth()->user()?->email }}
                        </div>
                    </div>

                    <!-- Link para acessar a tela de edição do perfil -->
                    <a href="{{ route('perfil') }}"
                       style="display: flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: var(--radius-sm); font-size: 12.5px; color: var(--text-secondary); text-decoration: none; transition: all 120ms ease;"
                       onmouseenter="this.style.backgroundColor='var(--bg-surface-hover)'; this.style.color='var(--text-primary)';"
                       onmouseleave="this.style.backgroundColor='transparent'; this.style.color='var(--text-secondary)';">
                        <x-icon name="user" size="14" />
                        <span>Meu Perfil</span>
                    </a>

                    <!-- Formulário para encerrar a sessão do usuário com segurança -->
                    <form method="POST" action="{{ route('sair') }}" style="margin: 0;">
                        @csrf
                        <button type="submit"
                                style="width: 100%; display: flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: var(--radius-sm); font-size: 12.5px; color: var(--color-danger); background: transparent; border: none; cursor: pointer; text-align: left; transition: all 120ms ease;"
                                onmouseenter="this.style.backgroundColor='var(--color-danger-subtle)';"
                                onmouseleave="this.style.backgroundColor='transparent';">
                            <x-icon name="log-out" size="14" />
                            <span>Sair da Conta</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
