import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BidCreateComponent } from './bid-create.component';

describe('BidCreateComponent', () => {
  let component: BidCreateComponent;
  let fixture: ComponentFixture<BidCreateComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [BidCreateComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BidCreateComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
