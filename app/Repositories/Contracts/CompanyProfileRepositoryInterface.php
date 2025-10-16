<?php

namespace App\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface CompanyProfileRepositoryInterface extends BaseRepositoryInterface
{
    public function getCompanyProfile();
    public function updateCompanyProfile($attribute);
    public function updateStorage($attribute);
    public function getStorage();
    public function saveOpenAiKey($request);
    public function getOpenAiKey();
    public function saveGoogleGeminiApiKey($request);
    public function getGoogleGeminiApiKey();
    public function updateLicense($attribute);
    public function updateArchiveDocumentRetension($request);
    public function updateAllowPdfSignature($attribute);
    public function updateEmailLogRetentionPeriod($attribute);
    public function updateCronJobLogRetentionPeriod($attribute);
    public function updateLoginAuditRetentionPeriod($attribute);
    public function deleteLogs();
}
