<?php

namespace Tests\Unit;

use App\Services\FastApiClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class FastApiClientTest extends TestCase
{
    private function makeClientWithMockResponses(array $responses): FastApiClient
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        // Bind into our FastApiClient via reflection to replace http client
        $client = new FastApiClient();
        $ref = new \ReflectionClass($client);
        $prop = $ref->getProperty('http');
        $prop->setAccessible(true);
        $prop->setValue($client, $guzzle);

        $baseProp = $ref->getProperty('baseUrl');
        $baseProp->setAccessible(true);
        $baseProp->setValue($client, 'http://fake.test');

        return $client;
    }

    public function test_summarize_success_returns_array()
    {
        $payload = ['type' => 'text', 'transcript' => 'hello'];
        $client = $this->makeClientWithMockResponses([
            new Response(200, [], json_encode(['summary' => 'ok']))
        ]);

        $out = $client->summarize($payload);
        $this->assertIsArray($out);
        $this->assertSame('ok', $out['summary']);
    }

    public function test_summarize_error_throws_runtime_exception()
    {
        $this->expectException(\RuntimeException::class);
        $payload = ['type' => 'text', 'transcript' => 'hello'];
        $client = $this->makeClientWithMockResponses([
            new Response(500, [], json_encode(['detail' => 'fail']))
        ]);
        $client->summarize($payload);
    }

    public function test_summarize_media_requires_readable_file()
    {
        $client = $this->makeClientWithMockResponses([]);
        $this->expectException(\InvalidArgumentException::class);
        $client->summarizeMedia(['type' => 'media', 'file_path' => __DIR__.'\\not-exist.mp4']);
    }

    public function test_summarize_media_success()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'media');
        file_put_contents($tmp, 'x');
        $client = $this->makeClientWithMockResponses([
            new Response(200, [], json_encode(['summary' => 'media ok']))
        ]);
        $out = $client->summarizeMedia(['type' => 'media', 'file_path' => $tmp, 'filename' => 'a.mp4']);
        $this->assertSame('media ok', $out['summary']);
        @unlink($tmp);
    }
}
