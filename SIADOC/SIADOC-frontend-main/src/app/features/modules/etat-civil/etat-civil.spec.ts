import { TestBed } from '@angular/core/testing';

import { EtatCivil } from './etat-civil';

describe('EtatCivil', () => {
  let service: EtatCivil;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(EtatCivil);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
