<?php
//
//namespace App\Console\Commands;
//
//use App\Services\FastApiClient;
//use Illuminate\Console\Command;
//
//class TestFastApi extends Command
//{
//    protected $signature = 'fastapi:test {--text=Hello world}';
//
//    protected $description = 'Test connectivity to the configured FastAPI service and print the response or error.';
//
//    public function handle(FastApiClient $client): int
//    {
//        $payload = [
//            'type' => 'text',
//            'text' => (string) $this->option('text'),
//        ];
//
//        $this->info('FASTAPI_URL = ' . config('services.fastapi.url'));
//        try {
//            $result = $client->summarize($payload);
//            $this->info('Request succeeded. Response:');
//            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
//            return self::SUCCESS;
//        } catch (\Throwable $e) {
//            $this->error('Request failed: ' . $e->getMessage());
//            return self::FAILURE;
//        }
//    }
//}
