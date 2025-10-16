import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CronJobLogsComponent } from './cron-job-logs.component';

describe('CronJobLogsComponent', () => {
  let component: CronJobLogsComponent;
  let fixture: ComponentFixture<CronJobLogsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CronJobLogsComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CronJobLogsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
