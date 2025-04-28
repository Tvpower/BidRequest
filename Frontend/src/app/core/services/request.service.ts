import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
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
    type?: 'service' | 'product';
  } = {}): Observable<RequestsResponse> {
    // Build query string from params
    const queryParams = Object.entries(params)
      .filter(([_, value]) => value !== undefined)
      .map(([key, value]) => `${key}=${value}`)
      .join('&');
    
    const url = `${environment.apiUrl}/requests/index.php${queryParams ? '?' + queryParams : ''}`;
    return this.http.get<any>(url).pipe(
      map(response => {
        // Extract requests from the nested data structure
        if (response && response.data && response.data.requests) {
          return {
            requests: response.data.requests,
            pagination: response.data.pagination || {
              total: 0,
              page: 1,
              limit: 10,
              total_pages: 0
            }
          };
        }
        // Return empty array if structure doesn't match
        return { 
          requests: [],
          pagination: {
            total: 0,
            page: 1,
            limit: 10,
            total_pages: 0
          }
        };
      })
    );
  }

  getRequestById(requestId: number): Observable<Request> {
    return this.http.get<any>(`${environment.apiUrl}/requests/request.php?id=${requestId}`).pipe(
      map(response => {
        // Extract request from the nested data structure
        if (response && response.data) {
          return response.data;
        }
        // Return empty object if structure doesn't match
        return {};
      })
    );
  }

  createRequest(requestData: Partial<Request>): Observable<any> {
    return this.http.post<any>(`${environment.apiUrl}/requests/index.php`, requestData).pipe(
      map(response => {
        return response && response.data ? response.data : {};
      })
    );
  }

  updateRequest(requestId: number, requestData: Partial<Request>): Observable<any> {
    return this.http.put<any>(`${environment.apiUrl}/requests/request.php?id=${requestId}`, requestData).pipe(
      map(response => {
        return response && response.data ? response.data : {};
      })
    );
  }

  deleteRequest(requestId: number): Observable<any> {
    return this.http.delete<any>(`${environment.apiUrl}/requests/request.php?id=${requestId}`).pipe(
      map(response => {
        return response && response.data ? response.data : {};
      })
    );
  }
}
