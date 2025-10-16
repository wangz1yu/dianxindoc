import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { CompanyProfile, GoogleGeminiApi, OpenAiApi, S3Config } from '@core/domain-classes/company-profile';
import { CommonError } from '@core/error-handler/common-error';
import { CommonHttpErrorService } from '@core/error-handler/common-http-error.service';
import { Observable } from 'rxjs';
import { catchError } from 'rxjs/operators';

@Injectable({
  providedIn: 'root',
})
export class CompanyProfileService {
  constructor(
    private http: HttpClient,
    private commonHttpErrorService: CommonHttpErrorService
  ) { }

  getCompanyProfile(): Observable<CompanyProfile | CommonError> {
    const url = `companyprofile`;
    return this.http
      .get<CompanyProfile>(url)
      .pipe(catchError(this.commonHttpErrorService.handleError));
  }

  updateCompanyProfile(
    companyProfile
  ): Observable<CompanyProfile | CommonError> {
    const url = `companyProfile`;
    return this.http
      .post<CompanyProfile>(url, companyProfile)
      .pipe(catchError(this.commonHttpErrorService.handleError));
  }

  updateLocalStorage(
    companyProfile: S3Config
  ): Observable<CompanyProfile | CommonError> {
    const url = `storage`;
    return this.http
      .post<CompanyProfile>(url, companyProfile)
      .pipe(catchError(this.commonHttpErrorService.handleError));
  }

  getS3Config(): Observable<S3Config | CommonError> {
    const url = `storage`;
    return this.http
      .get<S3Config>(url)
      .pipe(catchError(this.commonHttpErrorService.handleError));
  }

  saveOpenAiKey(apiKey: OpenAiApi): Observable<OpenAiApi | CommonError> {
    const url = `open-ai/key`;
    return this.http
      .post<OpenAiApi>(url, apiKey)
      .pipe(catchError(this.commonHttpErrorService.handleError));
  }

  getOpenAiApiKey(): Observable<OpenAiApi | CommonError> {
    const url = `open-ai/key`;
    return this.http
      .get<OpenAiApi>(url)
      .pipe(catchError(this.commonHttpErrorService.handleError));
  }

  getGoogleGeminiApiKey(): Observable<GoogleGeminiApi> {
    const url = `google-gemini/key`;
    return this.http
      .get<GoogleGeminiApi>(url);
  }

  saveGoogleGeminiApiKey(apiKey: GoogleGeminiApi): Observable<GoogleGeminiApi | CommonError> {
    const url = `google-gemini/key`;
    return this.http
      .post<GoogleGeminiApi>(url, apiKey)
      .pipe(catchError(this.commonHttpErrorService.handleError));
  }

  updatePdfSignatureSetting(allowPdfSignature: boolean): Observable<any | CommonError> {
    const url = `allow-pdf-signature`;
    return this.http
      .post(url, {
        allowPdfSignature: allowPdfSignature,
      })
      .pipe(catchError(this.commonHttpErrorService.handleError));
  }
}
