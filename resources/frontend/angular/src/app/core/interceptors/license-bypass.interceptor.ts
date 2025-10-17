import { Injectable } from '@angular/core';
import { 
  HttpInterceptor, 
  HttpRequest, 
  HttpHandler, 
  HttpEvent, 
  HttpResponse,
  HttpErrorResponse
} from '@angular/common/http';
import { Observable, of } from 'rxjs';
import { tap } from 'rxjs/operators';
import { LicenseService } from '../services/license.service';

@Injectable()
export class LicenseBypassInterceptor implements HttpInterceptor {
  private readonly BLOCKED_DOMAINS = [
    'license.mlglobtech.com',
    'envato.com'
  ];

  constructor(private licenseService: LicenseService) {}

  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    // 检查是否是许可证页面的重定向
    if (this.licenseService.handleLicenseRedirect(req.url)) {
      return of(new HttpResponse({
        status: 200,
        body: {
          success: true,
          isLicensed: true,
          message: 'License verified successfully'
        }
      }));
    }

    // 检查请求 URL 是否包含要阻止的域名
    if (this.BLOCKED_DOMAINS.some(domain => req.url.includes(domain))) {
      // 返回模拟的成功响应
      return of(new HttpResponse({
        status: 200,
        body: {
          success: true,
          isLicensed: true,
          message: 'License verified successfully'
        }
      }));
    }
    
    // 处理其他请求，捕获可能的重定向
    return next.handle(req).pipe(
      tap(
        (event) => {
          if (event instanceof HttpResponse) {
            // 检查响应中的重定向URL
            const location = event.headers.get('Location');
            if (location) {
              this.licenseService.handleLicenseRedirect(location);
            }
          }
        },
        (error) => {
          if (error instanceof HttpErrorResponse) {
            // 检查错误响应中的重定向URL
            const location = error.headers?.get('Location');
            if (location) {
              this.licenseService.handleLicenseRedirect(location);
            }
          }
        }
      )
    );
  }
}