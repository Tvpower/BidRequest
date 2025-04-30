import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, tap, map } from 'rxjs';
import { User, AuthResponse } from '../models/user.model';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser$ = this.currentUserSubject.asObservable();
  private tokenKey = 'auth_token';
  private userKey = 'user_data';

  constructor(private http: HttpClient) {
    this.loadStoredUser();
  }

  private loadStoredUser(): void {
    const storedToken = localStorage.getItem(this.tokenKey);
    const storedUser = localStorage.getItem(this.userKey);
    
    if (storedToken && storedUser) {
      try {
        const user = JSON.parse(storedUser);
        this.currentUserSubject.next(user);
      } catch (e) {
        this.logout();
      }
    }
  }

  login(email: string, password: string): Observable<AuthResponse> {
    return this.http.post<any>(`${environment.apiUrl}/auth/login.php`, { email, password })
      .pipe(
        map(response => {
          // Extract the actual AuthResponse from the nested structure
          if (response && response.success && response.data) {
            return response.data as AuthResponse;
          }
          throw new Error('Invalid response structure');
        }),
        tap(authResponse => {
          this.storeAuthData(authResponse);
        })
      );
  }

  register(userData: {
    username: string;
    email: string;
    password: string;
    user_type: 'buyer' | 'seller';
    company_name?: string;
    contact_info?: string;
  }): Observable<any> {
    return this.http.post<any>(`${environment.apiUrl}/auth/register.php`, userData);
  }

  logout(): void {
    localStorage.removeItem(this.tokenKey);
    localStorage.removeItem(this.userKey);
    this.currentUserSubject.next(null);
  }

  private storeAuthData(authResponse: AuthResponse): void {
    console.log('Auth response received:', authResponse);
    if (!authResponse || !authResponse.token || !authResponse.user) {
      console.error('Invalid auth response structure:', authResponse);
      return;
    }
    
    localStorage.setItem(this.tokenKey, authResponse.token);
    localStorage.setItem(this.userKey, JSON.stringify(authResponse.user));
    this.currentUserSubject.next(authResponse.user);
    console.log('Auth data stored, current user:', this.currentUserSubject.value);
    console.log('Token in localStorage:', localStorage.getItem(this.tokenKey));
    console.log('User in localStorage:', localStorage.getItem(this.userKey));
  }

  get currentUser(): User | null {
    return this.currentUserSubject.value;
  }

  get isLoggedIn(): boolean {
    return !!this.currentUserSubject.value;
  }

  get token(): string | null {
    return localStorage.getItem(this.tokenKey);
  }

  get isBuyer(): boolean {
    return this.currentUser?.user_type === 'buyer';
  }

  get isSeller(): boolean {
    return this.currentUser?.user_type === 'seller';
  }
}
