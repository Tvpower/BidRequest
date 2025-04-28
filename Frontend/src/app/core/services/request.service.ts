import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Request, RequestsResponse } from '../models/request.model';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class RequestService {
  constructor(private http: HttpClient) { }

  getRequests(params: {
    page?: number;
    limit?: number;
    category_id?: number;
    status?: string;
    user_id?: number;
  } = {}): Observable<RequestsResponse> {
    // Build query string from params
    const queryParams = Object.entries(params)
      .filter(([_, value]) => value !== undefined)
      .map(([key, value]) => `${key}=${value}`)
      .join('&');
    
    const url = `${environment.apiUrl}/requests/index.php${queryParams ? '?' + queryParams : ''}`;
    return this.http.get<RequestsResponse>(url);
  }

  getRequestById(requestId: number): Observable<Request> {
    return this.http.get<Request>(`${environment.apiUrl}/requests/request.php?id=${requestId}`);
  }

  createRequest(requestData: Partial<Request>): Observable<any> {
    return this.http.post<any>(`${environment.apiUrl}/requests/index.php`, requestData);
  }

  updateRequest(requestId: number, requestData: Partial<Request>): Observable<any> {
    return this.http.put<any>(`${environment.apiUrl}/requests/request.php?id=${requestId}`, requestData);
  }

  deleteRequest(requestId: number): Observable<any> {
    return this.http.delete<any>(`${environment.apiUrl}/requests/request.php?id=${requestId}`);
  }
}
