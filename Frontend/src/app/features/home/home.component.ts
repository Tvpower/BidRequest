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
  latestRequests: Request[] = [];
  categories: Category[] = [];
  isLoading = true;
  errorMessage = '';

  constructor(
    private requestService: RequestService,
    private categoryService: CategoryService
  ) { }

  ngOnInit(): void {
    this.loadLatestRequests();
    this.loadCategories();
  }

  private loadLatestRequests(): void {
    this.requestService.getRequests({ limit: 6 }).subscribe({
      next: (response) => {
        this.latestRequests = response.requests;
        this.isLoading = false;
      },
      error: (error) => {
        this.errorMessage = 'Failed to load latest requests. Please try again later.';
        this.isLoading = false;
        console.error('Error loading requests:', error);
      }
    });
  }

  private loadCategories(): void {
    this.categoryService.getCategories().subscribe({
      next: (response) => {
        this.categories = response.categories;
      },
      error: (error) => {
        console.error('Error loading categories:', error);
      }
    });
  }
}
