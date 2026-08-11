import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Mutation } from './mutation';

describe('Mutation', () => {
  let component: Mutation;
  let fixture: ComponentFixture<Mutation>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Mutation]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Mutation);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
