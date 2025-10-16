import { CommonModule } from '@angular/common';
import { AfterViewInit, Component, ElementRef, inject, OnInit, ViewChild } from '@angular/core';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatDialog, MatDialogModule } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatMenuModule } from '@angular/material/menu';
import { MatPaginator, MatPaginatorModule } from '@angular/material/paginator';
import { MatSlideToggleModule } from '@angular/material/slide-toggle';
import { MatSort, MatSortModule } from '@angular/material/sort';
import { MatTableModule } from '@angular/material/table';
import { NgSelectModule } from '@ng-select/ng-select';
import { SharedModule } from '@shared/shared.module';
import { OwlDateTimeModule, OwlNativeDateTimeModule } from 'ng-pick-datetime-ex';
import { BaseComponent } from '../base.component';
import { DocumentInfo } from '@core/domain-classes/document-info';
import { ExpiredDocumentDataSource } from './expired-document-dataSource';
import { DocumentResource } from '@core/domain-classes/document-resource';
import { SelectionModel } from '@angular/cdk/collections';
import { Direction } from '@angular/cdk/bidi';
import { DocumentStatusStore } from '../document-status/store/document-status.store';
import { CategoryStore } from '../category/store/category-store';
import { ExpireDocumentService } from './expire-document.service';
import { DocumentService } from '../document/document.service';
import { OverlayPanel } from '@shared/overlay-panel/overlay-panel.service';
import { ClonerService } from '@core/services/clone.service';
import { CommonDialogService } from '@core/common-dialog/common-dialog.service';
import { TranslationService } from '@core/services/translation.service';
import { CommonService } from '@core/services/common.service';
import { ToastrService } from 'ngx-toastr';
import { debounceTime, distinctUntilChanged, fromEvent, merge, tap } from 'rxjs';
import { ResponseHeader } from '@core/domain-classes/document-header';
import { DocumentAuditTrail } from '@core/domain-classes/document-audit-trail';
import { DocumentView } from '@core/domain-classes/document-view';
import { BasePreviewComponent } from '@shared/base-preview/base-preview.component';
import { DocumentOperation } from '@core/domain-classes/document-operation';

@Component({
  selector: 'app-expired-document',
  standalone: true,
  imports: [
    CommonModule,
    SharedModule,
    FormsModule,
    ReactiveFormsModule,
    MatTableModule,
    MatDialogModule,
    MatCheckboxModule,
    MatPaginatorModule,
    MatSortModule,
    MatMenuModule,
    MatInputModule,
    MatSlideToggleModule,
    MatIconModule,
    MatButtonModule,
    OwlDateTimeModule,
    OwlNativeDateTimeModule,
    NgSelectModule
  ],
  templateUrl: './expired-document.component.html',
  styleUrl: './expired-document.component.scss'
})
export class ExpiredDocumentComponent extends BaseComponent implements OnInit, AfterViewInit {
  dataSource: ExpiredDocumentDataSource;
  documents: DocumentInfo[] = [];
  displayedColumns: string[] = [
    'action',
    'name',
    'categoryName',
    'location',
    'statusName',
    'expiredDate',
  ];
  footerToDisplayed = ['footer'];
  documentResource: DocumentResource;
  @ViewChild(MatPaginator) paginator: MatPaginator;
  @ViewChild(MatSort) sort: MatSort;
  @ViewChild('input') input: ElementRef;
  @ViewChild('metatag') metatag: ElementRef;
  selection = new SelectionModel<DocumentInfo>(true, []);
  max = new Date();
  direction: Direction;
  documentStatusStore = inject(DocumentStatusStore);
  categoryStore = inject(CategoryStore);

  constructor(
    private expireDocumentService: ExpireDocumentService,
    private documentService: DocumentService,
    public overlay: OverlayPanel,
    public clonerService: ClonerService,
    private commonDialogService: CommonDialogService,
    private translationService: TranslationService,
    private commonService: CommonService,
    private toastrService: ToastrService,

    private dialog: MatDialog
  ) {
    super();
    this.documentResource = new DocumentResource();
    this.documentResource.pageSize = 10;
    this.documentResource.orderBy = 'expiredDate desc';
  }

  ngOnInit(): void {
    this.dataSource = new ExpiredDocumentDataSource(
      this.expireDocumentService
    );
    this.dataSource.loadDocuments(this.documentResource);
    this.getResourceParameter();
    this.getLangDir();
  }

  getLangDir() {
    this.sub$.sink = this.translationService.lanDir$.subscribe(
      (c: Direction) => (this.direction = c)
    );
  }

