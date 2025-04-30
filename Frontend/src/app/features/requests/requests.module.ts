import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { RequestsRoutingModule } from './requests-routing.module';
import { RequestDetailsComponent } from './request-details/request-details.component';
import { PostRequestComponent } from './post-request/post-request.component';
import {ReactiveFormsModule} from '@angular/forms';


@NgModule({
  declarations: [
    RequestDetailsComponent,
    PostRequestComponent
  ],
  imports: [
    CommonModule,
    RequestsRoutingModule,
    ReactiveFormsModule
  ]
})
export class RequestsModule { }
