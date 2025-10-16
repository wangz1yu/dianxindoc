import { Component, Inject, inject } from '@angular/core';
import { FormBuilder, FormGroup, FormsModule } from '@angular/forms';
import { OpenAIStreamService } from '../openai-stream.service';
import { AI_MODELS, AIModel } from '../models.config';
import { DocumentInfo } from '@core/domain-classes/document-info';
import { MAT_DIALOG_DATA, MatDialogModule } from '@angular/material/dialog';
import { MatSelectModule } from '@angular/material/select';
import { MatIconModule } from '@angular/material/icon';
import { SharedModule } from '@shared/shared.module';
import { ToastrService } from 'ngx-toastr';
import { TranslationService } from '@core/services/translation.service';
import { marked } from 'marked';
import { DialogRef } from '@angular/cdk/dialog';

@Component({
  selector: 'app-ai-document-summary',
  standalone: true,
  imports: [
    FormsModule,
    MatDialogModule,
    MatSelectModule,
    MatIconModule,
    SharedModule
  ],
  templateUrl: './ai-document-summary.component.html',
  styleUrl: './ai-document-summary.component.scss'
})
export class AiDocumentSummaryComponent {
  summaryForm: FormGroup;
  summary: string | null = null;
  fb = inject(FormBuilder);
  models: AIModel[] = AI_MODELS;
  groupedModels: { [key: string]: AIModel[] } = {};
  aiSummary = inject(OpenAIStreamService);
  toastrService = inject(ToastrService);
  translationService = inject(TranslationService);
  isLoading = false;

  get providers(): string[] {
    return Object.keys(this.groupedModels);
  }

  constructor(@Inject(MAT_DIALOG_DATA) public data: DocumentInfo, private dialog: DialogRef<AiDocumentSummaryComponent>) {
    this.summaryForm = this.fb.group({
      model: ['gemini-2.5-flash-lite']
    });

    this.groupedModels = this.models.reduce((acc, model) => {
      (acc[model.provider] = acc[model.provider] || []).push(model);
      return acc;
    }, {} as { [key: string]: AIModel[] });
  }

  generateSummary() {
    this.summary = null;
    const { model } = this.summaryForm.value;
    this.isLoading = true;
    this.aiSummary.summarize(model, this.data.id).subscribe({
      next: res => {
        this.isLoading = false;
        this.summary = marked.parse(res.summary) as string;
        this.toastrService.success(this.translationService.getValue('SUMMARY_GENERATED_SUCCESSFULLY'));
      }, error: () => {
        this.isLoading = false;
      }
    });
  }

  closeDialog() {
    this.dialog.close();
  }

  copySummary() {
    if (this.summary) {
      navigator.clipboard.writeText(this.summary.replace(/<[^>]+>/g, '')).then(() => {
        this.toastrService.success(this.translationService.getValue('SUMMARY_COPIED_TO_CLIPBOARD'));
      });
    }
  }
}
