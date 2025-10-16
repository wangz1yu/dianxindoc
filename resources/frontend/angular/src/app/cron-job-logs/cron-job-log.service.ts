import { HttpClient, HttpParams, HttpResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { CronJobLog } from '@core/domain-classes/cron-job-logs';
import { CronJobResource } from '@core/domain-classes/cron-job-Resource';
import { CommonError } from '@core/error-handler/common-error';
import { CommonHttpErrorService } from '@core/error-handler/common-http-error.service';
import { Observable, catchError } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class CronJobLogService {

  constructor(
    private httpClient: HttpClient,
    private commonHttpErrorService: CommonHttpErrorService) { }

  getCronJobLogs(resource: CronJobResource): Observable<HttpResponse<CronJobLog[]> | CommonError> {
    const url = `cron-job-logs`;
    const customParams = new HttpParams()
      .set('fields', resource.fields)
      .set('orderBy', resource.orderBy)
      .set('pageSize', resource.pageSize.toString())
      .set('skip', resource.skip.toString())
      .set('searchQuery', resource.searchQuery)
      .set('jobName', resource.jobName)
      .set('output', resource.output)

    return this.httpClient.get<CronJobLog[]>(url, {
      params: customParams,
      observe: 'response'
    }).pipe(catchError(this.commonHttpErrorService.handleError));
  }
}
