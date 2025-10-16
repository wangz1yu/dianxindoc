import { Pipe, PipeTransform } from '@angular/core';
import { RetentionActionEnum } from '@core/domain-classes/retention-action-enum';

@Pipe({
  name: 'retentionAction',
})

export class RetentionActionPipe implements PipeTransform {
  transform(value: any, ...args: any[]): any {
    if (value === RetentionActionEnum.ARCHIVE) {
      return 'Archive';
    }
    else if (value === RetentionActionEnum.PERMANENT_DELETE) {
      return 'Permanent Delete';
    }
    else if (value === RetentionActionEnum.EXPIRE) {
      return 'Expire';
    }
  }
}

