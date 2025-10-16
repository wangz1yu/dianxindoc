<?php

namespace App\Http\Controllers;

use App\Installer\RequirementsChecker;
use App\Models\CompanyProfiles;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class UpdateController extends Controller
{
    public function index(RequirementsChecker $checker)
    {
        $this->clearCache();
        $isUpdateAvailable = false;
        $installed = $this->getInstalledVersion();
        $currentVerion = $this->getCurrentVersion();

        if (version_compare(
            $currentVerion,
            $installed
        ) != 0) {
            $isUpdateAvailable = true;
        }

        $requirements = collect([]);

        if ($isUpdateAvailable) {
            $requirementsCheck   = $checker->check();
            $extensions = $requirementsCheck['results']['php'];
            foreach ($extensions as $extension => $enabled) {
                if (!$enabled) {
                    $requirements[] =   [
                        'path' => $extension,
                        'result' => false,
                        'errorMessage' => 'PHP ' . $extension . ' extension is required.',
                    ];
                }
            }

            $directories = [
                '',
                'storage',
                'storage/app',
                'storage/logs',
                'storage/framework',
                'public',
            ];

            $baseDir = base_path();
            foreach ($directories as $directory) {
                $path = rtrim("$baseDir/$directory", '/');
                $writable = is_writable($path);
                if (!$writable) {
                    $result = [
                        'path' => $path,
                        'result' => false,
                        'errorMessage' => '',
                    ];
                    $result['errorMessage'] = is_dir($path)
                        ? 'Make this directory writable by giving it 755 or 777 permissions via file manager.'
                        : 'Make this directory writable by giving it 644 permissions via file manager.';
                    $requirements[] = $result;
                }
            }
        }

        return view('update.update')->with([
            'requirements' => $requirements,
            'isRequirementsErrors' => $requirements->count() > 0,
            'isUpdateAvailable' => $isUpdateAvailable
        ]);
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:cache');
    }

    public function update()
    {
        $currentVerion = $this->getCurrentVersion();

        // migrate database
        Artisan::call('migrate', [
            '--force' => true,
        ]);

        $this->RunSeeder();

        $this->clearCache();

        $this->validateCode();

        $this->setEnv('APP_VERSION', $currentVerion);

        return view('update.finish');
    }

    private function RunSeeder()
    {
        $seeders = [
            \LanguageSeeder::class,
            \PermissionSeederV2::class,
            \PermissionSeederV21::class,
            \PermissionSeederV22::class,
            \PermissionSeederV23::class,
            \PermissionSeederV24::class,
            \PermissionSeederV30::class,
            \PermissionSeederV31::class,
            \PermissionSeederV40::class,
            \PermissionSeederV50::class,
            \PermissionSeederV51::class,
            \PermissionSeederV52::class,
        ];

        foreach ($seeders as $seeder) {
            Artisan::call('db:seed', [
                '--class' => $seeder,
                '--force' => true,
            ]);
        }
    }

    private function getCurrentVersion(): string
    {
        return Config::get('constants.APP_VERSION');
    }

    private function getInstalledVersion(): string
    {
        return env('APP_VERSION', '1.0.0');
    }

    private function setEnv($key, $value)
    {
        $path = base_path('.env');

        if (is_bool(env($key))) {
            $old = env($key) ? 'true' : 'false';
        } else {
            $old = env($key);
        }

        if (file_exists($path)) {
            if ($old != null) {
                $newContent = str_replace(
                    "$key=" . $old,
                    "$key=" . $value,
                    file_get_contents($path)
                );
                file_put_contents($path, $newContent);
            } else {

                $newContent = file_get_contents($path);
                file_put_contents($path, $newContent . $key . "=" . $value);
            }
        }
    }

    private function validateCode()
    {
        try {
            $companyProfile = CompanyProfiles::first();
            if (!$companyProfile) {
                return;
            }
            $key = $companyProfile->licenseKey;
            $code = $companyProfile->purchaseCode;

            if (!$key || !$code) {
                return;
            }

            $result = $this->getCode($key, $code);
            if (!$result) {
                $companyProfile->licenseKey = '';
                $companyProfile->purchaseCode = '';
                $companyProfile->save();
            }
        } catch (\Throwable $th) {
            Log::error('License validation failed: ' . $th->getMessage());
        }
    }

    public function getCode($__0x61, $__0x62)
    {
        $__0x4d47543d = [
            'aHR0cHM6Ly9hcGlsaWNlbnNlLm1sZ2xvYnRlY2guY29tL2FwaS9hdXRoL2NoZWNrbGljZW5zZS8=',
            'TGljZW5zZSB2YWxpZGF0aW9uIGZhaWxlZDog',
            'Wm1Gc2MyVT0='
        ];

        $__0x63 = function () use ($__0x61, $__0x62, $__0x4d47543d) {
            $__0x64 = base64_decode($__0x4d47543d[0]) . $__0x61 . chr(0x2F) . $__0x62;

            $__0x65 = Http::withoutVerifying();
            $__0x66 = $__0x65->get($__0x64, []);

            if ($__0x66->successful()) {
                $__0x67 = $__0x66->body() ?? base64_decode($__0x4d47543d[2]);
                return filter_var($__0x67, FILTER_VALIDATE_BOOLEAN);
            }
            return !!(0x0);
        };

        try {
            return $__0x63();
        } catch (\Throwable $__0x68) {
            Log::error(base64_decode($__0x4d47543d[1]) . $__0x68->getMessage());
            return !!(0x0);
        }
    }
}
