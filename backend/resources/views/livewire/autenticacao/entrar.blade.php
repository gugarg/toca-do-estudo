<div class="animacao-surgir">
    <!-- Marca & Logo -->
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: var(--radius-md); background-color: var(--bg-surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary); margin-bottom: 10px;">
            <x-icon name="book-open" size="20" />
        </div>
        <h1 style="font-size: 20px; font-weight: 600; color: var(--text-primary); letter-spacing: -0.015em;">
            Toca<span style="color: var(--text-secondary);"> do Estudo</span>
        </h1>
        <p style="font-size: 13px; color: var(--text-muted); margin-top: 3px;">
            Acompanhamento social e gamificado de estudos acadêmicos
        </p>
    </div>

    <!-- Card de Autenticação -->
    <div class="card" style="padding: 24px;">

        <x-alert type="danger" :message="$errorMessage" />

        <form wire:submit="authenticate" style="display: flex; flex-direction: column; gap: 14px;">
            <!-- Campo de texto para informar o e-mail do usuário -->
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="email">E-mail de estudante</label>
                <input wire:model="email"
                       type="email"
                       id="email"
                       class="form-input"
                       placeholder="estudante@exemplo.com"
                       autocomplete="email"
                       required />
                @error('email')
                    <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 2px;">{{ $message }}</span>
                @enderror
            </div>

            <!-- Campo para inserir a senha de acesso -->
            <div class="form-group" style="margin-bottom: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label class="form-label" for="password">Senha de acesso</label>
                    <!-- Link para recuperação caso o estudante tenha esquecido a senha -->
                    <a href="{{ route('password.request') }}"
                       style="font-size: 11.5px; color: var(--color-primary); text-decoration: none;"
                       onmouseenter="this.style.textDecoration='underline';"
                       onmouseleave="this.style.textDecoration='none';">
                        Esqueci minha senha
                    </a>
                </div>
                <input wire:model="password"
                       type="password"
                       id="password"
                       class="form-input"
                       placeholder="••••••••"
                       autocomplete="current-password"
                       required />
                @error('password')
                    <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 2px;">{{ $message }}</span>
                @enderror
            </div>

            <!-- Opção para manter a sessão ativa neste dispositivo -->
            <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--text-secondary); cursor: pointer; user-select: none;">
                <input type="checkbox" wire:model="remember" style="accent-color: var(--color-primary); width: 14px; height: 14px; cursor: pointer;">
                <span>Lembrar de mim neste dispositivo</span>
            </label>

            <!-- Botão utilizado para autenticar e acessar a plataforma -->
            <x-button-loading text="Acessar Plataforma"
                              loadingText="Autenticando..."
                              icon="log-in"
                              style="padding: 10px; margin-top: 6px; font-weight: 600;"
                              :fullWidth="true" />
        </form>
    </div>

    <!-- Link para cadastrar uma nova conta de estudante -->
    <div style="text-align: center; margin-top: 18px; font-size: 12.5px; color: var(--text-muted);">
        Ainda não tem uma conta?
        <a href="{{ route('register') }}" style="color: var(--color-primary); text-decoration: none; font-weight: 500;"
           onmouseenter="this.style.textDecoration='underline';"
           onmouseleave="this.style.textDecoration='none';">
            Cadastre-se gratuitamente
        </a>
    </div>
</div>
