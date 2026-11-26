<div class="animacao-surgir" x-data="{
    showPassword: false,
    showConfirm: false,
    pwd: '',
    confirmPwd: ''
}">
    <!-- Marca & Logo -->
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: var(--radius-md); background-color: var(--bg-surface-subtle); border: 1px solid var(--border-subtle); color: var(--text-primary); margin-bottom: 10px;">
            <x-icon name="book-open" size="20" />
        </div>
        <h1 style="font-size: 20px; font-weight: 600; color: var(--text-primary); letter-spacing: -0.015em;">
            Toca<span style="color: var(--text-secondary);"> do Estudo</span>
        </h1>
        <p style="font-size: 13px; color: var(--text-muted); margin-top: 3px;">
            Definição de nova senha de acesso
        </p>
    </div>

    <!-- Card Principal -->
    <div class="card" style="padding: 24px;">
        @if(!$tokenValid)
            <!-- Estado: Link Inválido ou Expirado -->
            <div style="text-align: center; padding: 12px 0;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background-color: var(--color-danger-subtle); border: 1px solid var(--color-danger-border); color: var(--color-danger); margin-bottom: 14px;">
                    <x-icon name="alert-circle" size="24" />
                </div>
                <h2 style="font-size: 17px; font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">
                    Link Inválido ou Expirado
                </h2>
                <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.5; margin-bottom: 20px;">
                    {{ $errorMessage ?? 'Este link de recuperação é inválido, expirou ou já foi utilizado.' }}
                </p>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="{{ route('password.request') }}" class="btn btn-primary" style="width: 100%; padding: 10px; font-size: 13px; font-weight: 600; justify-content: center;">
                        Solicitar novo link
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-ghost" style="width: 100%; padding: 8px; font-size: 12.5px; color: var(--text-muted);">
                        Voltar ao login
                    </a>
                </div>
            </div>
        @elseif($success)
            <!-- Estado: Senha Atualizada com Sucesso -->
            <div style="text-align: center; padding: 12px 0;">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background-color: var(--metric-active-subtle); border: 1px solid var(--metric-active-border); color: var(--metric-active); margin-bottom: 14px;">
                    <x-icon name="check-circle-2" size="24" />
                </div>
                <h2 style="font-size: 17px; font-weight: 600; color: var(--text-primary); margin-bottom: 8px;">
                    Senha Atualizada com Sucesso!
                </h2>
                <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.5; margin-bottom: 20px;">
                    Sua nova credencial foi gravada com segurança. Você já pode acessar sua conta utilizando a nova senha.
                </p>
                <a href="{{ route('login') }}" class="btn btn-primary" style="width: 100%; padding: 11px; font-weight: 600; gap: 8px; justify-content: center;">
                    <x-icon name="log-in" size="16" />
                    <span>Ir para o login</span>
                </a>
            </div>
        @else
            <!-- Formulário de Nova Senha -->
            <div style="margin-bottom: 18px;">
                <h2 style="font-size: 17px; font-weight: 600; color: var(--text-primary); letter-spacing: -0.01em;">
                    Criar nova senha
                </h2>
                <p style="font-size: 12.5px; color: var(--text-secondary); margin-top: 4px; line-height: 1.5;">
                    Defina uma nova senha para a conta: <strong style="color: var(--text-primary);">{{ $email }}</strong>
                </p>
            </div>

            <x-alert type="danger" :message="$errorMessage" style="margin-bottom: 16px;" />

            <form wire:submit="resetPassword" style="display: flex; flex-direction: column; gap: 14px;">
                <!-- Campo para inserir a nova senha de acesso -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="password">Nova senha</label>
                    <div style="position: relative;">
                        <input wire:model="password"
                               x-model="pwd"
                               :type="showPassword ? 'text' : 'password'"
                               id="password"
                               class="form-input"
                               style="padding-right: 40px;"
                               placeholder="Mínimo de 6 caracteres"
                               autocomplete="new-password"
                               required />
                        <button type="button"
                                @click="showPassword = !showPassword"
                                style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--text-muted); cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center;"
                                :title="showPassword ? 'Ocultar senha' : 'Ver senha'">
                            <span x-show="!showPassword"><x-icon name="eye" size="16" /></span>
                            <span x-show="showPassword"><x-icon name="eye-off" size="16" /></span>
                        </button>
                    </div>
                    @error('password')
                        <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 4px; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Campo para confirmar a nova senha digitada -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="password_confirmation">Confirmar nova senha</label>
                    <div style="position: relative;">
                        <input wire:model="password_confirmation"
                               x-model="confirmPwd"
                               :type="showConfirm ? 'text' : 'password'"
                               id="password_confirmation"
                               class="form-input"
                               style="padding-right: 40px;"
                               placeholder="Repita a nova senha"
                               autocomplete="new-password"
                               required />
                        <button type="button"
                                @click="showConfirm = !showConfirm"
                                style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--text-muted); cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center;"
                                :title="showConfirm ? 'Ocultar senha' : 'Ver senha'">
                            <span x-show="!showConfirm"><x-icon name="eye" size="16" /></span>
                            <span x-show="showConfirm"><x-icon name="eye-off" size="16" /></span>
                        </button>
                    </div>
                </div>

                <!-- Indicadores de requisitos de segurança e correspondência -->
                <div style="background-color: var(--bg-surface-subtle); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 10px 12px; font-size: 12px; display: flex; flex-direction: column; gap: 6px;">
                    <div style="display: flex; align-items: center; gap: 6px;"
                         :style="pwd.length >= 6 ? 'color: var(--color-success);' : 'color: var(--text-muted);'">
                        <x-icon name="check-circle" size="14" />
                        <span>Mínimo de 6 caracteres</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 6px;"
                         :style="(confirmPwd.length > 0 && confirmPwd === pwd) ? 'color: var(--color-success);' : 'color: var(--text-muted);'">
                        <x-icon name="check-circle" size="14" />
                        <span>Senhas conferem entre si</span>
                    </div>
                </div>

                <!-- Botão utilizado para salvar as alterações e atualizar a senha -->
                <x-button-loading text="Redefinir senha"
                                  loadingText="Gravando nova senha..."
                                  target="resetPassword"
                                  icon="key-round"
                                  type="submit"
                                  variant="primary"
                                  style="width: 100%; padding: 10px; margin-top: 4px; font-weight: 600;" />

                <div style="text-align: center; margin-top: 4px;">
                    <a href="{{ route('login') }}"
                       style="font-size: 12.5px; color: var(--text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 6px;"
                       onmouseenter="this.style.color='var(--text-primary)';"
                       onmouseleave="this.style.color='var(--text-muted)';">
                        <x-icon name="arrow-left" size="14" />
                        <span>Voltar ao login</span>
                    </a>
                </div>
            </form>
        @endif
    </div>
</div>
