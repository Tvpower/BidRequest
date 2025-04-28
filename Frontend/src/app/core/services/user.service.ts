import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { User } from '../models/user.model';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class UserService {
  constructor(private http: HttpClient) { }

  getUserProfile(userId: number): Observable<User> {
    return this.http.get<User>(`${environment.apiUrl}/users/user.php?id=${userId}`);
  }

  updateUserProfile(userId: number, userData: Partial<User>): Observable<any> {
    return this.http.put<any>(`${environment.apiUrl}/users/user.php?id=${userId}`, userData);
  }
}
