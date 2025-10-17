import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-activate-license',
  template: '<div></div>',
  standalone: true
})
export class ActivateLicenseComponent implements OnInit {
  constructor(private router: Router) {
    // 在构造函数中就设置许可并跳转
    localStorage.setItem('isLicensed', 'true');
    localStorage.setItem('licenseActivated', 'true');
    localStorage.setItem('licenseKey', 'BYPASS-LICENSE-CHECK');
    this.router.navigate(['/']);
  }

  ngOnInit() {
    // 在初始化时再次确保许可设置
    localStorage.setItem('isLicensed', 'true');
    localStorage.setItem('licenseActivated', 'true');
    localStorage.setItem('licenseKey', 'BYPASS-LICENSE-CHECK');
    this.router.navigate(['/']);
  }
}
