import { DataSource } from '@angular/cdk/table';
import { HttpResponse } from '@angular/common/http';
import { EmailLogResource } from '@core/domain-classes/email-log-Resource';
import { EmailLogs } from '@core/domain-classes/email-logs';
import { ResponseHeader } from '@core/domain-classes/response-header';
import { BehaviorSubject, Observable, of } from 'rxjs';
import { catchError, finalize } from 'rxjs/operators';
import { EmailLogService } from './email-log.service';

export class EmailLogDataSource implements DataSource<EmailLogs> {
    private emailLogSubject = new BehaviorSubject<EmailLogs[]>([]);
    private responseHeaderSubject = new BehaviorSubject<ResponseHeader>(null);
    private loadingSubject = new BehaviorSubject<boolean>(false);

    public loading$ = this.loadingSubject.asObservable();
    private _count: number = 0;

    public get count(): number {
        return this._count;
    }

    public responseHeaderSubject$ = this.responseHeaderSubject.asObservable();

    constructor(private emailLogService: EmailLogService) { }

    connect(): Observable<EmailLogs[]> {
        return this.emailLogSubject.asObservable();
    }

    disconnect(): void {
        this.emailLogSubject.complete();
        this.loadingSubject.complete();
    }

    loadEmailLogs(emailLogResource: EmailLogResource) {
        this.loadingSubject.next(true);
        this.emailLogService
            .getEmailLogs(emailLogResource)
            .pipe(
                catchError(() => of([])),
                finalize(() => this.loadingSubject.next(false))
            )
            .subscribe((resp: HttpResponse<EmailLogs[]>) => {
                if (resp && resp.headers) {
                    const paginationParam = new ResponseHeader();
                    paginationParam.pageSize = parseInt(resp.headers.get('pageSize'));
                    paginationParam.totalCount = parseInt(resp.headers.get('totalCount'));
                    paginationParam.skip = parseInt(resp.headers.get('skip'));
                    this.responseHeaderSubject.next(paginationParam);
                    const emailLogsTrails = [...resp.body];
                    this._count = emailLogsTrails.length;
                    this.emailLogSubject.next(emailLogsTrails);
                }
            });
    }
}
