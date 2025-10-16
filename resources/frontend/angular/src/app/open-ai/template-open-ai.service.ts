import { Injectable } from '@angular/core';
import { HttpClient, HttpParams, HttpResponse } from '@angular/common/http';
import { AIPromptTemplate } from '@core/domain-classes/ai-prompt-template';
import { catchError, Observable } from 'rxjs';
import { CommonError } from '@core/error-handler/common-error';
import { CommonHttpErrorService } from '@core/error-handler/common-http-error.service';
import { OpenAiDocumentResource } from '@core/domain-classes/open-ai-document-resource';
import { OpenAiDocuments } from '@core/domain-classes/open-ai-documents';

@Injectable({ providedIn: 'root' })
export class AIPromptTemplateService {
    constructor(private httpClient: HttpClient,
        private commonHttpErrorService: CommonHttpErrorService) { }

    getAIPromptTemplates(): Observable<AIPromptTemplate[] | CommonError> {
        const url = 'aIPromptTemplate';
        return this.httpClient.get<AIPromptTemplate[]>(url)
            .pipe(catchError(this.commonHttpErrorService.handleError));
    }

    getAIPromptTemplate(id: string): Observable<AIPromptTemplate | CommonError> {
        const url = `aIPromptTemplate/${id}`;
        return this.httpClient.get<AIPromptTemplate>(url)
            .pipe(catchError(this.commonHttpErrorService.handleError));
    }

    addAIPromptTemplate(template: AIPromptTemplate): Observable<AIPromptTemplate | CommonError> {
        const url = `aIPromptTemplate`;
        return this.httpClient.post<AIPromptTemplate>(url, template)
            .pipe(catchError(this.commonHttpErrorService.handleError));
    }

    updateAIPromptTemplate(template: AIPromptTemplate): Observable<AIPromptTemplate | CommonError> {
        const url = `aIPromptTemplate/${template.id}`;
        return this.httpClient.put<AIPromptTemplate>(url, template)
            .pipe(catchError(this.commonHttpErrorService.handleError));
    }

    deleteAIPromptTemplate(id: string): Observable<AIPromptTemplate | CommonError> {
        const url = `aIPromptTemplate/${id}`;
        return this.httpClient.delete<AIPromptTemplate>(url)
            .pipe(catchError(this.commonHttpErrorService.handleError));
    }
}
