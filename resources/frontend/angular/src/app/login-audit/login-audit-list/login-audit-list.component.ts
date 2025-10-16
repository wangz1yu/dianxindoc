import {
  AfterViewInit,
  Component,
  ElementRef,
  OnInit,
  ViewChild,
} from '@angular/core';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';
import { LoginAudit } from '@core/domain-classes/login-audit';
import { LoginAuditResource } from '@core/domain-classes/login-audit-resource';
import { ResponseHeader } from '@core/domain-classes/response-header';
import { fromEvent, merge, Observable } from 'rxjs';
import { debounceTime, distinctUntilChanged, tap } from 'rxjs/operators';
import { BaseComponent } from 'src/app/base.component';
import { LoginAuditDataSource } from '../login-audit-datasource';
import { LoginAuditService } from '../login-audit.service';
import { MatDialog } from '@angular/material/dialog';
import { LogsRetentionSettingsComponent } from 'src/app/logs-retention-settings/logs-retention-settings.component';

@Component({
  selector: 'app-login-audit-list',
  templateUrl: './login-audit-list.component.html',
  styleUrls: ['./login-audit-list.component.scss'],
})
export class LoginAuditListComponent
  extends BaseComponent
  implements OnInit, AfterViewInit {
  dataSource: LoginAuditDataSource;
  loginAudits: LoginAudit[] = [];
  displayedColumns: string[] = [
    'loginTime',
    'userName',
    'remoteIP',
    'status',
    'latitude',
    'longitude',
  ];
  footerToDisplayed = ['footer'];
  loginAuditResource: LoginAuditResource;
  @ViewChild(MatPaginator) paginator: MatPaginator;
  @ViewChild(MatSort) sort: MatSort;
  @ViewChild('input') input: ElementRef;

  constructor(private loginAuditService: LoginAuditService,
    private dialog: MatDialog
  ) {
    super();
    this.loginAuditResource = new LoginAuditResource();
    this.loginAuditResource.pageSize = 10;
    this.loginAuditResource.orderBy = 'loginTime desc';
  }

  ngOnInit(): void {
    this.dataSource = new LoginAuditDataSource(this.loginAuditService);
    this.dataSource.loadLoginAudits(this.loginAuditResource);
    this.getResourceParameter();
  }

  ngAfterViewInit() {
    this.sort.sortChange.subscribe(() => (this.paginator.pageIndex = 0));

    this.sub$.sink = merge(this.sort.sortChange, this.paginator.page)
      .pipe(
        tap(() => {
          this.loginAuditResource.skip =
            this.paginator.pageIndex * this.paginator.pageSize;
          this.loginAuditResource.pageSize = this.paginator.pageSize;
          this.loginAuditResource.orderBy =
            this.sort.active + ' ' + this.sort.direction;
          this.dataSource.loadLoginAudits(this.loginAuditResource);
        })
      )
      .subscribe();

    this.sub$.sink = fromEvent(this.input.nativeElement, 'keyup')
      .pipe(
        debounceTime(1000),
        distinctUntilChanged(),
        tap(() => {
          this.paginator.pageIndex = 0;
          this.loginAuditResource.skip = 0;
          this.loginAuditResource.userName = this.input.nativeElement.value;
          this.dataSource.loadLoginAudits(this.loginAuditResource);
        })
      )
      .subscribe();
  }

  getResourceParameter() {
    this.sub$.sink = this.dataSource.responseHeaderSubject$.subscribe(
      (c: ResponseHeader) => {
        if (c) {
          this.loginAuditResource.pageSize = c.pageSize;
          this.loginAuditResource.skip = c.skip;
          this.loginAuditResource.totalCount = c.totalCount;
        }
      }
    );
  }

  manageLogsRetention() {
    this.dialog.open(LogsRetentionSettingsComponent, {
      width: '35vw',
      maxHeight: '70vh',
      data: { type: 'login-audit-log-setting' },
    });
  }
}
