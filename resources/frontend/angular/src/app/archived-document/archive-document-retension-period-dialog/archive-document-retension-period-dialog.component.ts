import { Component } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from '@shared/shared.module';
import { SecurityService } from '@core/security/security.service';
import { ArchiveDocumentService } from '../archive-document.service';
import { TranslationService } from '@core/services/translation.service';
import { CompanyProfile } from '@core/domain-classes/company-profile';

@Component({
  selector: 'app-archive-document-retension-period-dialog',
  standalone: true,
  imports: [
    TranslateModule,
    MatIconModule,
    FormsModule,
    MatDialogModule,
    SharedModule,
  ],
  templateUrl: './archive-document-retension-period-dialog.component.html',
  styleUrl: './archive-document-retension-period-dialog.component.scss'
})
export class ArchiveDocumentRetensionPeriodDialogComponent {
  retentionPeriod: number;
  companyProfile: CompanyProfile;
  constructor(
    public dialogRef: MatDialogRef<ArchiveDocumentRetensionPeriodDialogComponent>,
    private securityService: SecurityService,
    private archiveDocumentService: ArchiveDocumentService,
    private translationService: TranslationService
  ) {
    this.subscribeCompanyProfile();
  }

  subscribeCompanyProfile() {
    this.securityService.companyProfile.subscribe((companyProfile: CompanyProfile) => {
      this.companyProfile = companyProfile;
      this.retentionPeriod = companyProfile.archiveDocumentRetensionPeriod;
    });
  }

  closeDialog() {
    this.dialogRef.close();
  }

  saveArchiveSettings() {
    this.archiveDocumentService
      .archiveDocument(this.retentionPeriod)
      .subscribe(() => {
        this.translationService.getValue('DOCUMENT_ARCHIVED_SETTINGS_SUCCESSFULLY_SAVED');
        this.companyProfile.archiveDocumentRetensionPeriod = this.retentionPeriod;
        this.securityService.updateProfile(this.companyProfile);
        this.closeDialog();
      });
  }
}
