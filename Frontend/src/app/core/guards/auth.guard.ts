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
    if (this.authService.isLoggedIn) {
      // Check for role restrictions if specified in route data
      if (route.data['roles'] && route.data['roles'].length > 0) {
        const userType = this.authService.currentUser?.user_type;
        if (!userType || !route.data['roles'].includes(userType)) {
          this.router.navigate(['/']);
          return false;
        }
      }
      return true;
    }
    
    // Store attempted URL for redirecting after login
    this.router.navigate(['/auth/login'], { 
      queryParams: { returnUrl: state.url }
    });
    return false;
  }
}
