import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Medical } from './medical';

describe('Medical', () => {
  let component: Medical;
  let fixture: ComponentFixture<Medical>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Medical]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Medical);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
