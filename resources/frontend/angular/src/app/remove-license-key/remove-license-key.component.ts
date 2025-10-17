import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';

@Component({
  selector: 'app-remove-license-key',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule
  ],
  templateUrl: './remove-license-key.component.html',
  styleUrl: './remove-license-key.component.scss'
})
export class RemoveLicenseKeyComponent implements OnInit {
  activatedForm: FormGroup;
  router = inject(Router);

  ngOnInit(): void {
    this.createForm();
  }
  createForm(): void {
    this.activatedForm = new FormGroup({
      purchaseCode: new FormControl('', [Validators.required, Validators.minLength(36)])
    });
  }

  onDeactiveLicense(): void {
    if (this.activatedForm.invalid) {
      this.activatedForm.markAllAsTouched();
      return;
    }
    this.router.navigate(['/']);
  }
}
