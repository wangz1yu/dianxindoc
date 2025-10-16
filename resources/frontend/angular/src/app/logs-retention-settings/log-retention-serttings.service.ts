import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { CommonHttpErrorService } from '@core/error-handler/common-http-error.service';
import { catchError } from 'rxjs/operators';

@Injectable({
    providedIn: 'root'
})
export class LogRetentionService {

    constructor(
        private httpClient: HttpClient,
        private commonHttpErrorService: CommonHttpErrorService) { }

    saveLogRetentionSettings(retentionPeriod: number, type: string) {
        const url = `retention/${type}`;
        const body = { retentionPeriod };
        return this.httpClient.post(url, body).pipe(
            catchError(this.commonHttpErrorService.handleError)
        );
    }
}
