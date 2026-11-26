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
            Crie sua conta de estudante e comece a registrar seus estudos
        </p>
    </div>

    <!-- Card de Cadastro -->
    <div class="card" style="padding: 24px;">

        <x-alert type="danger" :message="$errorMessage" />

        <form wire:submit="register" style="display: flex; flex-direction: column; gap: 14px;">
            <!-- Campo de texto para informar o nome completo do estudante -->
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="name">Nome completo</label>
                <input wire:model="name"
                       type="text"
                       id="name"
                       class="form-input"
                       placeholder="Ex: Matheus Silva"
                       autocomplete="name"
                       required />
                @error('name')
                    <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 2px;">{{ $message }}</span>
                @enderror
            </div>

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
                <label class="form-label" for="password">Senha de acesso</label>
                <input wire:model="password"
                       type="password"
                       id="password"
                       class="form-input"
                       placeholder="Mínimo de 6 caracteres"
                       autocomplete="new-password"
                       required />
                @error('password')
                    <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 2px;">{{ $message }}</span>
                @enderror
            </div>

            <!-- Campo para confirmar a senha digitada -->
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="password_confirmation">Confirmar senha</label>
                <input wire:model="password_confirmation"
                       type="password"
                       id="password_confirmation"
                       class="form-input"
                       placeholder="Repita a senha"
                       autocomplete="new-password"
                       required />
            </div>

            <!-- Botão utilizado para registrar a nova conta e acessar a plataforma -->
            <x-button-loading text="Criar Minha Conta"
                              loadingText="Criando conta..."
                              icon="check"
                              style="padding: 10px; margin-top: 6px; font-weight: 600;"
                              :fullWidth="true" />
        </form>
    </div>

    <!-- Link para direcionar estudantes que já possuem conta para o login -->
    <div style="text-align: center; margin-top: 18px; font-size: 12.5px; color: var(--text-muted);">
        Já possui uma conta?
        <a href="{{ route('login') }}" style="color: var(--color-primary); text-decoration: none; font-weight: 500;"
           onmouseenter="this.style.textDecoration='underline';"
           onmouseleave="this.style.textDecoration='none';">
            Fazer login
        </a>
    </div>
</div>
