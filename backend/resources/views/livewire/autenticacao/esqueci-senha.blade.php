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
            Recuperação segura de acesso à conta
        </p>
    </div>

    <!-- Card Principal -->
    <div class="card" style="padding: 24px;">
        <div style="margin-bottom: 18px;">
            <h2 style="font-size: 17px; font-weight: 600; color: var(--text-primary); letter-spacing: -0.01em;">
                Recuperar senha
            </h2>
            <p style="font-size: 12.5px; color: var(--text-secondary); margin-top: 4px; line-height: 1.5;">
                Informe o endereço de e-mail associado à sua conta de estudante. Enviaremos as instruções de segurança para você cadastrar uma nova senha.
            </p>
        </div>

        <x-alert type="danger" :message="$errorMessage" style="margin-bottom: 16px;" />

        @if($submitted)
            <!-- Estado de Sucesso / Confirmação de Solicitação -->
            <div style="background-color: var(--metric-active-subtle); border: 1px solid var(--metric-active-border); color: var(--metric-active); border-radius: var(--radius-md); padding: 16px; margin-bottom: 18px; font-size: 12.5px; line-height: 1.6;">
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <x-icon name="check-circle-2" size="20" style="flex-shrink: 0; margin-top: 2px;" />
                    <div>
                        <strong style="font-size: 13px; display: block; margin-bottom: 4px;">Solicitação processada com sucesso!</strong>
                        <p style="margin: 0 0 8px 0; color: var(--text-primary);">
                            Se existir uma conta associada ao endereço informado, enviaremos um e-mail com as instruções para redefinir sua senha. Se o e-mail informado estiver cadastrado, a mensagem chegará em instantes.
                        </p>
                        <p style="margin: 0; color: var(--text-secondary); font-size: 12px;">
                            Verifique sua caixa de entrada. Se a mensagem não aparecer, confira também as pastas <strong>Spam</strong> ou <strong>Lixo eletrônico</strong> e as abas <strong>Promoções</strong> e <strong>Outros</strong>, caso existam.
                        </p>
                    </div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                <!-- Botão para retornar à tela de login após o envio -->
                <a href="{{ route('login') }}" class="btn btn-primary" style="width: 100%; padding: 10px; font-weight: 600; gap: 8px; justify-content: center;">
                    <x-icon name="arrow-left" size="16" />
                    <span>Voltar ao login</span>
                </a>

                <!-- Botão para reiniciar o fluxo e solicitar recuperação para outro e-mail -->
                <button type="button"
                        wire:click="novaSolicitacao"
                        class="btn btn-ghost"
                        style="width: 100%; padding: 8px; font-size: 12px; color: var(--text-secondary);">
                    Solicitar para outro e-mail
                </button>
            </div>
        @else
            <!-- Formulário de Solicitação -->
            <form wire:submit="sendResetLink" style="display: flex; flex-direction: column; gap: 14px;">
                <!-- Campo de texto para informar o e-mail do usuário -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="email">Endereço de e-mail</label>
                    <input wire:model="email"
                           type="email"
                           id="email"
                           class="form-input"
                           placeholder="seu.email@exemplo.com"
                           autocomplete="email"
                           required />
                    @error('email')
                        <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 4px; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Botão utilizado para disparar o link de recuperação por e-mail -->
                <x-button-loading text="Enviar link de recuperação"
                                  loadingText="Processando envio..."
                                  target="sendResetLink"
                                  icon="mail"
                                  type="submit"
                                  variant="primary"
                                  style="width: 100%; padding: 10px; margin-top: 6px; font-weight: 600;" />

                <!-- Link para retornar à tela de login sem enviar -->
                <div style="text-align: center; margin-top: 6px;">
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
