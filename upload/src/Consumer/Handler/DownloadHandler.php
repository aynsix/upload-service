<?php

declare(strict_types=1);

namespace App\Consumer\Handler;

use App\Model\Commit;
use App\Storage\AssetManager;
use App\Storage\FileStorageManager;
use Arthem\Bundle\RabbitBundle\Consumer\Event\AbstractEntityManagerHandler;
use Arthem\Bundle\RabbitBundle\Consumer\Event\EventMessage;
use Arthem\Bundle\RabbitBundle\Producer\EventProducer;
use GuzzleHttp\Client;
use Mimey\MimeTypes;

class DownloadHandler extends AbstractEntityManagerHandler
{
    const EVENT = 'download';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var FileStorageManager
     */
    private $storageManager;

    /**
     * @var AssetManager
     */
    private $assetManager;

    /**
     * @var EventProducer
     */
    private $eventProducer;

    public function __construct(
        FileStorageManager $storageManager,
        Client $client,
        AssetManager $assetManager,
        EventProducer $eventProducer
    ) {
        $this->client = $client;
        $this->storageManager = $storageManager;
        $this->assetManager = $assetManager;
        $this->eventProducer = $eventProducer;
    }

    public function handle(EventMessage $message): void
    {
        $payload = $message->getPayload();
        $url = $payload['url'];
        $userId = $payload['user_id'];
        $formData = $payload['form_data'];
        $response = $this->client->request('GET', $url);
        $headers = $response->getHeaders();
        $contentType = $headers['Content-Type'][0] ?? 'application/octet-stream';

        $originalName = basename(explode('?', $url, 2)[0]);
        if (isset($headers['Content-Disposition'][0])) {
            $contentDisposition = $headers['Content-Disposition'][0];
            if (preg_match('#\s+filename="(.+?)"#', $contentDisposition, $regs)) {
                $originalName = $regs[1];
            }
        }

        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $extension = !empty($extension) ? $extension : null;
        if (!$extension && 'application/octet-stream' !== $contentType) {
            $mimes = new MimeTypes();
            $extension = $mimes->getExtension($contentType);
            $originalName .= '.'.$extension;
        }

        $path = $this->storageManager->generatePath($extension);

        $this->storageManager->store($path, $response->getBody()->getContents());

        $asset = $this->assetManager->createAsset(
            $path,
            $contentType,
            $originalName,
            $response->getBody()->getSize()
        );

        $commit = new Commit();
        $commit->setFormData($formData);
        $commit->setUserId($userId);
        $commit->setFiles([$asset->getId()]);
        $commit->generateToken();
        $this->eventProducer->publish(new EventMessage(CommitHandler::EVENT, $commit->toArray()));
    }

    public static function getHandledEvents(): array
    {
        return [self::EVENT];
    }

    public static function getQueueName(): string
    {
        return 'download_url';
    }
}
