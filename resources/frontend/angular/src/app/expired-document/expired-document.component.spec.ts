import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ExpiredDocumentComponent } from './expired-document.component';

describe('ExpiredDocumentComponent', () => {
  let component: ExpiredDocumentComponent;
  let fixture: ComponentFixture<ExpiredDocumentComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ExpiredDocumentComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ExpiredDocumentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
