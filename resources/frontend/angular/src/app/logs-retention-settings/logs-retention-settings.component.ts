import { Component, Inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { CompanyProfile } from '@core/domain-classes/company-profile';
import { SecurityService } from '@core/security/security.service';
import { TranslationService } from '@core/services/translation.service';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from '@shared/shared.module';
import { LogRetentionService } from './log-retention-serttings.service';
import { ToastrService } from 'ngx-toastr';

@Component({
  selector: 'app-logs-retention-settings',
  standalone: true,
  imports: [TranslateModule,
    MatIconModule,
    FormsModule,
    MatDialogModule,
    SharedModule,],
  templateUrl: './logs-retention-settings.component.html',
  styleUrl: './logs-retention-settings.component.scss'
})
export class LogsRetentionSettingsComponent {
  companyProfile: CompanyProfile;
  retentionPeriod: number;

  constructor(@Inject(MAT_DIALOG_DATA) public data: { type: string },
    public dialogRef: MatDialogRef<LogsRetentionSettingsComponent>,
    private securityService: SecurityService,
    private logRetentionService: LogRetentionService,
    private translationService: TranslationService,
    private toastrService: ToastrService) {
    this.subscribeCompanyProfile();
  }

  subscribeCompanyProfile() {
    this.securityService.companyProfile.subscribe((companyProfile: CompanyProfile) => {
      this.companyProfile = companyProfile;
      this.retentionPeriod = this.data.type === 'login-audit-log-setting' ? companyProfile.loginAuditRetentionPeriod :
        this.data.type === 'email-log-setting' ? companyProfile.emailLogRetentionPeriod :
          this.data.type === 'cron-job-log-setting' ? companyProfile.cronJobLogRetentionPeriod : null;
    });
  }

  closeDialog() {
    this.dialogRef.close();
  }

  saveSettings() {
    this.logRetentionService
      .saveLogRetentionSettings(this.retentionPeriod, this.data.type)
      .subscribe(() => {
        this.toastrService.success(this.translationService.getValue('RETENTION_SETTINGS_SUCCESSFULLY_SAVED'));
        if (this.data.type === 'login-audit-log-setting') {
          this.companyProfile.loginAuditRetentionPeriod = this.retentionPeriod;
        } else if (this.data.type === 'email-log-setting') {
          this.companyProfile.emailLogRetentionPeriod = this.retentionPeriod;
        } else if (this.data.type === 'cron-job-log-setting') {
          this.companyProfile.cronJobLogRetentionPeriod = this.retentionPeriod;
        }
        this.securityService.updateProfile(this.companyProfile);
        this.closeDialog();
      });
  }
}
