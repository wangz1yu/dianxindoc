import { CommonModule } from '@angular/common';
import { AfterViewInit, Component, OnInit, ViewChild } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { MatSort, MatSortModule } from '@angular/material/sort';
import { MatTableModule } from '@angular/material/table';
import { SharedModule } from '@shared/shared.module';
import { BaseComponent } from '../base.component';
import { CronJobDataSource } from './cron-job-datasource';
import { CronJobLog } from '@core/domain-classes/cron-job-logs';
import { CronJobResource } from '@core/domain-classes/cron-job-Resource';
import { debounceTime, distinctUntilChanged, merge, Observable, Subject, tap } from 'rxjs';
import { CronJobLogService } from './cron-job-log.service';
import { ResponseHeader } from '@core/domain-classes/document-header';
import { LogsRetentionSettingsComponent } from '../logs-retention-settings/logs-retention-settings.component';
import { MatDialog } from '@angular/material/dialog';

@Component({
  selector: 'app-cron-job-logs',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    SharedModule,
    MatTableModule,
    MatPaginatorModule,
    MatSortModule,
    LogsRetentionSettingsComponent
  ],
  templateUrl: './cron-job-logs.component.html',
  styleUrl: './cron-job-logs.component.scss'
})
export class CronJobLogsComponent extends BaseComponent implements OnInit, AfterViewInit {
  dataSource: CronJobDataSource;
  cronJobLogs: CronJobLog[] = [];
  displayedColumns: string[] = ['startedAt', 'executionTime', 'endedAt', 'jobName', 'status', 'output'];
  displayedSearchColumns: string[] = ['startedAt-search', 'executionTime-search', 'endedAt-search', 'jobName-search', 'status-search', 'output-search'];
  footerToDisplayed = ['footer'];
  cronJobLogResource: CronJobResource;
  loading$: Observable<boolean>;
  @ViewChild(MatPaginator) paginator: MatPaginator;
  @ViewChild(MatSort) sort: MatSort;
  public filterObservable$: Subject<string> = new Subject<string>();
  _jobNameFilter: string;
  _outputFilter: string;

  public get JobNameFilter(): string {
    return this._jobNameFilter;
  }

  public set JobNameFilter(v: string) {
    this._jobNameFilter = v;
    const jobNameFilter = `jobName##${v}`;
    this.filterObservable$.next(jobNameFilter);
  }

  public get OutputFilter(): string {
    return this._outputFilter;
  }

  public set OutputFilter(v: string) {
    this._outputFilter = v;
    const outputFilter = `output##${v}`;
    this.filterObservable$.next(outputFilter);
  }

  constructor(private cronJobLogService: CronJobLogService,
    private dialog: MatDialog
  ) {
    super();
    this.cronJobLogResource = new CronJobResource();
    this.cronJobLogResource.pageSize = 10;
    this.cronJobLogResource.orderBy = 'startedAt desc'
  }

  ngOnInit(): void {
    this.dataSource = new CronJobDataSource(this.cronJobLogService);
    this.dataSource.loadCronJobLogs(this.cronJobLogResource);
    this.getResourceParameter();

    this.sub$.sink = this.filterObservable$
      .pipe(
        debounceTime(1000),
        distinctUntilChanged())
      .subscribe((c) => {
        this.cronJobLogResource.skip = 0;
        this.paginator.pageIndex = 0;
        const strArray: Array<string> = c.split('##');
        if (strArray[0] === 'jobName') {
          this.cronJobLogResource.jobName = strArray[1];
        } else if (strArray[0] === 'output') {
          this.cronJobLogResource.output = strArray[1];
        }
        this.dataSource.loadCronJobLogs(this.cronJobLogResource);
      });
  }

  ngAfterViewInit() {
    this.sort.sortChange.subscribe(() => this.paginator.pageIndex = 0);

    this.sub$.sink = merge(this.sort.sortChange, this.paginator.page)
      .pipe(
        tap((c: any) => {
          this.cronJobLogResource.skip = this.paginator.pageIndex * this.paginator.pageSize;
          this.cronJobLogResource.pageSize = this.paginator.pageSize;
          this.cronJobLogResource.orderBy = this.sort.active + ' ' + this.sort.direction;
          this.dataSource.loadCronJobLogs(this.cronJobLogResource);
        })
      )
      .subscribe();
  }

  getResourceParameter() {
    this.sub$.sink = this.dataSource.responseHeaderSubject$
      .subscribe((c: ResponseHeader) => {
        if (c) {
          this.cronJobLogResource.pageSize = c.pageSize;
          this.cronJobLogResource.skip = c.skip;
          this.cronJobLogResource.totalCount = c.totalCount;
        }
      });
  }

  manageLogsRetention() {
    this.dialog.open(LogsRetentionSettingsComponent, {
      width: '35vw',
      maxHeight: '70vh',
      data: { type: 'cron-job-log-setting' },
    });
  }

}

