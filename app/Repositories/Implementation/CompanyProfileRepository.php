<?php

namespace App\Repositories\Implementation;

use App\Models\CompanyProfiles;
use App\Models\CronJobLogs;
use App\Models\EmailLogs;
use App\Models\Languages;
use App\Models\LoginAudit;
use App\Models\Users;
use App\Repositories\Implementation\BaseRepository;
use App\Repositories\Contracts\CompanyProfileRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class CompanyProfileRepository extends BaseRepository implements CompanyProfileRepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor..
     *
     *
     * @param Model $model
     */


    public static function model()
    {
        return CompanyProfiles::class;
    }

    public function getCompanyProfile()
    {
        $languages = Languages::select(['languages.code', 'languages.name', 'languages.imageUrl', 'languages.order', 'languages.isRTL'])->orderBy('languages.order')->get();
        try {
            $model = $this->model->first();
            if ($model == null) {
                $authUser = Auth::user();
                if ($authUser) {
                    $model = CompanyProfiles::create([
                        'logoUrl' =>  '',
                        'title' => 'Document Management System',
                        'location' =>  'local',
                        'smallLogoUrl' => '',
                        'allowPdfSignature' => true,
                    ]);
                    $model->save();
                    $result = $this->parseResult($model);
                    $result->languages = $languages;
                    $result->isS3Supported = env('AWS_ACCESS_KEY_ID') ? true : false;
                    $result->id = (string)$result->id;
                    return $result;
                }

                // If no authenticated user, create a default company profile
                $user = Users::first();
                if ($user) {
                    $model = CompanyProfiles::create([
                        'logoUrl' =>  '',
                        'title' => 'Document Management System',
                        'location' =>  'local',
                        'smallLogoUrl' => '',
                        'createdBy' => $user->id,
                        'allowPdfSignature' => true,
                    ]);
                    $model->save();
                    $result = $this->parseResult($model);
                    $result->languages = $languages;
                    $result->isS3Supported = env('AWS_ACCESS_KEY_ID') ? true : false;
                    $result->id = (string)$result->id;
                    return $result;
                }
                return [
                    'logoUrl' =>  '',
                    'title' => 'Document Management System',
                    'languages' => $languages,
                    'location' =>  'local',
                    'smallLogoUrl' => '',
                    'isS3Supported' => env('AWS_ACCESS_KEY_ID') ? true : false,
                    'allowPdfSignature' => true,
                ];
            }

            $model->languages = $languages;
            $model->isS3Supported = true;
            $model->id = (string)$model->id;
            if ($model->location == 'local') {
                $model->isS3Supported = env('AWS_ACCESS_KEY_ID') ? true : false;
            }
            return $model;
        } catch (\Throwable $th) {
            return [
                'logoUrl' =>  '',
                'title' => 'Document Management System',
                'languages' => $languages,
                'location' =>  'local',
                'smallLogoUrl' => '',
                'allowPdfSignature' => true,
            ];
        }
    }


    public function updateCompanyProfile($request)
    {
        $languages = Languages::select(['languages.code', 'languages.name', 'languages.imageUrl', 'languages.order', 'languages.isRTL'])->get();
        $model = $this->model->first();
        $logo = '';
        $banner = '';
        if ($request['imageData']) {
            $logo = $this->saveCompanyProfileImage($request['imageData']);
        } else {
            $logo = $model->logoUrl;
        }

        if ($request['bannerData']) {
            $banner = $this->saveCompanyProfileImage($request['bannerData']);
        } else {
            $banner = $model->bannerUrl;
        }

        if ($request['smallLogoData']) {
            $smallLogo = $this->saveCompanyProfileImage($request['smallLogoData']);
        } else {
            $smallLogo = $model->smallLogoUrl;
        }

        if ($model == null) {
            $model = $this->model->newInstance($request);
            $model->title = $request['title'];
            $model->logoUrl = $logo;
            $model->bannerUrl = $banner;
            $model->smallLogoUrl = $smallLogo;
            $model->save();
            $result = $this->parseResult($model);
            $result->languages = $languages;
            return $result;
        } else {
            $model->logoUrl = $logo;
            $model->bannerUrl = $banner;
            $model->smallLogoUrl = $smallLogo;
            $model->title = $request['title'];
            $model->save();
            $result = $this->parseResult($model);
            $result->languages = $languages;
            return $result;
        }
    }

    public function updateStorage($request)
    {
        $model = $this->model->first();
        $oldLocation = $model->location;

        if ($request['location'] == 'local') {
            $model->location = $request['location'];
            $model->save();
            return response()->json([]);
        }

        $oldConfig = collect(
            [
                'AWS_ACCESS_KEY_ID' => env('AWS_ACCESS_KEY_ID'),
                'AWS_SECRET_ACCESS_KEY' => env('AWS_SECRET_ACCESS_KEY'),
                'AWS_DEFAULT_REGION' => env('AWS_DEFAULT_REGION'),
                'AWS_BUCKET' => env('AWS_BUCKET'),
            ]
        );

        try {

            try {
                if (!Storage::disk('local')->exists('sample-test-file.txt')) {
                    Storage::disk('local')->put('sample-test-file.txt', 'this is test file to check connection');
                }

                $client = new S3Client([
                    'credentials' => [
                        'key'    => $request['amazonS3key'],
                        'secret' => $request['amazonS3secret'],
                    ],
                    'region' => $request['amazonS3region'],
                    'version' => 'latest',
                ]);

                $client->putObject([
                    'Bucket' => $request['amazonS3bucket'],
                    'Key'    => 'sample-test-file.txt',
                    'Body'   => Storage::disk('local')->get('sample-test-file.txt'),
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error in connecting to s3 bucket with given config',
                ], 409);
            }

            // try {

            //     if (!Storage::disk('local')->exists('sample-test-file.txt')) {
            //         Storage::disk('local')->put('sample-test-file.txt', 'this is test file to check connection');
            //     }

            //     config([
            //         'filesystems.disks.s3.bucket' => $request['amazonS3bucket'],
            //         'filesystems.disks.s3.region' => $request['amazonS3region'],
            //         'filesystems.disks.s3.key' => $request['amazonS3key'],
            //         'filesystems.disks.s3.secret' => $request['amazonS3secret'],
            //     ]);

            //     $filePath = Storage::path('local') . 'sample-test-file.txt';
            //     Storage::disk('s3')->put('sample-test-file.txt', file_get_contents($filePath));
            // } catch (\Exception $th) {
            //     // $this->setEnvs($oldConfig);
            //     return response()->json([
            //         'message' => 'Error in connecting to s3 bucket with given config',
            //     ], 409);
            // }

            try {
                $config = collect(
                    [
                        'AWS_ACCESS_KEY_ID' => $request['amazonS3key'],
                        'AWS_SECRET_ACCESS_KEY' => $request['amazonS3secret'],
                        'AWS_DEFAULT_REGION' => $request['amazonS3region'],
                        'AWS_BUCKET' => $request['amazonS3bucket'],
                    ]
                );
                $this->setEnvs($config);
            } catch (\Throwable $th) {
                return response()->json([
                    'message' => 'Error in saving .evn File' . $th->getMessage(),
                ], 409);
            }

            $model->location = $request['location'];
            $model->save();

            return response()->json([]);
        } catch (\Throwable $th) {

            // revert config settings.
            $this->setEnvs($oldConfig);

            // revert location settings.
            $model->location = $oldLocation;
            $model->save();

            return response()->json([
                'message' => 'Error in saving data.' . $th->getMessage(),
            ], 409);
        }
    }

    public function getStorage()
    {
        $model = $this->model->first();
        if ($model == null) {
            return response()->json([]);
        }

        return response()->json([
            'location' => $model->location ?? 'local',
            'amazonS3key' => env('AWS_ACCESS_KEY_ID'),
            'amazonS3secret' => env('AWS_SECRET_ACCESS_KEY'),
            'amazonS3region' => env('AWS_DEFAULT_REGION'),
            'amazonS3bucket' => env('AWS_BUCKET'),
        ]);
    }

    public function getOpenAiKey()
    {
        return response()->json([
            'openApiKey' => env('OPENAI_API_KEY'),
        ]);
    }

    public function saveOpenAiKey($request)
    {
        $key = $request->input('openApiKey');
        $envPath = base_path('.env');

        if (File::exists($envPath)) {
            // Replace or add the key
            $envContent = File::get($envPath);
            $pattern = '/^OPENAI_API_KEY=.*$/m';

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "OPENAI_API_KEY={$key}", $envContent);
            } else {
                $envContent .= "\nOPENAI_API_KEY={$key}";
            }

            File::put($envPath, $envContent);

            return response()->json(['message' => 'API key saved to .env file']);
        }

        return response()->json(['error' => '.env file not found'], 500);
    }

    public function getGoogleGeminiApiKey()
    {
        return response()->json([
            'googleGeminiApiKey' => env('GEMINI_API_KEY'),
        ]);
    }

    public function saveGoogleGeminiApiKey($request)
    {
        $key = $request->input('googleGeminiApiKey');
        $envPath = base_path('.env');

        if (File::exists($envPath)) {
            // Replace or add the key
            $envContent = File::get($envPath);
            $pattern = '/^GEMINI_API_KEY=.*$/m';

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "GEMINI_API_KEY={$key}", $envContent);
            } else {
                $envContent .= "\nGEMINI_API_KEY={$key}";
            }

            File::put($envPath, $envContent);

            return response()->json(['message' => 'API key saved to .env file']);
        }

        return response()->json(['error' => '.env file not found'], 500);
    }

    private function saveCompanyProfileImage($image_64)
    {
        try {
            $extension = explode('/', explode(':', substr($image_64, 0, strpos($image_64, ';')))[1])[1];

            $replace = substr($image_64, 0, strpos($image_64, ',') + 1);

            $image = str_replace($replace, '', $image_64);

            $image = str_replace(' ', '+', $image);

            $destinationPath = public_path() . '/images//';

            $imageName = Uuid::uuid4() . '.' . $extension;

            file_put_contents($destinationPath . $imageName,  base64_decode($image));
            return 'images/' . $imageName;
        } catch (\Exception $e) {
            return '';
        }
    }


    private function setEnvs($config)
    {
        $path = base_path('.env');

        $keys = $config->keys();
        foreach ($keys as $key) {
            if (is_bool(env($key))) {
                $old = env($key) ? 'true' : 'false';
            } elseif (env($key) === null) {
                $old = 'null';
            } else {
                $old = env($key);
            }

            $fileContents = file_get_contents($path);
            if (file_exists($path)) {
                $fileContents = str_replace(
                    "$key=" . $old,
                    "$key=" . $config[$key],
                    $fileContents
                );
            }
            file_put_contents($path, $fileContents);
        }
    }

    public function updateLicense($attribute)
    {
        $model = $this->model->first();

        $user = Users::first();

        if (!$user) {
            return response()->json(['message' => 'Installation is pending'], 404);
        }

        if ($model == null) {
            CompanyProfiles::create([
                'logoUrl' =>  '',
                'title' => 'Document Management System',
                'location' =>  'local',
                'smallLogoUrl' => '',
                'createdBy' => $user->id,
                'licenseKey' => $attribute['licenseKey'],
                'purchaseCode' => $attribute['purchaseCode'],
                'allowPdfSignature' => true,
            ]);
            return response()->json([], 200);
        }

        $model->licenseKey = $attribute['licenseKey'];
        $model->purchaseCode = $attribute['purchaseCode'];
        $model->save();

        return response()->json([], 200);
    }

    public function updateArchiveDocumentRetension($request)
    {
        try {

            DB::beginTransaction();

            $companyProfile = $this->model->first();
            if (!$companyProfile) {
                throw new \Exception("Company profile not found.");
            }

            $companyProfile->archiveDocumentRetensionPeriod = $request->period ?? 0;
            $companyProfile->save();

            DB::commit();

            return response()->json([
                'message' => 'Archive Document Retention Period updated successfully',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
            return response()->json([
                'message' => $th->getMessage(),
            ], 409);
        }
    }

    public function updateAllowPdfSignature($attribute)
    {
        $model = $this->model->first();
        if ($model == null) {
            return response()->json([], 404);
        }

        $model->allowPdfSignature = filter_var($attribute['allowPdfSignature'], FILTER_VALIDATE_BOOLEAN);
        $model->save();

        return response()->json([], 200);
    }

    public function updateEmailLogRetentionPeriod($attribute)
    {
        $model = $this->model->first();
        if ($model == null) {
            return response()->json([], 200);
        }

        $model->emailLogRetentionPeriod = $attribute['retentionPeriod'];
        $model->save();

        return response()->json([], 200);
    }

    public function updateCronJobLogRetentionPeriod($attribute)
    {
        $model = $this->model->first();
        if ($model == null) {
            return response()->json([], 200);
        }

        $model->cronJobLogRetentionPeriod = $attribute['retentionPeriod'];
        $model->save();

        return response()->json([], 200);
    }

    public function updateLoginAuditRetentionPeriod($attribute)
    {
        $model = $this->model->first();
        if ($model == null) {
            return response()->json([], 200);
        }

        $model->loginAuditRetentionPeriod = $attribute['retentionPeriod'];
        $model->save();

        return response()->json([], 200);
    }

    public function deleteLogs()
    {
        $model = $this->model->first();
        if ($model == null) {
            return response()->json([], 200);
        }

        // Delete Email Logs
        if ($model->emailLogRetentionPeriod) {
            try {
                $emailLogs = EmailLogs::where('sentAt', '<', now()->subDays($model->emailLogRetentionPeriod))
                    ->with('emailLogAttachments')
                    ->get();

                foreach ($emailLogs as $log) {
                    if (isset($log->emailLogAttachments)) {
                        foreach ($log->emailLogAttachments as $attachment) {
                            try {
                                $path = storage_path('app') . $attachment->path;
                                if (file_exists($path)) {
                                    unlink($path);
                                }
                            } catch (\Throwable $th) {
                                //ignore
                            }
                            $attachment->delete();
                        }
                    }
                    $log->delete();
                }
            } catch (\Throwable $th) {
                // ignore
            }
        }

        // Delete Cron Job Logs
        if ($model->cronJobLogRetentionPeriod) {
            try {
                CronJobLogs::where('startedAt', '<', now()->subDays($model->cronJobLogRetentionPeriod))->delete();
            } catch (\Throwable $th) {
                // ignore
            }
        }

        // Delete Login Audit Logs
        if ($model->loginAuditRetentionPeriod) {
            try {
                LoginAudit::where('loginTime', '<', now()->subDays($model->loginAuditRetentionPeriod))->delete();
            } catch (\Throwable $th) {
                // ignore
            }
        }

        return response()->json([], 200);
    }
}
