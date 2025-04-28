import { Component, OnInit } from '@angular/core';
import { RequestService } from '../../core/services/request.service';
import { CategoryService } from '../../core/services/category.service';
import { Request } from '../../core/models/request.model';
import { Category } from '../../core/models/category.model';

@Component({
  selector: 'app-home',
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.scss']
})
export class HomeComponent implements OnInit {
  latestServiceRequests: Request[] = [];
  latestProductRequests: Request[] = [];
  categories: Category[] = [];
  servicesLoading = true;
  productsLoading = true;
  servicesErrorMessage = '';
  productsErrorMessage = '';

  constructor(
    private requestService: RequestService,
    private categoryService: CategoryService
  ) { }

  ngOnInit(): void {
    this.loadLatestServiceRequests();
    this.loadLatestProductRequests();
    this.loadCategories();
  }

  private loadLatestServiceRequests(): void {
    this.requestService.getRequests({ limit: 3, type: 'service' }).subscribe({
      next: (response) => {
        this.latestServiceRequests = response?.requests || [];
        this.servicesLoading = false;
      },
      error: (error) => {
        this.servicesErrorMessage = 'Failed to load latest service requests. Please try again later.';
        this.servicesLoading = false;
        console.error('Error loading service requests:', error);
      }
    });
  }

  private loadLatestProductRequests(): void {
    this.requestService.getRequests({ limit: 3, type: 'product' }).subscribe({
      next: (response) => {
        this.latestProductRequests = response?.requests || [];
        this.productsLoading = false;
      },
      error: (error) => {
        this.productsErrorMessage = 'Failed to load latest product requests. Please try again later.';
        this.productsLoading = false;
        console.error('Error loading product requests:', error);
      }
    });
  }

  private loadCategories(): void {
    this.categoryService.getCategories().subscribe({
      next: (response) => {
        this.categories = response?.categories || [];
      },
      error: (error) => {
        console.error('Error loading categories:', error);
      }
    });
  }
}
