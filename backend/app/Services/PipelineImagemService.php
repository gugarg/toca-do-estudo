<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class PipelineImagemService
{
    private const MAX_DIMENSION = 1280;
    private const WEBP_QUALITY = 80;
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/webp',
    ];

    // Processa a imagem enviada pelo estudante, ajusta as dimensões e converte para WebP garantindo alta compressão e privacidade.
    public function processarEConverterParaWebP(UploadedFile $file, string $directory = 'sessions'): string
    {
        // Garante a integridade do upload inspecionando os bytes reais via finfo em vez de confiar na extensão informada pelo cliente
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file->getRealPath());
        finfo_close($finfo);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException("Formato de arquivo não suportado. Apenas JPEG, PNG e WEBP são permitidos.");
        }

        // Carrega o bitmap na memória usando o driver GD correspondente ao formato detectado
        $sourceImage = match ($mimeType) {
            'image/jpeg', 'image/pjpeg' => @imagecreatefromjpeg($file->getRealPath()),
            'image/png' => @imagecreatefrompng($file->getRealPath()),
            'image/webp' => @imagecreatefromwebp($file->getRealPath()),
            default => false,
        };

        if ($sourceImage === false) {
            throw new RuntimeException("Falha ao decodificar a imagem fornecida.");
        }

        // Mantém a proporção original caso a foto ultrapasse o limite de 1280px, evitando distorções visuais
        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);
        $newWidth = $origWidth;
        $newHeight = $origHeight;

        if ($origWidth > self::MAX_DIMENSION || $origHeight > self::MAX_DIMENSION) {
            $ratio = $origWidth / $origHeight;
            if ($ratio > 1) {
                $newWidth = self::MAX_DIMENSION;
                $newHeight = (int) round(self::MAX_DIMENSION / $ratio);
            } else {
                $newHeight = self::MAX_DIMENSION;
                $newWidth = (int) round(self::MAX_DIMENSION * $ratio);
            }
        }

        // Criamos uma nova tela limpa. Isso remove metadados EXIF da câmera (como coordenadas GPS e modelo do aparelho), protegendo a privacidade do aluno
        $targetImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserva o canal alfa para imagens com fundo transparente
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        imagefilledrectangle($targetImage, 0, 0, $newWidth, $newHeight, $transparent);

        imagecopyresampled(
            $targetImage,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $origWidth, $origHeight
        );

        // Captura o fluxo binário comprimido em WebP sem necessidade de gravar arquivos intermediários no disco
        ob_start();
        imagewebp($targetImage, null, self::WEBP_QUALITY);
        $webpData = ob_get_clean();

        // Libera os ponteiros da biblioteca GD imediatamente para evitar consumo desnecessário de memória PHP
        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        if ($webpData === false) {
            throw new RuntimeException("Falha ao codificar a imagem para o formato WebP.");
        }

        // Salva o arquivo no disco público com um UUID único para evitar colisões
        $filename = Str::uuid()->toString() . '.webp';
        $path = "{$directory}/{$filename}";

        Storage::disk('public')->put($path, $webpData);

        return $path;
    }

    /**
     * Processa uma imagem recebida como string base64 (data URI do canvas do navegador),
     * redimensiona e converte para WebP com remoção de metadados EXIF.
     * Utilizado pelo editor visual de corte de avatar.
     */
    public function processarBase64ParaWebP(string $base64Data, string $directory = 'avatars'): string
    {
        // Remove o prefixo data URI caso presente (ex: "data:image/png;base64,")
        if (str_contains($base64Data, ',')) {
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        }

        $binaryData = base64_decode($base64Data, true);

        if ($binaryData === false) {
            throw new InvalidArgumentException('Dados de imagem base64 inválidos.');
        }

        // Valida o tipo MIME a partir dos bytes reais
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($binaryData);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException("Formato de arquivo não suportado. Apenas JPEG, PNG e WEBP são permitidos.");
        }

        // Carrega o bitmap na memória a partir dos bytes binários
        $sourceImage = @imagecreatefromstring($binaryData);

        if ($sourceImage === false) {
            throw new RuntimeException("Falha ao decodificar a imagem fornecida.");
        }

        // Redimensiona mantendo a proporção caso ultrapasse o limite máximo
        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);
        $newWidth = $origWidth;
        $newHeight = $origHeight;

        if ($origWidth > self::MAX_DIMENSION || $origHeight > self::MAX_DIMENSION) {
            $ratio = $origWidth / $origHeight;
            if ($ratio > 1) {
                $newWidth = self::MAX_DIMENSION;
                $newHeight = (int) round(self::MAX_DIMENSION / $ratio);
            } else {
                $newHeight = self::MAX_DIMENSION;
                $newWidth = (int) round(self::MAX_DIMENSION * $ratio);
            }
        }

        // Nova tela limpa sem metadados EXIF
        $targetImage = imagecreatetruecolor($newWidth, $newHeight);

        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        imagefilledrectangle($targetImage, 0, 0, $newWidth, $newHeight, $transparent);

        imagecopyresampled(
            $targetImage,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $origWidth, $origHeight
        );

        // Captura o WebP comprimido
        ob_start();
        imagewebp($targetImage, null, self::WEBP_QUALITY);
        $webpData = ob_get_clean();

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        if ($webpData === false) {
            throw new RuntimeException("Falha ao codificar a imagem para o formato WebP.");
        }

        $filename = Str::uuid()->toString() . '.webp';
        $path = "{$directory}/{$filename}";

        Storage::disk('public')->put($path, $webpData);

        return $path;
    }
}
