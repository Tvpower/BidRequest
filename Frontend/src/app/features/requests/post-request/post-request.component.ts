import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, FormArray, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { RequestService } from '../../../core/services/request.service';
import { Request, RequestSpecification } from '../../../core/models/request.model';
import { Observable } from 'rxjs';

interface Category {
  category_id: number;
  name: string;
}

@Component({
  selector: 'app-post-request',
  templateUrl: './post-request.component.html',
  styleUrls: ['./post-request.component.scss']
})
export class PostRequestComponent implements OnInit {
  requestForm!: FormGroup;
  categories: Category[] = [];
  submitting = false;
  submitError = '';
  requestTypes = ['product', 'service'];
  conditions = ['new', 'like-new', 'good', 'fair', 'poor', 'any'];

  constructor(
    private fb: FormBuilder,
    private router: Router,
    private requestService: RequestService
  ) {}

  ngOnInit(): void {
    this.initForm();
    this.loadCategories();
  }

  private initForm(): void {
    this.requestForm = this.fb.group({
      title: ['', [Validators.required, Validators.minLength(5), Validators.maxLength(100)]],
      description: ['', [Validators.required, Validators.minLength(20), Validators.maxLength(1000)]],
      category_id: ['', Validators.required],
      type: ['product', Validators.required],
      budget: [null],
      desired_condition: ['any'],
      expiration_date: ['', Validators.required],
      specifications: this.fb.array([])
    });

    // Listen for type changes to show/hide relevant fields
    this.requestForm.get('type')?.valueChanges.subscribe(type => {
      if (type === 'product') {
        this.requestForm.get('desired_condition')?.enable();
      } else {
        this.requestForm.get('desired_condition')?.disable();
      }
    });
  }

  private loadCategories(): void {
    // This would typically call a category service
    // For now, we'll use some sample categories
    this.categories = [
      { category_id: 1, name: 'Electronics' },
      { category_id: 2, name: 'Furniture' },
      { category_id: 3, name: 'Clothing' },
      { category_id: 4, name: 'Services' },
      { category_id: 5, name: 'Other' }
    ];
  }

  get specificationsArray(): FormArray {
    return this.requestForm.get('specifications') as FormArray;
  }
  getSpecificationFormGroups(): FormGroup[] {
    return this.specificationsArray.controls as FormGroup[];
  }

  addSpecification(): void {
    const specGroup = this.fb.group({
      specification_type: ['', Validators.required],
      specification_value: ['', Validators.required],
      is_required: [true]
    });

    this.specificationsArray.push(specGroup);
  }

  removeSpecification(index: number): void {
    this.specificationsArray.removeAt(index);
  }

  onSubmit(): void {
    if (this.requestForm.invalid) {
      // Mark all fields as touched to trigger validation messages
      Object.keys(this.requestForm.controls).forEach(key => {
        const control = this.requestForm.get(key);
        control?.markAsTouched();
      });
      return;
    }

    this.submitting = true;
    this.submitError = '';

    // Prepare the request data
    const requestData: Partial<Request> = {
      ...this.requestForm.value,
      // Convert string IDs to numbers if needed
      category_id: +this.requestForm.value.category_id,
      // Format specifications if needed
      specifications: this.requestForm.value.specifications as RequestSpecification[]
    };

    this.requestService.createRequest(requestData).subscribe({
      next: (response) => {
        this.submitting = false;
        // Navigate to the request details page
        if (response && response.request_id) {
          this.router.navigate(['/requests/details', response.request_id]);
        } else {
          // If no ID is returned, go to home page
          this.router.navigate(['/']);
        }
      },
      error: (error) => {
        this.submitting = false;
        this.submitError = 'Failed to create request. Please try again.';
        console.error('Error creating request:', error);
      }
    });
  }

  cancel(): void {
    this.router.navigate(['/']);
  }
}
