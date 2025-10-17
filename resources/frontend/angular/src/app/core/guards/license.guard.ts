import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, Router } from '@angular/router';
import { LicenseService } from '../services/license.service';

@Injectable({
  providedIn: 'root'
})
export class LicenseGuard implements CanActivate {
  constructor(private licenseService: LicenseService, private router: Router) {}

  canActivate(next: ActivatedRouteSnapshot, state: RouterStateSnapshot): boolean {
    // 检查 URL 是否包含许可证验证
    const url = state.url;
    if (url.includes('activate-license') || url.includes('remove-license-key')) {
      // 通过 service 处理许可证状态
      this.licenseService.handleLicenseRedirect(url);
      return false;
    }
    return true;
  }
}