<div>
    <x-modal :show="$isOpen"
             title="Registrar Estudo"
             subtitle="Registre seu tempo, questões e comprove sua dedicação."
             icon="book-open"
             onClose="close"
             maxWidth="540px">

        <form wire:submit.prevent="save" style="display: flex; flex-direction: column; flex: 1; overflow-y: auto;">
            <div style="padding: 20px; display: flex; flex-direction: column; gap: 16px;">

                <x-alert type="danger" :message="$errorMessage" />

                <!-- Data e Disciplina em Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1.4fr; gap: 12px;">
                    <!-- Campo para seleção da data de estudo (suporta lançamentos retroativos de até 7 dias corridos) -->
                    <div>
                        <label for="studyDate" class="label">Data de Estudo</label>
                        <input type="date"
                               id="studyDate"
                               wire:model="studyDate"
                               max="{{ now()->toDateString() }}"
                               min="{{ $minDate ?? \App\Models\SessaoEstudo::getDataLimiteRetroativa() }}"
                               class="input @error('studyDate') input-error @enderror"
                               required>
                        @error('studyDate')
                            <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 4px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Campo de texto para informar a matéria ou disciplina estudada -->
                    <div>
                        <label for="subject" class="label">Disciplina ou Matéria</label>
                        <input type="text"
                               id="subject"
                               wire:model="subject"
                               placeholder="Ex: Cálculo II, Algoritmos"
                               class="input @error('subject') input-error @enderror"
                               required>
                        @error('subject')
                            <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 4px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Tempo e Questões em Grid Responsivo -->
                <div class="modal-grid-2">
                    <!-- Tempo de Estudo -->
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label class="label" style="margin-bottom: 0;">Tempo de Estudo</label>
                            <span class="badge badge-hours" style="font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                <x-icon name="clock" size="12" />
                                {{ $this->tempoFormatado }}
                            </span>
                        </div>

                        <input type="hidden" id="minutes" wire:model="minutes">

                        <!-- Horas e Minutos lado a lado com sufixos embutidos -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <div>
                                <div class="input-with-suffix">
                                    <input type="number"
                                           id="hours"
                                           wire:model.live="hours"
                                           min="0"
                                           max="12"
                                           step="1"
                                           class="input no-spinner @error('minutes') input-error @enderror"
                                           style="padding-right: 28px; text-align: center; font-weight: 600;"
                                           placeholder="0"
                                           aria-label="Horas de estudo">
                                    <span class="input-suffix">h</span>
                                </div>
                            </div>

                            <div>
                                <div class="input-with-suffix">
                                    <input type="number"
                                           id="minutesPart"
                                           wire:model.live="minutesPart"
                                           min="0"
                                           max="59"
                                           step="5"
                                           class="input no-spinner @error('minutes') input-error @enderror"
                                           style="padding-right: 36px; text-align: center; font-weight: 600;"
                                           placeholder="0"
                                           aria-label="Minutos de estudo">
                                    <span class="input-suffix">min</span>
                                </div>
                            </div>
                        </div>

                        <!-- Atalhos rápidos de tempo -->
                        <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-top: 7px;">
                            @foreach([30 => '30m', 60 => '1h', 90 => '1h30', 120 => '2h', 180 => '3h', 240 => '4h'] as $presetMin => $label)
                                <button type="button"
                                        wire:click="setMinutesPreset({{ $presetMin }})"
                                        class="preset-chip {{ (int)$minutes === $presetMin ? 'active' : '' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        @error('minutes')
                            <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 4px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Campo numérico para o número de questões ou exercícios resolvidos -->
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label for="questions" class="label" style="margin-bottom: 0;">Questões Resolvidas</label>
                            <span style="font-size: 11.5px; color: var(--text-muted);">
                                {{ $questions > 0 ? $questions.' itens' : 'Opcional' }}
                            </span>
                        </div>

                        <div class="input-with-suffix">
                            <input type="number"
                                   id="questions"
                                   wire:model="questions"
                                   min="0"
                                   max="1000"
                                   class="input no-spinner @error('questions') input-error @enderror"
                                   style="padding-right: 48px;"
                                   placeholder="0"
                                   aria-label="Número de questões resolvidas">
                            <span class="input-suffix">itens</span>
                        </div>

                        <!-- Atalhos rápidos de questões -->
                        <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-top: 7px;">
                            @foreach([0 => '0', 10 => '10', 20 => '20', 30 => '30', 50 => '50'] as $qVal => $qLabel)
                                <button type="button"
                                        wire:click="$set('questions', {{ $qVal }})"
                                        class="preset-chip {{ (int)$questions === $qVal ? 'active' : '' }}">
                                    {{ $qLabel }}
                                </button>
                            @endforeach
                        </div>

                        @error('questions')
                            <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 4px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Campo de texto para anotações e tópicos abordados -->
                <div>
                    <label for="notes" class="label">Anotações / Resumo (Opcional)</label>
                    <textarea id="notes"
                              wire:model="notes"
                              rows="2"
                              placeholder="Ex: Exercícios de integrais duplas e revisão de teoremas..."
                              class="input @error('notes') input-error @enderror"
                              style="resize: vertical; min-height: 54px;"></textarea>
                    @error('notes')
                        <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 4px; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Campo de upload para registro visual do estudo -->
                <div>
                    <label class="label">Registro Visual (Opcional)</label>
                    
                    @if($photo)
                        <div style="position: relative; border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border-subtle); margin-bottom: 8px; background-color: var(--bg-surface-subtle); max-height: 180px; display: flex; align-items: center; justify-content: center;">
                            <img src="{{ $photo->temporaryUrl() }}"
                                 alt="Prévia da foto"
                                 style="max-height: 180px; width: 100%; object-fit: cover;">
                            <button type="button"
                                    wire:click="removePhoto"
                                    class="btn btn-secondary btn-sm"
                                    style="position: absolute; top: 8px; right: 8px; background-color: rgba(0, 0, 0, 0.7); backdrop-filter: blur(2px); border-color: rgba(255, 255, 255, 0.2); color: #fff; gap: 4px;">
                                <x-icon name="trash" size="13" />
                                <span>Remover</span>
                            </button>
                        </div>
                    @else
                        <div style="position: relative;">
                            <label for="photo-upload"
                                   style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 18px; border: 1px dashed var(--border-subtle); border-radius: var(--radius-md); background-color: var(--bg-surface-subtle); cursor: pointer; transition: all 120ms ease;"
                                   onmouseenter="this.style.borderColor='var(--border-focus)'; this.style.backgroundColor='var(--bg-surface-hover)';"
                                   onmouseleave="this.style.borderColor='var(--border-subtle)'; this.style.backgroundColor='var(--bg-surface-subtle)';">
                                <div style="width: 34px; height: 34px; border-radius: 50%; background-color: var(--bg-surface-elevated); display: flex; align-items: center; justify-content: center; color: var(--text-secondary);">
                                    <x-icon name="camera" size="18" />
                                </div>
                                <div style="text-align: center;">
                                    <span style="font-size: 13px; font-weight: 500; color: var(--text-primary);">
                                        Clique para anexar foto de comprovação
                                    </span>
                                    <p style="font-size: 11px; color: var(--text-muted); margin: 2px 0 0 0;">
                                        PNG, JPG ou WEBP até 5MB. Otimizado automaticamente para WebP.
                                    </p>
                                </div>
                                <input type="file"
                                       id="photo-upload"
                                       wire:model="photo"
                                       accept="image/png,image/jpeg,image/webp"
                                       style="display: none;">
                            </label>
                        </div>
                    @endif

                    <div wire:loading wire:target="photo" class="btn-content" style="font-size: 12px; color: var(--text-muted); margin-top: 6px; gap: 6px;">
                        <x-icon name="loader" size="14" class="spin" />
                        <span>Carregando foto...</span>
                    </div>

                    <!-- Aviso de Privacidade e Minimização de Dados (LGPD) -->
                    <p style="font-size: 10.5px; color: var(--text-muted); margin-top: 6px; line-height: 1.4; display: flex; align-items: flex-start; gap: 5px;">
                        <span style="font-size: 11px; opacity: 0.8; flex-shrink: 0;">🔒</span>
                        <span><strong style="color: var(--text-secondary); font-weight: 600;">Aviso de Privacidade:</strong> envie apenas imagens relacionadas aos seus estudos, como anotações, cadernos ou telas de estudo. Evite fotografias que contenham documentos, endereços ou outros dados pessoais.</span>
                    </p>

                    @error('photo')
                        <span style="font-size: 11.5px; color: var(--color-danger); margin-top: 4px; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Compartilhamento no Mural (Alternativa 1: ON / OFF) -->
                <div style="background-color: var(--bg-surface-subtle); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 12px 14px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                    <div>
                        <label for="toggle-compartilhar" style="font-size: 13px; font-weight: 600; color: var(--text-primary); cursor: pointer; display: block;">
                            Compartilhar nos Murais das Comunidades
                        </label>
                        <p style="font-size: 11px; color: var(--text-muted); margin: 2px 0 0 0;">
                            Ao ativar, esta sessão aparecerá nos murais e contará nos rankings das comunidades das quais você já participa nesta data de estudo (estudos anteriores à entrada em um grupo não contam retroativamente para ele).
                        </p>
                    </div>
                    <input type="checkbox"
                           id="toggle-compartilhar"
                           wire:model="compartilhar"
                           style="width: 20px; height: 20px; accent-color: var(--color-primary); cursor: pointer; flex-shrink: 0;">
                </div>

            </div>

            <!-- Rodapé com Ações -->
            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 14px 20px; border-top: 1px solid var(--border-subtle); background-color: var(--bg-surface);">
                <button type="button" wire:click="close" class="btn btn-secondary">
                    Cancelar
                </button>
                <x-button-loading text="Salvar Estudo" loadingText="Registrando..." target="save" type="submit" variant="primary" />
            </div>
        </form>
    </x-modal>
</div>
