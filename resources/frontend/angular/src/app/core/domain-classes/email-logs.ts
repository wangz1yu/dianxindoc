import { EmailLogAttachments } from "./email-log-attachments";

export class EmailLogs {
    id?: string;
    senderEmail: string;
    recipientEmail: string;
    subject: string;
    body: string;
    status: string;
    sentAt: Date;
    errorMessage?: string;
    emailLogAttachments?: EmailLogAttachments[];
}