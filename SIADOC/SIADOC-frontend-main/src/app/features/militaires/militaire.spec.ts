import { TestBed } from '@angular/core/testing';

import { Militaire } from './militaire';

describe('Militaire', () => {
  let service: Militaire;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Militaire);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
