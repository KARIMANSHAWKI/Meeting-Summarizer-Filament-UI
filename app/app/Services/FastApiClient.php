<?php

namespace App\Services;

use GuzzleHttp\Client;

class FastApiClient
{
    protected Client $http;
    protected string $baseUrl;

    public function __construct(?Client $http = null, ?string $baseUrl = null)
    {
        $this->http = $http ?: new Client([
            // Increase timeouts for long media processing; 0 means no timeout for some handlers
            'timeout' => 0,
            'read_timeout' => 0,
            'connect_timeout' => 15,
            'verify' => false,
        ]);
        if ($baseUrl) {
            $this->baseUrl = rtrim($baseUrl, '/');
        } else {
            // Avoid hard dependency on Laravel helpers when running pure unit tests.
            // Only read from the container if it exists AND the 'config' binding is present.
            $cfg = null;
            if (class_exists('Illuminate\\Container\\Container')) {
                $container = \Illuminate\Container\Container::getInstance();
                if ($container && method_exists($container, 'bound') && $container->bound('config')) {
                    try {
                        $cfg = $container->make('config')->get('services.fastapi.url');
                    } catch (\Throwable $e) {
                        $cfg = null;
                    }
                }
            }
            $envUrl = getenv('FASTAPI_URL') ?: (function_exists('env') ? env('FASTAPI_URL') : null);
            $this->baseUrl = rtrim((string) ($cfg ?: $envUrl), '/');
        }
    }

    /**
     * @param array{type:string, title?:string|null, text?:string, media_url?:string|null} $payload
     * @return array
     */
    public function summarize(array $payload): array
    {
        $endpoint = $this->baseUrl . '/summarize';
        try {
            $response = $this->http->post($endpoint, [
                'json' => $payload,
                'http_errors' => false,
            ]);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            // Provide a clearer message for connection issues
            throw new \RuntimeException("Cannot reach FastAPI at {$endpoint}. Please ensure FASTAPI_URL is correct and the service is running. Original error: " . $e->getMessage(), previous: $e);
        } catch (\Throwable $e) {
            throw $e;
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if ($status >= 400) {
            // Try to extract FastAPI error details
            $error = json_decode($body, true);
            $detail = is_array($error) ? json_encode($error, JSON_UNESCAPED_UNICODE) : $body;
            throw new \RuntimeException("FastAPI summarize error ({$status}): {$detail}");
        }

        $data = json_decode($body, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Call FastAPI to summarize media (audio/video) uploads.
     * Endpoint: /summerize-media
     *
     * @param array{type:string, title?:string|null, file_path?:string|null, filename?:string|null} $payload
     * @return array
     */
    public function summarizeMedia(array $payload): array
    {
        $endpoint = $this->baseUrl . '/summerize-media';

        $filePath = $payload['file_path'] ?? null;
        if (!$filePath || !is_readable($filePath)) {
            throw new \InvalidArgumentException('Uploaded file is missing or not readable at path: ' . ($filePath ?? '[null]'));
        }
        $filename = $payload['filename'] ?? basename($filePath);

        $multipart = [
            [
                'name' => 'file',
                'contents' => fopen($filePath, 'r'),
                'filename' => $filename,
            ],
            [
                'name' => 'type',
                'contents' => (string) ($payload['type'] ?? 'media'),
            ],
        ];
        if (!empty($payload['title'])) {
            $multipart[] = [
                'name' => 'title',
                'contents' => (string) $payload['title'],
            ];
        }

        try {
            $response = $this->http->post($endpoint, [
                'multipart' => $multipart,
                'http_errors' => false,
            ]);
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            throw new \RuntimeException("Cannot reach FastAPI at {$endpoint}. Please ensure FASTAPI_URL is correct and the service is running. Original error: " . $e->getMessage(), previous: $e);
        } catch (\Throwable $e) {
            throw $e;
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        if ($status >= 400) {
            $error = json_decode($body, true);
            $detail = is_array($error) ? json_encode($error, JSON_UNESCAPED_UNICODE) : $body;
            throw new \RuntimeException("FastAPI summarizeMedia error ({$status}): {$detail}");
        }

        $data = json_decode($body, true);
        return is_array($data) ? $data : [];
    }
}
