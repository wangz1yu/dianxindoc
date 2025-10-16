import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ArchiveDocumentRetensionPeriodDialogComponent } from './archive-document-retension-period-dialog.component';

describe('ArchiveDocumentRetensionPeriodDialogComponent', () => {
  let component: ArchiveDocumentRetensionPeriodDialogComponent;
  let fixture: ComponentFixture<ArchiveDocumentRetensionPeriodDialogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ArchiveDocumentRetensionPeriodDialogComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ArchiveDocumentRetensionPeriodDialogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
