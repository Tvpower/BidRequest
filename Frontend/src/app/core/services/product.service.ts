import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { Product, ProductsResponse } from '../models/product.model';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ProductService {
  constructor(private http: HttpClient) { }

  getProducts(params: {
    page?: number;
    limit?: number;
    category_id?: number;
    status?: string;
    user_id?: number;
    condition?: string;
    min_price?: number;
    max_price?: number;
  } = {}): Observable<ProductsResponse> {
    // Build query string from params
    const queryParams = Object.entries(params)
      .filter(([_, value]) => value !== undefined)
      .map(([key, value]) => `${key}=${value}`)
      .join('&');
    
    const url = `${environment.apiUrl}/products/index.php${queryParams ? '?' + queryParams : ''}`;
    return this.http.get<any>(url).pipe(
      map(response => {
        // Extract products from the nested data structure
        if (response && response.data && response.data.products) {
          return {
            products: response.data.products,
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
          products: [],
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

  getProductById(productId: number): Observable<Product> {
    return this.http.get<any>(`${environment.apiUrl}/products/product.php?id=${productId}`).pipe(
      map(response => {
        // Extract product from the nested data structure
        if (response && response.data) {
          return response.data;
        }
        // Return empty object if structure doesn't match
        return {};
      })
    );
  }

  createProduct(productData: Partial<Product>): Observable<any> {
    return this.http.post<any>(`${environment.apiUrl}/products/index.php`, productData).pipe(
      map(response => {
        return response && response.data ? response.data : {};
      })
    );
  }

  updateProduct(productId: number, productData: Partial<Product>): Observable<any> {
    return this.http.put<any>(`${environment.apiUrl}/products/product.php?id=${productId}`, productData).pipe(
      map(response => {
        return response && response.data ? response.data : {};
      })
    );
  }

  deleteProduct(productId: number): Observable<any> {
    return this.http.delete<any>(`${environment.apiUrl}/products/product.php?id=${productId}`).pipe(
      map(response => {
        return response && response.data ? response.data : {};
      })
    );
  }

  uploadProductImage(productId: number, imageFile: File): Observable<any> {
    const formData = new FormData();
    formData.append('product_id', productId.toString());
    formData.append('image', imageFile);

    return this.http.post<any>(`${environment.apiUrl}/products/upload-image.php`, formData).pipe(
      map(response => {
        return response && response.data ? response.data : {};
      })
    );
  }
}
