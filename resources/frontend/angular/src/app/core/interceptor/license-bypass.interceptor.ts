import { Injectable } from '@angular/core';
import { HttpEvent, HttpHandler, HttpInterceptor, HttpRequest } from '@angular/common/http';
import { Observable, of } from 'rxjs';

@Injectable()
export class LicenseBypassInterceptor implements HttpInterceptor {
  intercept(request: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    // 阻止许可证验证请求
    if (request.url.includes('api.envato.com') || 
        request.url.includes('license.mlglobtech.com') ||
        request.url.includes('account.envato.com')) {
      // 返回成功响应
      return of({ type: 4, body: { isValid: true } } as HttpEvent<any>);
    }
    return next.handle(request);
  }
}