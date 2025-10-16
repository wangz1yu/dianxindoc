import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DocumentSignatureComponent } from './document-signature.component';

describe('DocumentSignatureComponent', () => {
  let component: DocumentSignatureComponent;
  let fixture: ComponentFixture<DocumentSignatureComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DocumentSignatureComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DocumentSignatureComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
