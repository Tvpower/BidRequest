import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { RequestDetailsComponent } from './request-details/request-details.component';
import { PostRequestComponent } from './post-request/post-request.component';
import { AuthGuard } from '../../core/guards/auth.guard';

const routes: Routes = [
  // Specific routes first
  { path: 'post', component: PostRequestComponent, canActivate: [AuthGuard] },
  { path: 'create', component: PostRequestComponent, canActivate: [AuthGuard] },
  { path: 'details/:id', component: RequestDetailsComponent },
  // Wildcard route for IDs should come last to avoid conflicts
  { path: ':id', component: RequestDetailsComponent }
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class RequestsRoutingModule { }
