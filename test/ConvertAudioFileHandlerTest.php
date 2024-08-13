<?php
declare(strict_types=1);

namespace AppTest;

use App\ConvertAudioFileHandler;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;

class ConvertAudioFileHandlerTest extends TestCase
{
    const string UPLOAD_DIRECTORY = __DIR__ . '/data/upload';
    const string DOWNLOAD_DIRECTORY = __DIR__ . '/data/download';

    #[TestWith(['generic.mp3', 'audio/mpeg'])]
    #[TestWith(['generic.flac', 'audio/flac'])]
    #[TestWith(['generic.aac', 'audio/aac'])]
    #[TestWith(['/opt/generic.aac', 'audio/aac'])]
    public function testCanGetContentTypesForValidAudioFiles(string $filename, string $expectedOutput): void
    {
        $this->assertEquals(
            $expectedOutput,
            (new ConvertAudioFileHandler(
                self::UPLOAD_DIRECTORY,
                self::DOWNLOAD_DIRECTORY
            ))->getContentType($filename)
        );
    }

    public function testInvalidArgumentThrownForUnknownAudioFormats()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown audio format: unknown');

        (new ConvertAudioFileHandler(
            self::UPLOAD_DIRECTORY,
            self::DOWNLOAD_DIRECTORY
        ))->getContentType('unknown');
    }

    public function testCanConvertAudioFileToOneOfTheAcceptedFormats()
    {
        $handler = new ConvertAudioFileHandler(
            self::UPLOAD_DIRECTORY,
            self::DOWNLOAD_DIRECTORY
        );
        $handler->convertAudioFile('sample.mp3', 'flac');
        $this->assertFileExists(self::DOWNLOAD_DIRECTORY . '/sample.flac');
    }

    public function testCanMoveUploadedFile()
    {
        $handler = new ConvertAudioFileHandler(
            self::UPLOAD_DIRECTORY,
            self::DOWNLOAD_DIRECTORY
        );
        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $uploadedFile
            ->expects($this->once())
            ->method('getClientFilename')
            ->willReturn('sample.mp3');
        $uploadedFile
            ->expects($this->once())
            ->method('moveTo');

        $handler->moveUploadedFile(self::UPLOAD_DIRECTORY, $uploadedFile);
    }

    public function testCanOnlyConvertToSupportedAudioFormats()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown audio format: ogg');

        $handler = new ConvertAudioFileHandler(
            self::UPLOAD_DIRECTORY,
            self::DOWNLOAD_DIRECTORY
        );
        $handler->convertAudioFile('sample.wav', 'ogg');
    }

    public function tearDown(): void
    {
        if (file_exists(self::DOWNLOAD_DIRECTORY . '/sample.mp3')) {
            unlink(self::DOWNLOAD_DIRECTORY . '/sample.mp3');
        }
    }
}