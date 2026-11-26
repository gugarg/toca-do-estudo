<?php

declare(strict_types=1);

namespace App\Livewire\Componentes;

use Livewire\Attributes\On;
use Livewire\Component;

class VisualizadorFoto extends Component
{
    public bool $isOpen = false;
    public ?string $photoUrl = null;
    public string $subject = '';

    #[On('open-photo-lightbox')]
    public function open(string $url, string $subject = '', ?string $title = null): void
    {
        $this->photoUrl = $url;
        $this->subject = $subject ?: ($title ?: 'Comprovação de Estudo');
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->photoUrl = null;
        $this->subject = '';
    }

    public function render()
    {
        return view('livewire.componentes.visualizador-foto');
    }
}
