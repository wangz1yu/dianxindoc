import { CommonModule } from '@angular/common';
import { HttpEventType, HttpResponse } from '@angular/common/http';
import { Component, Inject } from '@angular/core';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { EmailLogAttachments } from '@core/domain-classes/email-log-attachments';
import { EmailLogs } from '@core/domain-classes/email-logs';
import { TranslationService } from '@core/services/translation.service';
import { SharedModule } from '@shared/shared.module';
import { ToastrService } from 'ngx-toastr';
import { EmailLogService } from '../email-log.service';

@Component({
  selector: 'app-email-log-details',
  standalone: true,
  imports: [
    CommonModule,
    MatIconModule,
    MatButtonModule,
    MatDialogModule,
    SharedModule,
    ReactiveFormsModule,
  ],
  templateUrl: './email-log-details.component.html',
  styleUrl: './email-log-details.component.scss'
})
export class EmailLogDetailsComponent {

  body: FormControl = new FormControl();
  constructor(@Inject(MAT_DIALOG_DATA) public data: EmailLogs,
    private dialogRef: MatDialogRef<EmailLogDetailsComponent>,
    private emailLogService: EmailLogService,
    private toastrService: ToastrService,
    private translationService: TranslationService) {
    this.body.setValue(data.body);
  }

  close() {
    this.dialogRef.close();
  }


  downloadAttachment(attachment: EmailLogAttachments) {
    this.emailLogService.downloadAttachment(attachment.id)
      .subscribe(
        (event) => {
          if (event.type === HttpEventType.Response) {
            this.downloadFile(event, attachment.name);
          }
        },
        (error) => {
          this.toastrService.error(this.translationService.getValue('ERROR_WHILE_DOWNLOADING_DOCUMENT'));
        }
      );
  }

  private downloadFile(data: HttpResponse<Blob>, name: string) {
    const downloadedFile = new Blob([data.body], { type: data.body.type });
    const a = document.createElement('a');
    a.setAttribute('style', 'display:none;');
    document.body.appendChild(a);
    a.download = name;
    a.href = URL.createObjectURL(downloadedFile);
    a.target = '_blank';
    a.click();
    document.body.removeChild(a);
  }
}
