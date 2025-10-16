import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AiDocumentSummaryComponent } from './ai-document-summary.component';

describe('AiDocumentSummaryComponent', () => {
  let component: AiDocumentSummaryComponent;
  let fixture: ComponentFixture<AiDocumentSummaryComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AiDocumentSummaryComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AiDocumentSummaryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
