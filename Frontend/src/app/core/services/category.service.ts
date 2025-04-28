import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Category, CategoriesResponse } from '../models/category.model';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class CategoryService {
  constructor(private http: HttpClient) { }

  getCategories(): Observable<CategoriesResponse> {
    return this.http.get<CategoriesResponse>(`${environment.apiUrl}/categories/index.php`);
  }

  getCategoryById(categoryId: number): Observable<Category> {
    return this.http.get<Category>(`${environment.apiUrl}/categories/category.php?id=${categoryId}`);
  }
}
