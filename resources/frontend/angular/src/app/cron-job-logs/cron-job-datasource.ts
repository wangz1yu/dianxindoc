import { DataSource } from '@angular/cdk/table';
import { HttpResponse } from '@angular/common/http';
import { CronJobLog } from '@core/domain-classes/cron-job-logs';
import { ResponseHeader } from '@core/domain-classes/response-header';
import { BehaviorSubject, Observable, of } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';
import { CronJobLogService } from './cron-job-log.service';
import { CronJobResource } from '@core/domain-classes/cron-job-Resource';

export class CronJobDataSource implements DataSource<CronJobLog> {
    private cronJobSubject = new BehaviorSubject<CronJobLog[]>([]);
    private responseHeaderSubject = new BehaviorSubject<ResponseHeader>(null);
    private loadingSubject = new BehaviorSubject<boolean>(false);

    public loading$ = this.loadingSubject.asObservable();
    private _count: number = 0;

    public get count(): number {
        return this._count;
    }

    public responseHeaderSubject$ = this.responseHeaderSubject.asObservable();

    constructor(private cronJobLogService: CronJobLogService) { }

    connect(): Observable<CronJobLog[]> {
        return this.cronJobSubject.asObservable();
    }

    disconnect(): void {
        this.cronJobSubject.complete();
        this.loadingSubject.complete();
    }

    loadCronJobLogs(cronJobResource: CronJobResource) {
        this.loadingSubject.next(true);
        this.cronJobLogService
            .getCronJobLogs(cronJobResource)
            .pipe(
                catchError(() => of([])),
                finalize(() => this.loadingSubject.next(false))
            )
            .subscribe((resp: HttpResponse<CronJobLog[]>) => {
                if (resp && resp.headers) {
                    const paginationParam = new ResponseHeader();
                    paginationParam.pageSize = parseInt(resp.headers.get('pageSize'));
                    paginationParam.totalCount = parseInt(resp.headers.get('totalCount'));
                    paginationParam.skip = parseInt(resp.headers.get('skip'));
                    this.responseHeaderSubject.next(paginationParam);
                    const cronJobLogsTrails = [...resp.body];
                    this._count = cronJobLogsTrails.length;
                    this.cronJobSubject.next(cronJobLogsTrails);
                }
            });
    }
}
