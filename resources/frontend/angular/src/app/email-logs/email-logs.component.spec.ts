import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EmailLogsComponent } from './email-logs.component';

describe('EmailLogsComponent', () => {
  let component: EmailLogsComponent;
  let fixture: ComponentFixture<EmailLogsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [EmailLogsComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EmailLogsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
