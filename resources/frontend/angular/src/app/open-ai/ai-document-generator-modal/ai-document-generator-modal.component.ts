import { CommonModule } from '@angular/common';
import { Component, Inject, OnInit } from '@angular/core';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { CKEditorModule } from '@ckeditor/ckeditor5-angular';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import { OpenAiDocuments } from '@core/domain-classes/open-ai-documents';
import { SharedModule } from '@shared/shared.module';
import { marked } from 'marked';
import { AIModel, AI_MODELS } from '../models.config';
import { MatSelectModule } from '@angular/material/select';

@Component({
  selector: 'app-ai-document-generator-modal',
  standalone: true,
  imports: [
    CKEditorModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    MatDialogModule,
    SharedModule,
    MatSelectModule
  ],
  templateUrl: './ai-document-generator-modal.component.html',
  styleUrl: './ai-document-generator-modal.component.scss'
})
export class AiDocumentGeneratorModalComponent implements OnInit {
  editor = ClassicEditor;
  parseData = '';
  models: AIModel[] = AI_MODELS;
  groupedModels: { [key: string]: AIModel[] } = {};

  get providers(): string[] {
    return Object.keys(this.groupedModels);
  }

  constructor(
    @Inject(MAT_DIALOG_DATA) public data: OpenAiDocuments,
    private dialogRef: MatDialogRef<AiDocumentGeneratorModalComponent>) {
    this.groupedModels = this.models.reduce((acc, model) => {
      (acc[model.provider] = acc[model.provider] || []).push(model);
      return acc;
    }, {} as { [key: string]: AIModel[] });
  }

  ngOnInit(): void {
    this.parseData = marked.parse(this.data.response) as string;;
  }

  onCancel(): void {
    this.dialogRef.close();
  }
}
