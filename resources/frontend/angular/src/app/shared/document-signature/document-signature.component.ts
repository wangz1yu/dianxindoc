import { CommonModule } from '@angular/common';
import { Component, ElementRef, Inject, ViewChild } from '@angular/core';
import { ReactiveFormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatOptionModule } from '@angular/material/core';
import {
  MAT_DIALOG_DATA,
  MatDialogModule,
  MatDialogRef,
} from '@angular/material/dialog';
import { MatSelectModule } from '@angular/material/select';
import { TranslationService } from '@core/services/translation.service';
import { SharedModule } from '@shared/shared.module';
import { FeatherModule } from 'angular-feather';
import { ToastrService } from 'ngx-toastr';
import SignaturePad from 'signature_pad';
import { PipesModule } from '@shared/pipes/pipes.module';
import { MatProgressSpinnerModule, ProgressSpinnerMode } from '@angular/material/progress-spinner';
import { DocumentService } from 'src/app/document/document.service';
import { DocumentSignature } from '@core/domain-classes/document-signature';
import { DocumentInfo } from '@core/domain-classes/document-info';
import { DocumentAuditTrail } from '@core/domain-classes/document-audit-trail';
import { DocumentOperation } from '@core/domain-classes/document-operation';
import { CommonService } from '@core/services/common.service';

@Component({
  selector: 'app-document-signature',
  standalone: true,
  imports: [
    MatDialogModule,
    FeatherModule,
    SharedModule,
    ReactiveFormsModule,
    MatButtonModule,
    MatOptionModule,
    MatSelectModule,
    PipesModule,
    CommonModule,
    MatProgressSpinnerModule
  ],
  templateUrl: './document-signature.component.html',
  styleUrl: './document-signature.component.scss',
})
export class DocumentSignatureComponent {
  @ViewChild('canvas', { static: true })
  canvasRef!: ElementRef<HTMLCanvasElement>;
  signaturePad!: SignaturePad;
  documentSignatures: DocumentSignature[] = [];
  uploadedSignature: string | null = null;
  mode: ProgressSpinnerMode = 'indeterminate';
  constructor(
    private toastrService: ToastrService,
    private translationService: TranslationService,
    private documentService: DocumentService,
    private dialogRef: MatDialogRef<DocumentSignatureComponent>,
    @Inject(MAT_DIALOG_DATA) public data: DocumentInfo,
    private commonService: CommonService
  ) { }

  ngOnInit() {
    this.initializeSignaturePad();
    this.getDocumentSignatures();
  }

  getDocumentSignatures() {
    this.documentService.getDocumentSignature(this.data.id)
      .subscribe((documentSignatures: DocumentSignature[]) => {
        this.documentSignatures = documentSignatures;
      });
  }

  initializeSignaturePad() {
    const canvas = this.canvasRef.nativeElement;
    this.signaturePad = new SignaturePad(canvas);

    this.signaturePad.addEventListener('beginStroke', () => {
      if (this.uploadedSignature) {
        this.uploadedSignature = null;

        const fileInput = document.querySelector('input[type="file"].signature-upload') as HTMLInputElement;
        if (fileInput) fileInput.value = '';
      }
    });

    this.resizeCanvas();
  }

  resizeCanvas() {
    const canvas = this.canvasRef.nativeElement;
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.width = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    canvas.getContext('2d')?.scale(ratio, ratio);
  }

  clearSignature() {
    this.signaturePad.clear();
  }


  saveSignature() {
    let signatureData: string | null = null;

    if (this.signaturePad && !this.signaturePad.isEmpty()) {
      signatureData = this.signaturePad.toDataURL();
    }
    else if (this.uploadedSignature) {
      signatureData = this.uploadedSignature;
    }
    if (!signatureData || signatureData === 'data:image/png;base64,' || signatureData === 'data:image/jpeg;base64,') {
      this.toastrService.error(this.translationService.getValue('PLEASE_PROVIDE_SIGNATURE'));
      return;
    }
    this.uploadSignature(signatureData);
  }

  uploadSignature(signatureData: string) {
    const documentSignature: DocumentSignature = {
      documentId: this.data.id,
      signatureUrl: signatureData,
    };

    this.documentService.saveDocumentSignature(documentSignature).subscribe({
      next: (savedSignature: DocumentSignature) => {
        this.documentSignatures.push(savedSignature);
        this.addDocumentTrail();
        this.dialogRef.close(true);
        this.uploadedSignature = null;
        this.signaturePad?.clear();
      },
      error: () => {
        this.toastrService.error(this.translationService.getValue('SIGNATURE_UPLOAD_FAILED'));
      },
    });
  }

  onFileSelected(event: Event) {
    const fileInput = event.target as HTMLInputElement;
    const file = fileInput.files?.[0];
    if (!file) return;

    if (file.type !== 'image/png' && file.type !== 'image/jpeg') {
      this.toastrService.error(this.translationService.getValue('INVALID_FILE_TYPE'));
      return;
    }

    const reader = new FileReader();
    reader.onload = () => {
      const image = new Image();
      image.src = reader.result as string;

      image.onload = () => {
        const canvas = this.canvasRef.nativeElement;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = canvas.width;
        tempCanvas.height = canvas.height;
        const tempCtx = tempCanvas.getContext('2d');
        if (!tempCtx) return;
        tempCtx.clearRect(0, 0, tempCanvas.width, tempCanvas.height);
        tempCtx.drawImage(image, 0, 0, tempCanvas.width, tempCanvas.height);
        this.uploadedSignature = tempCanvas.toDataURL(file.type === 'image/jpeg' ? 'image/jpeg' : 'image/png');
        this.signaturePad.clear();
      };
    };
    reader.readAsDataURL(file);
  }

  addDocumentTrail() {
    const documentAuditTrail: DocumentAuditTrail = {
      documentId: this.data.id,
      operationName: DocumentOperation.Added_Signature.toString(),
    };
    this.commonService
      .addDocumentAuditTrail(documentAuditTrail)
      .subscribe(() => { });
  }

  closeDialog() {
    this.dialogRef.close();
  }
}
