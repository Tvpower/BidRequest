import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-register',
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.scss']
})
export class RegisterComponent implements OnInit {
  registerForm: FormGroup;
  isSubmitting = false;
  errorMessage = '';
  successMessage = '';
  
  constructor(
    private formBuilder: FormBuilder,
    private authService: AuthService,
    private router: Router
  ) {
    this.registerForm = this.formBuilder.group({
      username: ['', [Validators.required, Validators.minLength(3)]],
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]],
      user_type: ['buyer', Validators.required],
      company_name: [''],
      contact_info: ['']
    });
  }

  ngOnInit(): void {
    // Redirect if already logged in
    if (this.authService.isLoggedIn) {
      this.router.navigate(['/']);
    }
    
    // Show company fields only for sellers
    this.registerForm.get('user_type')?.valueChanges.subscribe(userType => {
      if (userType === 'seller') {
        this.registerForm.get('company_name')?.setValidators([Validators.required]);
        this.registerForm.get('contact_info')?.setValidators([Validators.required]);
      } else {
        this.registerForm.get('company_name')?.clearValidators();
        this.registerForm.get('contact_info')?.clearValidators();
      }
      this.registerForm.get('company_name')?.updateValueAndValidity();
      this.registerForm.get('contact_info')?.updateValueAndValidity();
    });
  }

  onSubmit(): void {
    if (this.registerForm.invalid) {
      return;
    }

    this.isSubmitting = true;
    this.errorMessage = '';
    this.successMessage = '';

    this.authService.register(this.registerForm.value).subscribe({
      next: (response) => {
        this.isSubmitting = false;
        this.successMessage = 'Registration successful! You can now log in.';
        setTimeout(() => {
          this.router.navigate(['/auth/login']);
        }, 2000);
      },
      error: (error) => {
        this.isSubmitting = false;
        if (error.status === 409) {
          this.errorMessage = 'Email already exists';
        } else {
          this.errorMessage = error.error?.message || 'Registration failed. Please try again later.';
        }
        console.error('Registration error:', error);
      }
    });
  }

  // Getter for easy access to form fields
  get f() { return this.registerForm.controls; }
}
