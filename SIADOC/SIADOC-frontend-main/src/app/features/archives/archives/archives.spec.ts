import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Archives } from './archives';

describe('Archives', () => {
  let component: Archives;
  let fixture: ComponentFixture<Archives>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Archives]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Archives);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
