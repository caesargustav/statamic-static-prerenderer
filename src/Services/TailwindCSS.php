<?php

namespace Caesargustav\StaticPrerenderer\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class TailwindCSS
{
    public function __construct(protected string $binaryPath)
    {
    }

    public function process(string $output): ?string
    {
        $path = app()->resourcePath('tailwind.config.js');
        $config = File::exists($path) ? $path : '../resources/tailwind.config.js';

        $command = sprintf(
            './%s --input %s --output %s --config %s',
            self::getBinaryName(),
            $this->binaryPath . '/../resources/app.css',
            Storage::path($output),
            $config
        );

        Process::path($this->binaryPath)
            ->run($command)
            ->throw();

        return Storage::get($output);
    }

    public function downloadBinary(): void
    {
        try {
            $binaryName = self::getBinaryName();
            $fullBinaryName = $this->getFullBinaryPath();

            if (File::exists($fullBinaryName)) {
                return;
            }

            $latestRelease = Http::get('https://api.github.com/repos/tailwindlabs/tailwindcss/releases/latest')
                ->collect('assets')
                ->filter(fn ($asset) => $asset['name'] === $binaryName)
                ->first();

            $latestReleaseBinary = Http::get($latestRelease['browser_download_url']);

            File::put($fullBinaryName, $latestReleaseBinary->body());
            File::chmod($fullBinaryName, 0755);
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * @throws \Exception
     */
    private static function getBinaryName(): string
    {
        $os = strtolower(php_uname('s'));
        $machineType = php_uname('m');

        $osMap = [
            'linux' => 'linux',
            'darwin' => 'macos',
        ];

        $machineMap = [
            'x86_64' => 'x64',
            'arm64' => 'arm64',
            'armv7l' => 'armv7',
            'aarch64' => 'arm64',
        ];

        if (! isset($osMap[$os]) || ! isset($machineMap[$machineType])) {
            throw new \Exception('Unsupported OS or architecture.');
        }

        return sprintf('tailwindcss-%s-%s', $osMap[$os], $machineMap[$machineType]);
    }

    private function getFullBinaryPath(): string
    {
        return $this->binaryPath . '/' . self::getBinaryName();
    }
}
