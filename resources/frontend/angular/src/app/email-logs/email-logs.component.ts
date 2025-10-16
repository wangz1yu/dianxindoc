import { CommonModule } from '@angular/common';
import { AfterViewInit, Component, OnInit, ViewChild } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatNativeDateModule } from '@angular/material/core';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatDialog } from '@angular/material/dialog';
import { MatPaginatorModule, MatPaginator } from '@angular/material/paginator';
import { MatSortModule, MatSort } from '@angular/material/sort';
import { MatTableModule } from '@angular/material/table';
import { CommonDialogService } from '@core/common-dialog/common-dialog.service';
import { ResponseHeader } from '@core/domain-classes/document-header';
import { EmailLogResource } from '@core/domain-classes/email-log-Resource';
import { EmailLogs } from '@core/domain-classes/email-logs';
import { SharedModule } from '@shared/shared.module';
import { ToastrService } from 'ngx-toastr';
import { Observable, Subject, debounceTime, distinctUntilChanged, merge, tap } from 'rxjs';
import { BaseComponent } from '../base.component';
import { EmailLogDataSource } from './email-log-datasource';
import { EmailLogDetailsComponent } from './email-log-details/email-log-details.component';
import { EmailLogService } from './email-log.service';
import { TranslationService } from '@core/services/translation.service';
import { LogsRetentionSettingsComponent } from '../logs-retention-settings/logs-retention-settings.component';

@Component({
  selector: 'app-email-logs',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    SharedModule,
    MatTableModule,
    MatPaginatorModule,
    MatSortModule,
    MatDatepickerModule,
    MatNativeDateModule,
    LogsRetentionSettingsComponent
  ],
  templateUrl: './email-logs.component.html',
  styleUrl: './email-logs.component.scss',
})
export class EmailLogsComponent extends BaseComponent implements OnInit, AfterViewInit {
  dataSource: EmailLogDataSource;
  displayedColumns: string[] = ['action', 'sentAt', 'senderEmail', 'recipientEmail', 'subject', 'status', 'attachment'];
  displayedSearchColumns: string[] = [
    'action-search',
    'sentAt-search',
    'senderEmail-search',
    'recipientEmail-search',
    'subject-search',
    'status-search', 'attachment-search'];
  footerToDisplayed = ['footer'];
  emailLogResource: EmailLogResource;
  loading$: Observable<boolean>;
  @ViewChild(MatPaginator) paginator: MatPaginator;
  @ViewChild(MatSort) sort: MatSort;
  public filterObservable$: Subject<string> = new Subject<string>();
  _senderFilter: string;
  _recipientFilter: string;
  _subjectFilter: string;

  public get SenderFilter(): string {
    return this._senderFilter;
  }

  public set SenderFilter(v: string) {
    this._senderFilter = v;
    const senderNameFilter = `senderEmail##${v}`;
    this.filterObservable$.next(senderNameFilter);
  }

  public get RecipientFilter(): string {
    return this._recipientFilter;
  }

  public set RecipientFilter(v: string) {
    this._recipientFilter = v;
    const recipientFilter = `recipientEmail##${v}`;
    this.filterObservable$.next(recipientFilter);
  }

  public get SubjectFilter(): string {
    return this._subjectFilter;
  }

  public set SubjectFilter(v: string) {
    this._subjectFilter = v;
    const subjectFilter = `subject##${v}`;
    this.filterObservable$.next(subjectFilter);
  }

  constructor(private emailLogService: EmailLogService,
    private dialog: MatDialog,
    private commonDialogService: CommonDialogService,
    private toastrService: ToastrService,
    private translationService: TranslationService
  ) {
    super();
    this.emailLogResource = new EmailLogResource();
    this.emailLogResource.pageSize = 10;
    this.emailLogResource.orderBy = 'sentAt desc'
  }

  ngOnInit(): void {
    this.dataSource = new EmailLogDataSource(this.emailLogService);
    this.dataSource.loadEmailLogs(this.emailLogResource);
    this.getResourceParameter();

    this.sub$.sink = this.filterObservable$
      .pipe(
        debounceTime(1000),
        distinctUntilChanged())
      .subscribe((c) => {
        this.emailLogResource.skip = 0;
        this.paginator.pageIndex = 0;
        const strArray: Array<string> = c.split('##');
        if (strArray[0] === 'senderEmail') {
          this.emailLogResource.senderEmail = strArray[1];
        } else if (strArray[0] === 'recipientEmail') {
          this.emailLogResource.recipientEmail = strArray[1];
        } else if (strArray[0] === 'subject') {
          this.emailLogResource.subject = strArray[1];
        }
        this.dataSource.loadEmailLogs(this.emailLogResource);
      });
  }

  ngAfterViewInit() {
    this.sort.sortChange.subscribe(() => this.paginator.pageIndex = 0);

    this.sub$.sink = merge(this.sort.sortChange, this.paginator.page)
      .pipe(
        tap((c: any) => {
          this.emailLogResource.skip = this.paginator.pageIndex * this.paginator.pageSize;
          this.emailLogResource.pageSize = this.paginator.pageSize;
          this.emailLogResource.orderBy = this.sort.active + ' ' + this.sort.direction;
          this.dataSource.loadEmailLogs(this.emailLogResource);
        })
      )
      .subscribe();
  }

  getResourceParameter() {
    this.sub$.sink = this.dataSource.responseHeaderSubject$
      .subscribe((c: ResponseHeader) => {
        if (c) {
          this.emailLogResource.pageSize = c.pageSize;
          this.emailLogResource.skip = c.skip;
          this.emailLogResource.totalCount = c.totalCount;
        }
      });
  }

  openEmailLogDetails(emailLog: EmailLogs) {
    this.dialog.open(EmailLogDetailsComponent, {
      minWidth: '60vw',
      maxWidth: '80vw',
      maxHeight: '80vh',
      data: Object.assign({}, emailLog),
    });
  }

  deleteLog(emailLog: EmailLogs) {
    this.commonDialogService.deleteConformationDialog(this.translationService.getValue('ARE_YOU_SURE_YOU_WANT_TO_DELETE'))
      .subscribe((isTrue: boolean) => {
        if (isTrue) {
          this.sub$.sink = this.emailLogService.deleteEmailLog(emailLog.id)
            .subscribe(() => {
              this.toastrService.success(this.translationService.getValue('EMAIL_LOG_DELETED_SUCCESSFULLY'));
              this.paginator.pageIndex = 0;
              this.dataSource.loadEmailLogs(this.emailLogResource);
            });
        }
      });
  }


  manageLogsRetention() {
    this.dialog.open(LogsRetentionSettingsComponent, {
      width: '35vw',
      maxHeight: '70vh',
      data: { type: 'email-log-setting' },
    });
  }

}
