import {
  AfterViewInit,
  Component,
  ElementRef,
  OnInit,
  ViewChild,
} from '@angular/core';
import { MatSort } from '@angular/material/sort';
import { ResponseHeader } from '@core/domain-classes/document-header';
import { DocumentResource } from '@core/domain-classes/document-resource';
import { UserNotification } from '@core/domain-classes/notification';
import { OverlayPanel } from '@shared/overlay-panel/overlay-panel.service';
import { fromEvent, merge } from 'rxjs';
import { debounceTime, distinctUntilChanged, tap } from 'rxjs/operators';
import { BaseComponent } from 'src/app/base.component';
import { NotificationDataSource } from '../notification-datassource';
import { NotificationService } from '../notification.service';
import { BasePreviewComponent } from '@shared/base-preview/base-preview.component';
import { DocumentView } from '@core/domain-classes/document-view';
import { DocumentService } from 'src/app/document/document.service';
import { DocumentInfo } from '@core/domain-classes/document-info';
import { MatPaginator } from '@angular/material/paginator';
import { ToastrService } from 'ngx-toastr';
import { NotificationType } from '@core/domain-classes/notification-enum';
import { Router } from '@angular/router';
import { CommonService } from '@core/services/common.service';
import { CommonError } from '@core/error-handler/common-error';
import { TranslateService } from '@ngx-translate/core';
import { TranslationService } from '@core/services/translation.service';

@Component({
  selector: 'app-notification-list',
  templateUrl: './notification-list.component.html',
  styleUrls: ['./notification-list.component.css'],
})
export class NotificationListComponent
  extends BaseComponent
  implements OnInit, AfterViewInit {
  dataSource: NotificationDataSource;
  notifications: UserNotification[] = [];
  displayedColumns: string[] = ['action', 'createdDate', 'message'];
  notificationResource: DocumentResource;
  @ViewChild(MatPaginator) paginator: MatPaginator;
  @ViewChild(MatSort) sort: MatSort;
  @ViewChild('input') input: ElementRef;

  constructor(
    private notificationService: NotificationService,
    public overlay: OverlayPanel,
    private documentService: DocumentService,
    private toastrService: ToastrService,
    private router: Router,
    private commonService: CommonService,
    private translationService: TranslationService
  ) {
    super();
    this.notificationResource = new DocumentResource();
    this.notificationResource.pageSize = 10;
    this.notificationResource.orderBy = 'createdDate desc';
  }

  ngOnInit(): void {
    this.dataSource = new NotificationDataSource(this.notificationService);
    this.dataSource.loadNotifications(this.notificationResource);
    this.getResourceParameter();
  }

  ngAfterViewInit() {
    this.sort.sortChange.subscribe(() => (this.paginator.pageIndex = 0));

    this.sub$.sink = merge(this.sort.sortChange, this.paginator.page)
      .pipe(
        tap(() => {
          this.notificationResource.skip =
            this.paginator.pageIndex * this.paginator.pageSize;
          this.notificationResource.pageSize = this.paginator.pageSize;
          this.notificationResource.orderBy =
            this.sort.active + ' ' + this.sort.direction;
          this.dataSource.loadNotifications(this.notificationResource);
        })
      )
      .subscribe();

    this.sub$.sink = fromEvent(this.input.nativeElement, 'keyup')
      .pipe(
        debounceTime(1000),
        distinctUntilChanged(),
        tap(() => {
          this.paginator.pageIndex = 0;
          this.notificationResource.skip = 0;
          this.notificationResource.name = this.input.nativeElement.value;
          this.dataSource.loadNotifications(this.notificationResource);
        })
      )
      .subscribe();

    this.notificationService.markAllAsRead().subscribe(() => { });
  }
  getResourceParameter() {
    this.sub$.sink = this.dataSource.responseHeaderSubject$.subscribe(
      (c: ResponseHeader) => {
        if (c) {
          this.notificationResource.pageSize = c.pageSize;
          this.notificationResource.skip = c.skip;
          this.notificationResource.totalCount = c.totalCount;
        }
      }
    );
  }

  viewNotification(notification: UserNotification) {
    if (!notification.isRead) {
      this.sub$.sink = this.notificationService
        .markAsRead(notification.id)
        .subscribe();
    }

    notification.isRead = true;

    if (notification.notificationType == NotificationType.DOCUMENT_SHARE) {
      this.commonService.checkDocumentPermission(notification.documentId).subscribe(
        (hasPermission: boolean | CommonError) => {
          if (!hasPermission) {
            this.toastrService.error(
              this.translationService.getValue('DOCUMENT_PERMISSION_ERROR')
            );
            return;
          }
          this.sub$.sink = this.documentService
            .getDocument(notification.documentId)
            .subscribe((data: DocumentInfo) => {
              if (!data || Object.keys(data).length == 0) {
                this.toastrService.error(
                  this.translationService.getValue('DOCUMENT_PERMISSION_ERROR')
                );
                return;
              }
              const urls = data.url.split('.');
              const extension = urls[1];
              const documentView: DocumentView = {
                documentId: data.id,
                name: data.name,
                extension: extension,
                isVersion: false,
                isFromPublicPreview: false,
                isPreviewDownloadEnabled: false,
                isFromFileRequest: false,
              };
              this.overlay.open(BasePreviewComponent, {
                position: 'center',
                origin: 'global',
                panelClass: ['file-preview-overlay-container', 'white-background'],
                data: documentView,
              });
            });
        });
    } else if (notification.notificationType == NotificationType.FILE_REQUEST) {
      this.router.navigate(['/file-request']);
    } else if (notification.notificationType == NotificationType.WORKFLOW) {
      this.router.navigate(['/current-workflow']);
    } else if (notification.notificationType == NotificationType.REMINDER) {
      this.router.navigate(['/reminders']);
    }
  }
}
