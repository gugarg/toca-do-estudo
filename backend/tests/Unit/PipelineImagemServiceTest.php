<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PipelineImagemService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class PipelineImagemServiceTest extends TestCase
{
    private PipelineImagemService $pipeline;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pipeline = app(PipelineImagemService::class);
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_converte_jpeg_e_png_para_webp_valido(): void
    {
        $jpegFile = UploadedFile::fake()->image('caderno.jpg', 640, 480);
        $pngFile = UploadedFile::fake()->image('resumo.png', 800, 600);

        $path1 = $this->pipeline->processarEConverterParaWebP($jpegFile, 'sessions');
        $path2 = $this->pipeline->processarEConverterParaWebP($pngFile, 'avatars');

        $this->assertStringEndsWith('.webp', $path1);
        $this->assertStringEndsWith('.webp', $path2);

        Storage::disk('local')->assertExists($path1);
        Storage::disk('public')->assertMissing($path1);
        Storage::disk('public')->assertExists($path2);

        // Verifica que o conteúdo salvo é de fato um WebP válido legível pelo GD
        $fullPath1 = Storage::disk('local')->path($path1);
        $imgResource = @imagecreatefromwebp($fullPath1);
        $this->assertNotFalse($imgResource, 'O arquivo gerado deve ser um WebP válido legível pelo GD');
        imagedestroy($imgResource);
    }

    public function test_rejeita_arquivos_que_nao_sao_imagens_disfarcados(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Criar arquivo texto com extensão .jpg (mime-spoofing)
        $fakeImage = UploadedFile::fake()->createWithContent('malicioso.jpg', '<?php echo "exploit"; ?>');

        $this->pipeline->processarEConverterParaWebP($fakeImage, 'sessions');
    }

    public function test_redimensiona_imagens_que_excedem_a_dimensao_maxima(): void
    {
        // Imagem grande: 2560 x 1440
        $hugeImage = UploadedFile::fake()->image('tela_ultra.jpg', 2560, 1440);

        $path = $this->pipeline->processarEConverterParaWebP($hugeImage, 'sessions');
        $fullPath = Storage::disk('local')->path($path);

        $info = getimagesize($fullPath);
        $width = $info[0];
        $height = $info[1];

        // A dimensão máxima permitida no serviço é 1280px
        $this->assertLessThanOrEqual(1280, $width);
        $this->assertLessThanOrEqual(1280, $height);
        $this->assertEquals(1280, $width); // Reduzido proporcionalmente
        $this->assertEquals(720, $height);
    }
}
