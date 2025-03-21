<?php

namespace Caesargustav\StaticPrerenderer\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class TailwindCSS
{
    public function __construct(protected string $binaryPath) {}

    public function process(string $output): ?string
    {
        $customTailwindCssConfigPath = app()->resourcePath('tailwind.config.js');
        $tailwindCssConfigPath = File::exists($customTailwindCssConfigPath) ? $customTailwindCssConfigPath : '../resources/tailwind.config.js';

        $customCssPath = app()->resourcePath('css/headless.css');
        $cssPath = File::exists($customCssPath) ? $customCssPath : $this->binaryPath . '/../resources/css/headless.css';

        $command = sprintf(
            './%s --input %s --output %s --config %s',
            self::getBinaryName(),
            $cssPath,
            Storage::path($output),
            $tailwindCssConfigPath
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

            // Pin release to v3.4.17
            $latestRelease = Http::get('https://api.github.com/repos/tailwindlabs/tailwindcss/releases/191250475')
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
        return $this->binaryPath.'/'.self::getBinaryName();
    }
}
