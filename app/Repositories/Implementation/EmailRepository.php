<?php

namespace App\Repositories\Implementation;

use App\Repositories\Contracts\EmailRepositoryInterface;
use App\Models\EmailSMTPSettings;
use PHPMailer\PHPMailer\PHPMailer;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

//use Your Model

/**
 * Class ActionsRepository.
 */
class EmailRepository  implements EmailRepositoryInterface
{
    public $emailLogRepository;

    function __construct(EmailLogRepository $emailLogRepository)
    {
        $this->emailLogRepository = $emailLogRepository;
    }

    public function sendEmail($attribute)
    {
        $smtpSettings = EmailSMTPSettings::where('isDefault', 1)->first();

        if ($smtpSettings) {
            $mail = new  PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtpSettings['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpSettings['userName'];
            $mail->Password   = $smtpSettings['password'];
            $mail->SMTPSecure = $smtpSettings['encryption'];
            $mail->Port       = $smtpSettings['port'];
            $mail->addAddress($attribute['to_address']);
            $mail->setFrom($smtpSettings['fromEmail'], $smtpSettings['fromName'] ?? $smtpSettings['fromEmail']);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $attribute['subject'];
            $mail->Body    = $attribute['message'];
            $mail->AltBody = $attribute['message'];
            $mail->Sendmail   = '/usr/sbin/sendmail -bs';

            $emailLogAttachments = [];
            if ($attribute['path'] != null) {
                $file_contents = Storage::disk($attribute['location'])->get($attribute['doc_url']);
                $mail->addStringAttachment($file_contents, $attribute['file_name']);

                $path = $this->saveEmailAttachment($file_contents, $attribute['file_name']);
                if ($path) {
                    $emailLogAttachments[] = [
                        'path' => $path,
                        'name' => $attribute['file_name'],
                    ];
                }
            }

            $emailLog = [
                'senderEmail' => $smtpSettings['userName'],
                'recipientEmail' => $attribute['to_address'],
                'subject' => $attribute['subject'],
                'body' => $attribute['message']
            ];

            try {
                $mail->send();
                $emailLog['status'] = 'sent';
            } catch (\Throwable $th) {
                $emailLog['status'] = 'failed';
                $emailLog['errorMessage'] = $th->getMessage();
            }
            $this->emailLogRepository->createLog($emailLog, $emailLogAttachments);
        }
    }

    public function saveEmailAttachment($file_contents, $name)
    {
        try {
            $destinationPath = storage_path() . '/app/email_attachments/';

            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }

            $extneion =  pathinfo($name, PATHINFO_EXTENSION);
            $path =  Uuid::uuid4() . '.' . $extneion;
            $fullPath = $destinationPath . $path;
            file_put_contents($fullPath, $file_contents);
            return '/email_attachments/' . $path;
        } catch (\Throwable $th) {
            return '';
        }
    }
}
