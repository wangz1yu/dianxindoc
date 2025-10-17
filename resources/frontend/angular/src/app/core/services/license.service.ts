import { Injectable } from '@angular/core';
import { Router } from '@angular/router';

@Injectable({
  providedIn: 'root'
})
export class LicenseService {
  constructor(private router: Router) {
    // 初始化时就设置许可状态
    this.setLicenseState();
  }

  private setLicenseState() {
    localStorage.setItem('isLicensed', 'true');
    localStorage.setItem('licenseActivated', 'true');
    localStorage.setItem('licenseKey', 'BYPASS-LICENSE-CHECK');
  }

  public handleLicenseRedirect(url: string): boolean {
    // 检查是否是许可证验证的 URL
    if (url.includes('license.mlglobtech.com') || url.includes('envato.com')) {
      // 设置许可证状态
      this.setLicenseState();
      // 重定向到首页
      this.router.navigate(['/']);
      return true;
    }
    return false;
  }
}