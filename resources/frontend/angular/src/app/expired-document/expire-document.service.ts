import { HttpClient, HttpParams, HttpResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { DocumentInfo } from '@core/domain-classes/document-info';
import { DocumentResource } from '@core/domain-classes/document-resource';
import { CommonError } from '@core/error-handler/common-error';
import { CommonHttpErrorService } from '@core/error-handler/common-http-error.service';
import { Observable, catchError } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class ExpireDocumentService {
  constructor(
    private httpClient: HttpClient,
    private commonHttpErrorService: CommonHttpErrorService
  ) { }

  getExpireDocuments(
    resource: DocumentResource
  ): Observable<HttpResponse<DocumentInfo[]> | CommonError> {
    const url = `expired-documents`;
    const customParams = new HttpParams()
      .set('fields', resource.fields)
      .set('orderBy', resource.orderBy)
      .set(
        'deletedDateString',
        resource.deletedDate ? resource.deletedDate.toString() : ''
      )
      .set('pageSize', resource.pageSize.toString())
      .set('skip', resource.skip.toString())
      .set('searchQuery', resource.searchQuery)
      .set('categoryId', resource.categoryId)
      .set('name', resource.name)
      .set('metaTags', resource.metaTags)
      .set('id', resource.id.toString())
      .set('location', resource.location)
      .set('statusId', resource.statusId)
    return this.httpClient
      .get<DocumentInfo[]>(url, {
        params: customParams,
        observe: 'response',
      })
      .pipe(catchError(this.commonHttpErrorService.handleError));
  }

  activeDocument(id: string): Observable<DocumentInfo | CommonError> {
    const url = `expired-documents/${id}/active`;
    return this.httpClient
      .put<DocumentInfo>(url, {})
      .pipe(catchError(this.commonHttpErrorService.handleError));
  }

  archiveDocument(id: string): Observable<DocumentInfo | CommonError> {
    const url = `expired-documents/${id}/archive`;
    return this.httpClient
      .delete<DocumentInfo>(url, {})
      .pipe(catchError(this.commonHttpErrorService.handleError));
  }

}
