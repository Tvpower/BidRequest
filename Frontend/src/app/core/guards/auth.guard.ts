import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

@Injectable({
  providedIn: 'root'
})
export class AuthGuard implements CanActivate {
  constructor(private authService: AuthService, private router: Router) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): boolean {
    console.log('AuthGuard checking authentication for route:', state.url);
    console.log('Current auth state:', this.authService.isLoggedIn);
    console.log('Current user:', this.authService.currentUser);
    console.log('Token exists:', !!this.authService.token);
    
    // Force reload the auth state from localStorage
    const storedToken = localStorage.getItem('auth_token');
    const storedUser = localStorage.getItem('user_data');
    console.log('Stored token exists:', !!storedToken);
    console.log('Stored user exists:', !!storedUser);
    
    if (this.authService.isLoggedIn) {
      console.log('User is logged in, proceeding with route');
      // Check for role restrictions if specified in route data
      if (route.data['roles'] && route.data['roles'].length > 0) {
        const userType = this.authService.currentUser?.user_type;
        console.log('Route requires roles:', route.data['roles'], 'User type:', userType);
        if (!userType || !route.data['roles'].includes(userType)) {
          console.log('User does not have required role, redirecting to home');
          this.router.navigate(['/']);
          return false;
        }
      }
      return true;
    }
    
    console.log('User is not logged in, redirecting to login');
    // Store attempted URL for redirecting after login
    this.router.navigate(['/auth/login'], { 
      queryParams: { returnUrl: state.url }
    });
    return false;
  }
}
