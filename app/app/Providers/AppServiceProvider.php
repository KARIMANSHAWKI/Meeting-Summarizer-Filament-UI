<?php

namespace App\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Increase upload limits to support larger audio/video files during Livewire uploads
        @ini_set('upload_max_filesize', env('UPLOAD_MAX_FILESIZE', '200M'));
        @ini_set('post_max_size', env('POST_MAX_SIZE', '210M'));
        @ini_set('memory_limit', env('UPLOAD_MEMORY_LIMIT', '512M'));
        @ini_set('max_execution_time', (string) env('UPLOAD_MAX_EXECUTION_TIME', 300));
        @ini_set('max_input_time', (string) env('UPLOAD_MAX_INPUT_TIME', 300));

        // Ensure public disk tmp and media directories exist for Livewire/FileUpload
        try {
            $public = Storage::disk('public');
            foreach (['livewire-tmp', 'meeting-media'] as $dir) {
                if (! $public->exists($dir)) {
                    $public->makeDirectory($dir);
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore in case storage is not set up yet; app will still function
        }

        // Ensure local disk tmp directory exists for Livewire temporary uploads when using 'local' disk
        try {
            $local = Storage::disk('local');
            if (! $local->exists('livewire-tmp')) {
                $local->makeDirectory('livewire-tmp');
            }
        } catch (\Throwable $e) {
            // Ignore silently; if storage isn't ready yet, Livewire will attempt to create on first upload
        }
    }
}
