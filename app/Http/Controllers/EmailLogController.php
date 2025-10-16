<?php

namespace App\Http\Controllers;

use App\Models\EmailLogAttachments;
use App\Repositories\Contracts\EmailLogRepositoryInterface;
use App\Repositories\Exceptions\RepositoryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmailLogController extends Controller
{
    private $emailLogRepositoryInterface;

    public function __construct(EmailLogRepositoryInterface $emailLogRepositoryInterface)
    {
        $this->emailLogRepositoryInterface = $emailLogRepositoryInterface;
    }

    public function getEmailLogs(Request $request)
    {
        $queryString = (object) $request->all();

        $count = $this->emailLogRepositoryInterface->getEmailLogCount($queryString);
        return response()->json($this->emailLogRepositoryInterface->getEmailLogs($queryString))
            ->withHeaders(['totalCount' => $count, 'pageSize' => $queryString->pageSize, 'skip' => $queryString->skip]);
    }

    public function downloadAttachment($id)
    {
        $attachment = EmailLogAttachments::findOrFail($id);
        $fileupload = $attachment->path;

        if (Storage::exists($fileupload)) {
            $file_contents = Storage::disk('local')->get($fileupload);
            $fileType = Storage::mimeType($fileupload);

            $fileExtension = explode('.', $attachment->path);
            return response($file_contents)
                ->header('Cache-Control', 'no-cache private')
                ->header('Content-Description', 'File Transfer')
                ->header('Content-Type', $fileType)
                ->header('Content-length', strlen($file_contents))
                ->header('Content-Disposition', 'attachment; filename=' . $attachment->name . '.' . $fileExtension[1])
                ->header('Content-Transfer-Encoding', 'binary');
        } else {
            throw new RepositoryException('File Not Found.');
        }
    }

    public function deleteLog($id)
    {
        return $this->emailLogRepositoryInterface->deleteEmailLog($id);
    }
}
