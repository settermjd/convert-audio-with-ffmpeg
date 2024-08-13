<?php
declare(strict_types=1);

namespace App;

use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Aac;
use FFMpeg\Format\Audio\Flac;
use FFMpeg\Format\Audio\Mp3;
use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use function basename;
use function file_get_contents;
use function pathinfo;
use function sprintf;

readonly class ConvertAudioFileHandler
{
    public function __construct(
        private string $uploadDirectory,
        private string $downloadDirectory
    ){}

    /**
     * convertAudioFile converts an audio file from one format to another
     */
    public function convertAudioFile(string $filename, string $audioFormat): string
    {
        $format = match(strtolower($audioFormat)) {
            'aac'  => new Aac(),
            'flac'  => new Flac(),
            'mp3' => new Mp3(),
            default => throw new InvalidArgumentException("Unknown audio format: $audioFormat"),
        };

        $audioFile = $this->uploadDirectory . DIRECTORY_SEPARATOR . $filename;
        if (! file_exists($audioFile) || empty($audioFile)) {
            throw new InvalidArgumentException('Audio file does not exist: ' . $filename);
        }

        $ffmpeg = FFMpeg::create();
        $audio = $ffmpeg->open($audioFile);
        $file = sprintf(
            "%s%s.%s",
            $this->downloadDirectory . DIRECTORY_SEPARATOR,
            pathinfo($filename, PATHINFO_FILENAME),
            strtolower($audioFormat)
        );
        $audio->save($format, $file);

        return $file;
    }

    public function moveUploadedFile(string $uploadDirectory, UploadedFileInterface $uploadedFile): string
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8));
        $filename = sprintf('%s.%0.8s', $basename, $extension);

        $targetPath = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
        $uploadedFile->moveTo($targetPath);

        return $filename;
    }

    /**
     * getContentType takes a filename and, based on its extension, returns its media type.
     */
    public function getContentType(string $file): string
    {
        return match(pathinfo($file, PATHINFO_EXTENSION)) {
            'mp3' => 'audio/mpeg',
            'flac' => 'audio/flac',
            'aac' => 'audio/aac',
            default => throw new InvalidArgumentException("Unknown audio format: $file"),
        };
    }

    /**
     * The route accepts POST requests with an audio file (audio_file) in the POST data and the format to
     * convert it to in a route attribute (to_format). If the received file is an audio file, it then uses
     * FFMpeg to convert the audio file to that format, saving the file with an auto-generated, temporary
     * filename. After the audio file is converted, a JSON response is sent with a link to download the
     * converted audio file, and the original audio file is deleted. This final step could also be
     * handled by an  operating system-level process instead, to take pressure off the PHP application.
     */
    public function __invoke(Request $request, Response $response, array $args): MessageInterface|Response
    {
        $uploadedFiles = $request->getUploadedFiles();
        $audioFile = $uploadedFiles['audio_file'];
        if ($audioFile->getError() === UPLOAD_ERR_OK) {
            $filename = $this->moveUploadedFile($this->uploadDirectory, $audioFile);
            $toFormat = (string) $args['to_format'];
            $file = $this->convertAudioFile($filename, $toFormat);

            $response = $response->withStatus(201);
            $response = $response->withHeader('content-type', $this->getContentType($file));
            $response = $response->withHeader(
                'content-disposition',
                sprintf('attachment; filename="%s"', basename($file))
            );
            $response
                ->getBody()
                ->write(file_get_contents($file));
        }

        return $response;
    }
}