  ngAfterViewInit() {
    this.sort.sortChange.subscribe(() => (this.paginator.pageIndex = 0));

    this.sub$.sink = merge(this.sort.sortChange, this.paginator.page)
      .pipe(
        tap(() => {
          this.documentResource.skip =
            this.paginator.pageIndex * this.paginator.pageSize;
          this.documentResource.pageSize = this.paginator.pageSize;
          this.documentResource.orderBy =
            this.sort.active + ' ' + this.sort.direction;
          this.dataSource.loadDocuments(this.documentResource);
          this.selection.clear();
        })
      )
      .subscribe();

    this.sub$.sink = fromEvent(this.input.nativeElement, 'keyup')
      .pipe(
        debounceTime(1000),
        distinctUntilChanged(),
        tap(() => {
          this.paginator.pageIndex = 0;
          this.documentResource.skip = 0;
          this.documentResource.name = this.input.nativeElement.value;
          this.dataSource.loadDocuments(this.documentResource);
          this.selection.clear();
        })
      )
      .subscribe();

    this.sub$.sink = fromEvent(this.metatag.nativeElement, 'keyup')
      .pipe(
        debounceTime(1000),
        distinctUntilChanged(),
        tap(() => {
          this.paginator.pageIndex = 0;
          this.documentResource.skip = 0;
          this.documentResource.metaTags = this.metatag.nativeElement.value;
          this.dataSource.loadDocuments(this.documentResource);
        })
      )
      .subscribe();
  }

  onCategoryChange(filtervalue: string) {
    if (filtervalue) {
      this.documentResource.categoryId = filtervalue;
    } else {
      this.documentResource.categoryId = '';
    }
    this.documentResource.skip = 0;
    this.paginator.pageIndex = 0;
    this.dataSource.loadDocuments(this.documentResource);
  }

  onStorageChange(filtervalue: string) {
    if (filtervalue) {
      this.documentResource.location = filtervalue;
    } else {
      this.documentResource.location = '';
    }
    this.documentResource.skip = 0;
    this.paginator.pageIndex = 0;
    this.dataSource.loadDocuments(this.documentResource);
  }

  onDocumentStatusChange(filtervalue: string) {
    if (filtervalue) {
      this.documentResource.statusId = filtervalue;
    } else {
      this.documentResource.statusId = '';
    }
    this.documentResource.skip = 0;
    this.paginator.pageIndex = 0;
    this.dataSource.loadDocuments(this.documentResource);
  }

  getResourceParameter() {
    this.sub$.sink = this.dataSource.responseHeaderSubject$.subscribe(
      (c: ResponseHeader) => {
        if (c) {
          this.documentResource.pageSize = c.pageSize;
          this.documentResource.skip = c.skip;
          this.documentResource.totalCount = c.totalCount;
        }
      }
    );
  }

  addDocumentTrail(id: string, operation: string) {
    const objDocumentAuditTrail: DocumentAuditTrail = {
      documentId: id,
      operationName: operation,
    };
    this.sub$.sink = this.commonService
      .addDocumentAuditTrail(objDocumentAuditTrail)
      .subscribe();
  }

  archiveDocument(document: DocumentInfo) {
    this.sub$.sink = this.commonDialogService
      .deleteConformationDialog(
        this.translationService.getValue('ARE_YOU_SURE_YOU_WANT_TO_ARCHIVE'),
        document.name
      )
      .subscribe((isTrue: boolean) => {
        if (isTrue) {
          this.sub$.sink = this.documentService
            .archiveDocument(document.id)
            .subscribe(() => {
              this.addDocumentTrail(
                document.id,
                DocumentOperation.Archived.toString()
              );
              this.toastrService.success(
                this.translationService.getValue(
                  'DOCUMENT_ARCHIVED_SUCCESSFULLY'
                )
              );
              this.dataSource.loadDocuments(this.documentResource);
            });
        }
      });
  }

  activeDocument(id: string) {
    this.sub$.sink = this.commonDialogService
      .deleteConformationDialog(
        this.translationService.getValue('ARE_YOU_SURE_YOU_WANT_TO_RESOTRE')
      )
      .subscribe((isTrue) => {
        if (isTrue) {
          this.sub$.sink = this.expireDocumentService
            .activeDocument(id)
            .subscribe(() => {
              this.addDocumentTrail(id, DocumentOperation.Restored.toString());
              this.toastrService.success(
                this.translationService.getValue(
                  'DOCUMENT_RESTORED_SUCCESSFULLY'
                )
              );
              this.dataSource.loadDocuments(this.documentResource);
            });
        }
      });
  }


  onDocumentView(document: DocumentInfo) {
    const urls = document.url.split('.');
    const extension = urls[1];
    const documentView: DocumentView = {
      documentId: document.id,
      name: document.name,
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
  }
}
