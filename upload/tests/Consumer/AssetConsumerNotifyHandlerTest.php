<?php

declare(strict_types=1);

namespace App\Tests\Consumer;

use App\Consumer\Handler\AssetConsumerNotifyHandler;
use Arthem\Bundle\RabbitBundle\Consumer\Event\EventMessage;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AssetConsumerNotifyHandlerTest extends TestCase
{
    public function testAssetConsumerNotify(): void
    {
        $accessToken = 'secret_token';

        $consumerResponse = new Response(200, [
            'Content-Type' => 'application/json',
        ], '{"meta":{"api_version":"1.4.1","request":"POST \/api\/v1\/upload\/enqueue\/","response_time":"2019-06-05T16:28:24+02:00","http_code":200,"error_type":null,"error_message":null,"error_details":null,"charset":"UTF-8"},"response":{"data":{"assets":["4c097077-a26b-4af4-9a5d-b13fd4c77b3d","a134145e-9461-4f0a-8bd8-7025d31a6b8e"],"publisher":"d03fc9f6-3c6b-4428-8d6f-ba07c7c6e856","token":"a_token"}}}');

        $clientHandler = new MockHandler([
            $consumerResponse,
        ]);
        $clientStub = $client = new Client(['handler' => $clientHandler]);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('http://localhost/');

        $handler = new AssetConsumerNotifyHandler(
            $clientStub,
            'http://localhost/api/v1/upload/enqueue/',
            $accessToken,
            $urlGenerator
        );

        $logger = new TestLogger();
        $handler->setLogger($logger);

        $message = new EventMessage($handler::EVENT, [
            'files' => [
                '4c097077-a26b-4af4-9a5d-b13fd4c77b3d',
                'a134145e-9461-4f0a-8bd8-7025d31a6b8e',
            ],
            'form' => ['foo' => 'bar'],
            'user_id' => 'd03fc9f6-3c6b-4428-8d6f-ba07c7c6e856',
            'token' => 'a_token',
        ]);
        $handler->handle($message);

        $this->assertEquals('/api/v1/upload/enqueue/', $clientHandler->getLastRequest()->getUri()->getPath());

        $postBody = json_decode($clientHandler->getLastRequest()->getBody()->getContents(), true);
        $this->assertArrayHasKey('assets', $postBody);
        $this->assertCount(2, $postBody['assets']);
        $this->assertEquals('a_token', $postBody['token']);
        $this->assertEquals('http://localhost', $postBody['base_url']);

        $this->assertEquals(0, $clientHandler->count());
    }
}
