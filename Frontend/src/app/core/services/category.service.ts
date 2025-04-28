import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { Category, CategoriesResponse } from '../models/category.model';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class CategoryService {
  constructor(private http: HttpClient) { }

  getCategories(): Observable<CategoriesResponse> {
    return this.http.get<any>(`${environment.apiUrl}/categories/index.php`).pipe(
      map(response => {
        // Extract categories from the nested data structure
        if (response && response.data && response.data.categories) {
          return { categories: response.data.categories };
        }
        // Return empty array if structure doesn't match
        return { categories: [] };
      })
    );
  }

  getCategoryById(categoryId: number): Observable<Category> {
    return this.http.get<any>(`${environment.apiUrl}/categories/category.php?id=${categoryId}`).pipe(
      map(response => {
        // Extract category from the nested data structure
        if (response && response.data) {
          return response.data;
        }
        // Return empty object if structure doesn't match
        return {};
      })
    );
  }
}
