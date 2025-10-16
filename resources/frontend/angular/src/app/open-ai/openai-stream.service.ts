import { HttpClient, HttpParams, HttpResponse } from "@angular/common/http";
import { Injectable } from "@angular/core";
import { OpenAiDocumentResource } from "@core/domain-classes/open-ai-document-resource";
import { OpenAiDocuments } from "@core/domain-classes/open-ai-documents";
import { CommonError } from "@core/error-handler/common-error";
import { CommonHttpErrorService } from "@core/error-handler/common-http-error.service";
import { environment } from "@environments/environment";
import { catchError, Observable } from "rxjs";

@Injectable({ providedIn: 'root' })
export class OpenAIStreamService {
    constructor(private http: HttpClient,
        private commonHttpErrorService: CommonHttpErrorService
    ) { }

    getOpenAiDocuments(resource: OpenAiDocumentResource): Observable<HttpResponse<OpenAiDocuments[]> | CommonError> {
        const url = `ai/documents`;
        const customParams = new HttpParams()
            .set('fields', resource.fields)
            .set('orderBy', resource.orderBy)
            .set('pageSize', resource.pageSize.toString())
            .set('skip', resource.skip.toString())
            .set('searchQuery', resource.searchQuery)
            .set('id', resource.id.toString())
            .set('prompt', resource.prompt.toString())

        return this.http.get<OpenAiDocuments[]>(url, {
            params: customParams,
            observe: 'response'
        }).pipe(catchError(this.commonHttpErrorService.handleError));
    }

    getOpenAiDocumentResponse(id: string): Observable<OpenAiDocuments | CommonError> {
        const url = `ai/documents/${id}`;
        return this.http.get<OpenAiDocuments>(url)
            .pipe(catchError(this.commonHttpErrorService.handleError));
    }

    deleteOpenAiDocument(id: string): Observable<OpenAiDocuments | CommonError> {
        const url = `ai/documents/${id}`;
        return this.http.delete<OpenAiDocuments>(url)
            .pipe(catchError(this.commonHttpErrorService.handleError));
    }

    summarize(model: string, documentId: string): Observable<{ summary: string }> {
        return this.http.post<{ summary: string }>(`ai/summarize-document`, { model, documentId });
    }
}
