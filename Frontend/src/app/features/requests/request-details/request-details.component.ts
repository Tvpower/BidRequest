import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { RequestService } from '../../../core/services/request.service';
import { Request } from '../../../core/models/request.model';
import { switchMap } from 'rxjs/operators';
import { Observable, of } from 'rxjs';

@Component({
  selector: 'app-request-details',
  templateUrl: './request-details.component.html',
  styleUrls: ['./request-details.component.scss']
})
export class RequestDetailsComponent implements OnInit {
  request: Request | null = null;
  loading = true;
  error = false;
  requestId!: number;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private requestService: RequestService
  ) {}

  ngOnInit(): void {
    this.route.paramMap.pipe(
      switchMap(params => {
        const id = params.get('id');
        if (id) {
          this.requestId = +id;
          return this.requestService.getRequestById(+id);
        }
        return of(null);
      })
    ).subscribe({
      next: (data) => {
        this.loading = false;
        if (data) {
          this.request = data;
        } else {
          this.error = true;
        }
      },
      error: (err) => {
        this.loading = false;
        this.error = true;
        console.error('Error fetching request details:', err);
      }
    });
  }

  goBack(): void {
    this.router.navigate(['/']);
  }

  submitBid(): void {
    // Navigate to bid submission page with the request ID
    this.router.navigate(['/bids/submit', this.requestId]);
  }
}
