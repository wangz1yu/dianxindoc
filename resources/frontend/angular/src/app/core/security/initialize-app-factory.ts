import { SecurityService } from './security.service';
import { ToastrService } from 'ngx-toastr';

export function initializeApp(toastrService: ToastrService, securityService: SecurityService): () => Promise<void> {
  return () => new Promise<void>((resolve) => {
    // Skip license check
    resolve();
  });
}

