import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss']
})
export class LoginComponent implements OnInit {
  loginForm: FormGroup;
  isSubmitting = false;
  errorMessage = '';
  returnUrl: string = '/';

  constructor(
    private formBuilder: FormBuilder,
    private authService: AuthService,
    private router: Router,
    private route: ActivatedRoute
  ) {
    this.loginForm = this.formBuilder.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', Validators.required]
    });
  }

  ngOnInit(): void {
    // Get return URL from route parameters or default to '/'
    this.returnUrl = this.route.snapshot.queryParams['returnUrl'] || '/';
    
    // Redirect if already logged in
    if (this.authService.isLoggedIn) {
      this.router.navigate([this.returnUrl]);
    }
  }

  onSubmit(): void {
    if (this.loginForm.invalid) {
      return;
    }

    this.isSubmitting = true;
    this.errorMessage = '';

    const { email, password } = this.loginForm.value;
    console.log('Attempting login with email:', email);

    this.authService.login(email, password).subscribe({
      next: (response) => {
        console.log('Login successful, response:', response);
        console.log('Current auth state:', this.authService.isLoggedIn);
        console.log('Return URL:', this.returnUrl);
        
        // Small delay to ensure auth state is updated before navigation
        setTimeout(() => {
          this.router.navigate([this.returnUrl]);
        }, 100);
      },
      error: (error) => {
        this.isSubmitting = false;
        console.error('Login error details:', error);
        
        if (error.status === 401) {
          this.errorMessage = 'Invalid email or password';
        } else if (error.status === 404) {
          this.errorMessage = 'User not found';
        } else if (error.status === 0) {
          this.errorMessage = 'Network error. Please check your connection.';
        } else if (error.status === 408) {
          this.errorMessage = 'Request timeout. Please try again.';
        } else {
          this.errorMessage = `Login failed (${error.status}). Please try again later.`;
        }
      }
    });
  }

  // Getter for easy access to form fields
  get f() { return this.loginForm.controls; }
}
