<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfiles;
use Illuminate\Http\Request;
use App\Repositories\Contracts\DocumentSignatureRepositoryInterface;
use App\Models\Documents;
use App\Models\DocumentSignature;
use App\Models\DocumentVersions;
use App\Models\Users;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Faker\Provider\ar_EG\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class DocumentSignatureController extends Controller
{
    private $documentSignatureRepository;

    public function __construct(DocumentSignatureRepositoryInterface $documentSignatureRepository)
    {
        $this->documentSignatureRepository = $documentSignatureRepository;
    }

    public function signDocument(Request $request)
    {
        try {
            $request->validate([
                'documentId' => 'required|uuid',
                'signatureUrl' => 'required|string'
            ]);

            $document = Documents::findOrFail($request->documentId);
            $extension = pathinfo($document->url, PATHINFO_EXTENSION);

            if ($document->location == 's3') {
                $s3Key = config('filesystems.disks.s3.key');
                $s3Secret = config('filesystems.disks.s3.secret');
                $s3Region = config('filesystems.disks.s3.region');
                $s3Bucket = config('filesystems.disks.s3.bucket');

                if (empty($s3Key) || empty($s3Secret) || empty($s3Region) || empty($s3Bucket)) {
                    return response()->json([
                        'message' => 'Error: S3 configuration is missing',
                    ], 409);
                }
            }

            $userId = Auth::parseToken()->getPayload()->get('userId');
            $user = Users::findOrFail($userId);

            // Signature layout settings
            $boxWidth = 30;
            $boxHeight = 20;
            $marginX = 5;
            $marginY = 5;
            $startX = 10;
            $bottomMargin = 15;
            $maxColumns = 5;
            $signatureDataArray = explode(",", $request->signatureUrl);

            // Find extension from base64 data
            $mimeType = 'png';
            if (isset($signatureDataArray[0])) {
                if (preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64$/', $signatureDataArray[0], $matches)) {
                    $mimeType = $matches[1];
                    // Remove "image/" prefix if present
                    if (strpos($mimeType, 'image/') === 0) {
                        $mimeType = substr($mimeType, 6);
                    }
                }
            }
            $fileName = Str::uuid() . '.' . $mimeType;
            $signDir = storage_path('app' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'signature');
            if (!is_dir($signDir)) {
                mkdir($signDir, 0777, true);
            }
            $signFilePath = 'documents' . DIRECTORY_SEPARATOR . 'signature' . DIRECTORY_SEPARATOR . $fileName;

            // Decode and store signature image
            $signatureData = $signatureDataArray[1] ?? '';
            $signatureData = str_replace(' ', '+', $signatureData);
            $signatureImage = base64_decode($signatureData);
            $signaturePath = $signDir . DIRECTORY_SEPARATOR . $fileName;
            file_put_contents($signaturePath, $signatureImage);

            DocumentSignature::create([
                'id' => Str::uuid(),
                'documentId' => $request->documentId,
                'signatureUrl' => $signFilePath,
                'createdBy' => $userId,
                'createdDate' => Carbon::now()
            ]);

            $companyProfile = CompanyProfiles::first();

            if ($extension == 'pdf' && $companyProfile && $companyProfile->allowPdfSignature) {
                // Get the PDF file content from the correct storage
                if ($document->location == 's3') {
                    $pdfContent = Storage::disk('s3')->get($document->url);
                    // Store temporarily for FPDI
                    $tmpPdfPath = storage_path('app/tmp_' . uniqid() . '.pdf');
                    file_put_contents($tmpPdfPath, $pdfContent);
                    $pdfPath = $tmpPdfPath;
                } else {
                    $pdfPath = Storage::disk($document->location)->path($document->url);
                }

                $pdf = new Fpdi();
                $pageCount = $pdf->setSourceFile($pdfPath);

                // Count existing signatures by matching "Digitally Signed By :"
                $existingSignatureCount = 0;
                try {
                    $reader = new \Smalot\PdfParser\Parser();
                    $parsedPdf = $reader->parseFile($pdfPath);
                    $lastPageText = $parsedPdf->getPages()[$pageCount - 1]->getText();
                    preg_match_all('/Digitally Signed By\s*:/i', $lastPageText, $matches);
                    $existingSignatureCount = count($matches[0]);
                } catch (\Throwable $e) {
                    $existingSignatureCount = 0;
                }

                // Render all pages and capture last page size
                $lastPageSize = null;
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);

                    if ($pageNo === $pageCount) {
                        $lastPageSize = $size;
                    }
                }

                // Bottom to Top, Left to Right logic
                $signatureCount = $existingSignatureCount;
                $row = intdiv($signatureCount, $maxColumns);
                $col = $signatureCount % $maxColumns;

                $signatureX = $startX + $col * ($boxWidth + $marginX);
                $bottomY = $lastPageSize['height'] - $bottomMargin;
                $signatureY = $bottomY - ($row + 1) * ($boxHeight + $marginY);

                // Draw signature
                $this->drawSignatureBlock($pdf, $signatureX, $signatureY, $boxWidth, $boxHeight, $signaturePath, $user);

                $newFileName = Str::uuid() . '.pdf';
                $newFileRelativePath = 'documents' . DIRECTORY_SEPARATOR . $newFileName;

                if ($document->location == 's3') {
                    // Save to temp file, then upload to S3
                    $tmpSignedPdfPath = storage_path('app' . DIRECTORY_SEPARATOR . $newFileRelativePath);
                    $pdf->Output($tmpSignedPdfPath, 'F');
                    $signedPdfContent = file_get_contents($tmpSignedPdfPath);
                    Storage::disk('s3')->put(str_replace(DIRECTORY_SEPARATOR, '/', $newFileRelativePath), $signedPdfContent);
                    // Clean up temp files
                    @unlink($tmpSignedPdfPath);
                    @unlink($pdfPath); // remove the temp original
                } else {
                    // Save locally
                    $pdf->Output(storage_path('app' . DIRECTORY_SEPARATOR . $newFileRelativePath), 'F');
                }

                // Save document version
                $model = DocumentVersions::create([
                    'url' => $document->url,
                    'documentId' => $document->id,
                    'createdBy' => $document->createdBy,
                    'modifiedBy' => $document->modifiedBy,
                    'location' => $document->location,
                    'signById' => $document->signById,
                    'signDate' => $document->signDate
                ]);

                $model->createdDate = $document->createdDate;
                $model->modifiedDate = $document->modifiedDate;
                $model->save();

                $document->url = $newFileRelativePath;
                $document->createdDate = Carbon::now()->addSeconds(2);
                $document->modifiedDate = Carbon::now()->addSeconds(2);
                $document->createdBy = $userId;
            }

            $document->signById = $userId;
            $document->signDate = Carbon::now();
            $document->save();

            return response()->json([], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Signing failed: ' . $e->getMessage(),
            ], 500);
        }
    }


    private function drawSignatureBlock($pdf, $x, $y, $width, $height, $signaturePath, $user)
    {
        $pdf->Rect($x, $y, $width, $height);

        // Heights for each section
        $labelHeight = 3;
        $nameHeight = 3;
        $timestampHeight = 3;
        $padding = 1.5;

        // Calculate available height for signature image
        $usedHeight = $labelHeight + $nameHeight + $timestampHeight + ($padding * 4);
        $availableImgHeight = max(2, $height - $usedHeight);

        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetTextColor(255, 0, 0);
        $pdf->SetXY($x + 1, $y + $padding);
        $pdf->Cell($width - 2, $labelHeight, 'Digitally Signed By :', 0, 1);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x + 1, $y + $padding + $labelHeight + $padding);
        $pdf->Cell($width - 2, $nameHeight, $user->firstName . ' ' . $user->lastName, 0, 1);

        // Signature image placement
        $sigImgWidth = $width - 6;
        $sigImgHeight = $availableImgHeight;
        $sigImgX = $x + (($width - $sigImgWidth) / 2);
        $sigImgY = $y + $padding + $labelHeight + $padding + $nameHeight + $padding;
        if ($sigImgHeight > 0) {
            $pdf->Image($signaturePath, $sigImgX, $sigImgY, $sigImgWidth, $sigImgHeight);
        }

        // Timestamp at the bottom
        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetXY($x + 1, $y + $height - $timestampHeight - $padding);
        $pdf->Cell($width - 2, $timestampHeight, now()->format('Y-m-d H:i:s') . ' (UTC)', 0, 1);
    }

    public function getDocumentSignature($id)
    {
        return $this->documentSignatureRepository->getDocumentSignature($id);
    }
}
