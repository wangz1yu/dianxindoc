import { ComponentFixture, TestBed } from '@angular/core/testing';

import { LogsRetentionSettingsComponent } from './logs-retention-settings.component';

describe('LogsRetentionSettingsComponent', () => {
  let component: LogsRetentionSettingsComponent;
  let fixture: ComponentFixture<LogsRetentionSettingsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [LogsRetentionSettingsComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(LogsRetentionSettingsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
