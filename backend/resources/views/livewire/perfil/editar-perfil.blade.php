<div class="container" style="padding: 28px 32px 80px 32px; max-width: 1120px;">

    <!-- ========================================================================= -->
    <!-- 1. CABEÇALHO DO PERFIL: Identidade do Estudante                           -->
    <!-- ========================================================================= -->
    <div class="card" style="padding: 22px 24px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 20px; flex: 1; min-width: 260px;"
             x-data="avatarCropper()"
             x-on:livewire-upload-finish="onFileUploaded()">

            <!-- Avatar com Botão de Câmera e Preview Reativo -->
            <div style="position: relative; flex-shrink: 0;">
                <div style="width: 76px; height: 76px; border-radius: 50%; background-color: var(--bg-surface-elevated); border: 2px solid var(--border-subtle); box-shadow: var(--shadow-md); overflow: hidden; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                    <template x-if="croppedPreview">
                        <img :src="croppedPreview"
                             alt="Prévia do Avatar Cortado"
                             style="width: 100%; height: 100%; object-fit: cover;">
                    </template>
                    <template x-if="!croppedPreview">
                        @if($avatar)
                            <img src="{{ $avatar->temporaryUrl() }}"
                                 alt="Prévia do Avatar"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        @elseif($avatarUrl)
                            <img src="{{ url('storage/' . $avatarUrl) }}"
                                 alt="{{ $user->name }}"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        @else
                            <span style="font-size: 26px; font-weight: 700; color: var(--text-secondary);">
                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                            </span>
                        @endif
                    </template>
                </div>

                <!-- Botão para escolher nova foto de perfil a partir do seu dispositivo -->
                <label for="avatar-input"
                       title="Trocar foto de perfil"
                       style="position: absolute; bottom: 0; right: 0; width: 28px; height: 28px; border-radius: 50%; background-color: var(--color-primary); color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid var(--bg-surface); box-shadow: var(--shadow-sm); transition: transform 120ms ease;">
                    <x-icon name="camera" size="14" />
                    <!-- Campo de seleção de arquivo de imagem para foto de perfil -->
                    <input type="file"
                           id="avatar-input"
                           wire:model="avatar"
                           accept="image/jpeg,image/png,image/webp"
                           style="display: none;">
                </label>
            </div>

            <!-- Identificação e Bio Resumida -->
            <div style="flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <h1 style="font-size: 20px; font-weight: 600; color: var(--text-primary); margin: 0; letter-spacing: -0.015em;">
                        {{ $user->name }}
                    </h1>
                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: var(--radius-sm); background-color: rgba(37, 99, 235, 0.12); color: var(--metric-hours); font-size: 11px; font-weight: 500;">
                        <x-icon name="sparkles" size="11" />
                        <span>Conta Verificada</span>
                    </span>
                </div>

                <p style="font-size: 13px; color: var(--text-muted); margin: 4px 0 0 0;">
                    {{ $user->email }}
                </p>

                @if($user->bio)
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-top: 6px; margin-bottom: 0; font-style: italic;">
                        "{{ $user->bio }}"
                    </p>
                @endif

                @if($avatarUrl && !$avatar)
                    <!-- Botão para remover a foto de perfil cadastrada atualmente -->
                    <div style="margin-top: 6px;">
                        <button type="button"
                                wire:click="removeAvatar"
                                style="background: transparent; border: none; padding: 0; color: var(--color-danger); font-size: 11.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                            <x-icon name="trash" size="11" />
                            <span>Remover foto atual</span>
                        </button>
                    </div>
                @endif

                <div wire:loading wire:target="avatar" class="btn-content" style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px; gap: 6px;">
                    <x-icon name="loader" size="12" class="spin" />
                    <span>Carregando foto...</span>
                </div>

                @error('avatar')
                    <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 4px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <!-- ================================================================= -->
            <!-- MODAL DE CORTE E REDIMENSIONAMENTO DO AVATAR                      -->
            <!-- ================================================================= -->
            <template x-if="showCropper">
                <div class="modal-overlay" x-on:click.self="cancelCrop()" x-transition>
                    <div class="modal-content" style="max-width: 480px;">

                        <!-- Cabeçalho -->
                        <div class="modal-header">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <x-icon name="crop" size="18" style="color: var(--color-primary);" />
                                <h3 style="font-size: 15px; font-weight: 600; color: var(--text-primary); margin: 0;">
                                    Ajustar Foto de Perfil
                                </h3>
                            </div>
                            <button type="button" x-on:click="cancelCrop()"
                                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 4px; display: flex;">
                                <x-icon name="x" size="18" />
                            </button>
                        </div>

                        <!-- Corpo: Área de Corte -->
                        <div class="modal-body" style="align-items: center; gap: 16px;">
                            <p style="font-size: 12.5px; color: var(--text-muted); text-align: center; margin: 0;">
                                Arraste para mover e use os controles para ajustar o enquadramento. A área circular será salva como sua foto de perfil.
                            </p>

                            <!-- Container da imagem com área de corte circular -->
                            <div class="cropper-viewport"
                                 x-ref="cropperViewport"
                                 x-on:mousedown.prevent="startDrag($event)"
                                 x-on:touchstart.passive="startDrag($event)"
                                 style="position: relative; width: 280px; height: 280px; border-radius: 50%; overflow: hidden; background-color: var(--bg-surface-elevated); border: 3px solid var(--border-subtle); cursor: grab; user-select: none; touch-action: none; flex-shrink: 0;">

                                <img x-ref="cropImage"
                                     :src="imageSrc"
                                     :style="`position: absolute; transform-origin: 0 0; transform: translate(${offsetX}px, ${offsetY}px) scale(${scale}); max-width: none; pointer-events: none;`"
                                     x-on:load="onImageLoad()"
                                     alt="Imagem para corte" draggable="false">
                            </div>

                            <!-- Controles de Zoom -->
                            <div style="display: flex; align-items: center; gap: 10px; width: 100%; max-width: 280px;">
                                <button type="button" x-on:click="zoomOut()"
                                        style="background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: var(--text-secondary); border-radius: var(--radius-md); width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0;"
                                        title="Diminuir zoom">
                                    <x-icon name="minus" size="14" />
                                </button>

                                <input type="range" min="0.1" max="3" step="0.01"
                                       x-model.number="scale"
                                       x-on:input="clampPosition()"
                                       style="flex: 1; accent-color: var(--color-primary); cursor: pointer;">

                                <button type="button" x-on:click="zoomIn()"
                                        style="background: var(--bg-surface-elevated); border: 1px solid var(--border-subtle); color: var(--text-secondary); border-radius: var(--radius-md); width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0;"
                                        title="Aumentar zoom">
                                    <x-icon name="plus" size="14" />
                                </button>
                            </div>

                            <div style="font-size: 11px; color: var(--text-muted); text-align: center;">
                                Zoom: <span x-text="Math.round(scale * 100) + '%'"></span>
                            </div>
                        </div>

                        <!-- Rodapé com Ações -->
                        <div class="modal-footer">
                            <button type="button" x-on:click="cancelCrop()" class="btn btn-ghost" style="font-size: 13px; gap: 6px;">
                                <x-icon name="x" size="14" />
                                <span>Cancelar</span>
                            </button>
                            <button type="button" x-on:click="applyCrop()" class="btn btn-primary" style="font-size: 13px; gap: 6px;">
                                <x-icon name="check" size="14" />
                                <span>Aplicar Corte</span>
                            </button>
                        </div>

                    </div>
                </div>
            </template>

            <!-- Canvas oculto para recortar a imagem final -->
            <canvas x-ref="cropCanvas" style="display: none;"></canvas>
        </div>

        <!-- Badge de Status Ativo -->
        <div style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: var(--radius-full); background-color: var(--bg-surface-subtle); border: 1px solid var(--border-subtle); font-size: 11.5px; color: var(--text-secondary); align-self: center;">
            <span style="width: 7px; height: 7px; border-radius: 50%; background-color: var(--metric-active); box-shadow: 0 0 8px var(--metric-active);"></span>
            <span>Estudante Ativo</span>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. MÉTRICAS CONSOLIDADAS: Cartões Gamificados                              -->
    <!-- ========================================================================= -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 20px;">
        <x-card-stat
            title="Dias Ativos"
            :value="$summary['total_active_days']"
            unit="dias"
            icon="calendar"
            accent="active"
            helper="Total acumulado no histórico"
        />

        <x-card-stat
            title="Horas Líquidas"
            :value="$summary['total_hours']"
            unit="h"
            icon="clock"
            accent="hours"
            helper="Tempo total dedicado"
        />

        <x-card-stat
            title="Questões Resolvidas"
            :value="$summary['total_questions']"
            unit="itens"
            icon="target"
            accent="questions"
            helper="Volume de treino prático"
        />

        <x-card-stat
            title="Comunidades"
            :value="$summary['groups_count']"
            unit="grupos"
            icon="users"
            accent="active"
            helper="Grupos que você integra"
        />
    </div>

    <!-- ========================================================================= -->
    <!-- 3. ALERTAS DE STATUS (Sucesso e Erro)                                     -->
    <!-- ========================================================================= -->
    <x-alert type="success" :message="$feedbackMessage" style="margin-bottom: 18px;" />
    <x-alert type="danger" :message="$feedbackError" style="margin-bottom: 18px;" />
    <x-alert type="info" :message="$passwordResetFeedback" icon="mail" style="margin-bottom: 18px;" />
    <x-alert type="danger" :message="$passwordResetError" style="margin-bottom: 18px;" />


    <!-- ========================================================================= -->
    <!-- 4. FORMULÁRIO PRINCIPAL: Edição de Perfil                                 -->
    <!-- ========================================================================= -->
    <form wire:submit.prevent="save" style="display: flex; flex-direction: column; gap: 20px;">

        <!-- Seção 1: Identidade do Estudante -->
        <div class="card" style="padding: 24px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px;">
                <x-icon name="user" size="18" style="color: var(--color-primary);" />
                <h2 style="font-size: 15px; font-weight: 600; color: var(--text-primary); margin: 0;">
                    Informações do Estudante
                </h2>
            </div>

            <!-- Campo de texto para preencher o nome completo do estudante -->
            <div>
                <label for="name" class="label">Nome Completo</label>
                <input type="text"
                       id="name"
                       wire:model="name"
                       placeholder="Ex: Matheus Silva"
                       class="input @error('name') input-error @enderror"
                       required>
                <span style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px; display: block;">
                    Nome público de exibição visível para seus colegas de estudo nos grupos e rankings.
                </span>
                @error('name')
                    <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 4px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <!-- Campo de texto para informar a biografia e os objetivos de estudo -->
            <div style="margin-top: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="bio" class="label" style="margin-bottom: 0;">Biografia e Foco de Estudos</label>
                    <span style="font-size: 11px; color: {{ mb_strlen($bio) > 230 ? 'var(--color-danger)' : 'var(--text-muted)' }};">
                        {{ mb_strlen($bio) }}/250
                    </span>
                </div>
                <textarea id="bio"
                          wire:model="bio"
                          rows="3"
                          maxlength="250"
                          placeholder="Ex: Focado no concurso da Receita Federal. Meta diária: 100 questões e revisões espaçadas."
                          class="input @error('bio') input-error @enderror"
                          style="resize: vertical;"></textarea>
                @error('bio')
                    <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 4px; display: block;">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <!-- Seção 2: Configurações de Conta e Acesso -->
        <div class="card" style="padding: 24px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 18px;">
                <x-icon name="shield-check" size="18" style="color: var(--metric-active);" />
                <h2 style="font-size: 15px; font-weight: 600; color: var(--text-primary); margin: 0;">
                    Acesso e Configurações da Conta
                </h2>
            </div>

            <div>
                <!-- Campo de texto para informar o e-mail do usuário -->
                <div>
                    <label for="email" class="label" style="display: flex; align-items: center; gap: 6px;">
                        <x-icon name="mail" size="13" style="color: var(--text-muted);" />
                        <span>Endereço de E-mail</span>
                    </label>
                    <input type="email"
                           id="email"
                           wire:model="email"
                           placeholder="seu.email@exemplo.com"
                           class="input @error('email') input-error @enderror"
                           required>
                    <span style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px; display: block;">
                        Utilizado para login e recebimento de notificações essenciais.
                    </span>
                    @error('email')
                        <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 4px; display: block;">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Seção 3: Cartão de Segurança e Redefinição de Senha -->
        <div class="card" style="padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; background-color: var(--bg-surface-subtle);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: var(--radius-md); background-color: rgba(37, 99, 235, 0.12); color: var(--color-primary); display: flex; align-items: center; justify-content: center;">
                    <x-icon name="key-round" size="18" />
                </div>
                <div>
                    <div style="font-size: 13.5px; font-weight: 600; color: var(--text-primary);">
                        Segurança da Senha
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted);">
                        Deseja alterar ou renovar sua senha atual por segurança?
                    </div>
                </div>
            </div>

            <!-- Botão para enviar link de redefinição de senha para o e-mail do usuário -->
            <button type="button"
                    wire:click="sendPasswordResetLink"
                    wire:loading.attr="disabled"
                    class="btn btn-secondary"
                    style="font-size: 12px; gap: 6px;">
                <span wire:loading.remove wire:target="sendPasswordResetLink">Solicitar Redefinição por E-mail</span>
                <span wire:loading wire:target="sendPasswordResetLink" class="btn-content" style="gap: 6px;">
                    <x-icon name="loader" size="13" class="spin" />
                    <span>Enviando link...</span>
                </span>
            </button>
        </div>

        <!-- Barra de Ações: Salvar e Restaurar -->
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 6px;">
            <!-- Botão utilizado para descartar alterações e restaurar os valores originais -->
            <button type="button"
                    wire:click="restore"
                    wire:loading.attr="disabled"
                    class="btn btn-ghost"
                    style="font-size: 13px; gap: 6px;">
                <x-icon name="rotate-ccw" size="14" />
                <span>Restaurar</span>
            </button>

            <!-- Botão utilizado para salvar as alterações realizadas no perfil -->
            <x-button-loading text="Salvar Alterações"
                              loadingText="Salvando alterações..."
                              target="save"
                              icon="save"
                              type="submit"
                              variant="primary"
                              style="font-size: 13px; padding: 10px 22px;" />
        </div>
    </form>

    <!-- ========================================================================= -->
    <!-- ALPINE.JS: Lógica do Editor Visual de Corte de Avatar                     -->
    <!-- ========================================================================= -->
    <script>
        function avatarCropper() {
            return {
                showCropper: false,
                imageSrc: '',
                scale: 1,
                minScale: 0.1,
                offsetX: 0,
                offsetY: 0,
                isDragging: false,
                dragStartX: 0,
                dragStartY: 0,
                startOffsetX: 0,
                startOffsetY: 0,
                imgNaturalWidth: 0,
                imgNaturalHeight: 0,
                croppedPreview: null,
                viewportSize: 280,

                onFileUploaded() {
                    // Aguarda o Livewire terminar o upload e gerar a temporaryUrl
                    this.$nextTick(() => {
                        const fileInput = document.getElementById('avatar-input');
                        if (fileInput && fileInput.files && fileInput.files[0]) {
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                this.imageSrc = e.target.result;
                                this.scale = 1;
                                this.offsetX = 0;
                                this.offsetY = 0;
                                this.croppedPreview = null;
                                this.showCropper = true;
                            };
                            reader.readAsDataURL(fileInput.files[0]);
                        }
                    });
                },

                onImageLoad() {
                    const img = this.$refs.cropImage;
                    if (!img) return;
                    this.imgNaturalWidth = img.naturalWidth;
                    this.imgNaturalHeight = img.naturalHeight;

                    // Calcula o scale inicial para cobrir a viewport inteira (cover)
                    const scaleX = this.viewportSize / this.imgNaturalWidth;
                    const scaleY = this.viewportSize / this.imgNaturalHeight;
                    this.minScale = Math.max(scaleX, scaleY);
                    this.scale = this.minScale;

                    // Centraliza a imagem
                    this.offsetX = (this.viewportSize - this.imgNaturalWidth * this.scale) / 2;
                    this.offsetY = (this.viewportSize - this.imgNaturalHeight * this.scale) / 2;
                },

                clampPosition() {
                    if (this.scale < this.minScale) this.scale = this.minScale;
                    const scaledW = this.imgNaturalWidth * this.scale;
                    const scaledH = this.imgNaturalHeight * this.scale;

                    // Impede que a imagem mostre espaço vazio dentro do círculo
                    const maxX = 0;
                    const minX = this.viewportSize - scaledW;
                    const maxY = 0;
                    const minY = this.viewportSize - scaledH;

                    this.offsetX = Math.min(maxX, Math.max(minX, this.offsetX));
                    this.offsetY = Math.min(maxY, Math.max(minY, this.offsetY));
                },

                zoomIn() {
                    const center = this.viewportSize / 2;
                    const prevScale = this.scale;
                    this.scale = Math.min(3, this.scale + 0.1);

                    // Ajusta a posição para manter o centro visual
                    this.offsetX = center - (center - this.offsetX) * (this.scale / prevScale);
                    this.offsetY = center - (center - this.offsetY) * (this.scale / prevScale);
                    this.clampPosition();
                },

                zoomOut() {
                    const center = this.viewportSize / 2;
                    const prevScale = this.scale;
                    this.scale = Math.max(this.minScale, this.scale - 0.1);

                    this.offsetX = center - (center - this.offsetX) * (this.scale / prevScale);
                    this.offsetY = center - (center - this.offsetY) * (this.scale / prevScale);
                    this.clampPosition();
                },

                startDrag(event) {
                    this.isDragging = true;
                    const point = event.touches ? event.touches[0] : event;
                    this.dragStartX = point.clientX;
                    this.dragStartY = point.clientY;
                    this.startOffsetX = this.offsetX;
                    this.startOffsetY = this.offsetY;

                    const viewport = this.$refs.cropperViewport;
                    if (viewport) viewport.style.cursor = 'grabbing';

                    const onMove = (e) => {
                        if (!this.isDragging) return;
                        const p = e.touches ? e.touches[0] : e;
                        this.offsetX = this.startOffsetX + (p.clientX - this.dragStartX);
                        this.offsetY = this.startOffsetY + (p.clientY - this.dragStartY);
                        this.clampPosition();
                    };

                    const onEnd = () => {
                        this.isDragging = false;
                        if (viewport) viewport.style.cursor = 'grab';
                        document.removeEventListener('mousemove', onMove);
                        document.removeEventListener('mouseup', onEnd);
                        document.removeEventListener('touchmove', onMove);
                        document.removeEventListener('touchend', onEnd);
                    };

                    document.addEventListener('mousemove', onMove);
                    document.addEventListener('mouseup', onEnd);
                    document.addEventListener('touchmove', onMove, { passive: true });
                    document.addEventListener('touchend', onEnd);
                },

                applyCrop() {
                    const canvas = this.$refs.cropCanvas;
                    const ctx = canvas.getContext('2d');
                    const img = this.$refs.cropImage;
                    const outputSize = 512; // Tamanho final do avatar em pixels

                    canvas.width = outputSize;
                    canvas.height = outputSize;

                    // Calcula a área de recorte baseada na posição e escala atuais
                    const sourceX = -this.offsetX / this.scale;
                    const sourceY = -this.offsetY / this.scale;
                    const sourceSize = this.viewportSize / this.scale;

                    // Recorta em um quadrado (a máscara circular é aplicada via CSS no avatar)
                    ctx.clearRect(0, 0, outputSize, outputSize);
                    ctx.drawImage(
                        img,
                        sourceX, sourceY, sourceSize, sourceSize,
                        0, 0, outputSize, outputSize
                    );

                    // Gera o base64 da imagem cortada
                    const dataUrl = canvas.toDataURL('image/png', 1.0);
                    this.croppedPreview = dataUrl;
                    this.showCropper = false;

                    // Envia os dados cortados para o Livewire
                    @this.set('croppedAvatarData', dataUrl);
                },

                cancelCrop() {
                    this.showCropper = false;
                    this.imageSrc = '';
                    this.croppedPreview = null;
                    // Limpa o arquivo do Livewire
                    @this.set('avatar', null);
                    @this.set('croppedAvatarData', null);
                    // Reseta o input de arquivo para permitir nova seleção
                    const fileInput = document.getElementById('avatar-input');
                    if (fileInput) fileInput.value = '';
                }
            };
        }
    </script>

</div>
