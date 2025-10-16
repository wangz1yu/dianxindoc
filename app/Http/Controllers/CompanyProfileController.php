<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Contracts\CompanyProfileRepositoryInterface;

class CompanyProfileController extends Controller
{
    private $companyProfileRepository;

    public function __construct(CompanyProfileRepositoryInterface $companyProfileRepository)
    {
        $this->companyProfileRepository = $companyProfileRepository;
    }

    public function getCompanyProfile()
    {
        try {
            return response()->json($this->companyProfileRepository->getCompanyProfile());
        } catch (\Throwable $th) {
            return response()->json(['Message' => "Error fetching company profile: Please make sure installation has been finished successfully."], 500);
        }
    }

    public function updateCompanyProfile(Request $request)
    {
        return response()->json($this->companyProfileRepository->updateCompanyProfile($request->all()));
    }

    public function updateStorage(Request $request)
    {
        return $this->companyProfileRepository->updateStorage($request->all());
    }

    public function getStorage()
    {
        return $this->companyProfileRepository->getStorage();
    }

    public function getOpenAiKey()
    {
        return $this->companyProfileRepository->getOpenAiKey();
    }


    public function saveOpenAiKey(Request $request)
    {
        return $this->companyProfileRepository->saveOpenAiKey($request);
    }

    public function getGoogleGeminiApiKey()
    {
        return $this->companyProfileRepository->getGoogleGeminiApiKey();
    }

    public function saveGoogleGeminiApiKey(Request $request)
    {
        return $this->companyProfileRepository->saveGoogleGeminiApiKey($request);
    }

    public function updateLicense(Request $request)
    {
        return response()->json($this->companyProfileRepository->updateLicense($request));
    }

    public function updateArchiveDocumentRetension(Request $request)
    {
        return response()->json($this->companyProfileRepository->updateArchiveDocumentRetension($request));
    }

    public function updateAllowPdfSignature(Request $request)
    {
        return $this->companyProfileRepository->updateAllowPdfSignature($request->all());
    }

    public function updateEmailLogRetentionPeriod(Request $request)
    {
        return $this->companyProfileRepository->updateEmailLogRetentionPeriod($request->all());
    }

    public function updateCronJobLogRetentionPeriod(Request $request)
    {
        return $this->companyProfileRepository->updateCronJobLogRetentionPeriod($request->all());
    }

    public function updateLoginAuditRetentionPeriod(Request $request)
    {
        return $this->companyProfileRepository->updateLoginAuditRetentionPeriod($request->all());
    }
}
