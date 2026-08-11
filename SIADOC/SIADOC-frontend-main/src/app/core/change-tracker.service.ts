import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class ChangeTrackerService {
  private modifiedModules = new Set<string>();

  markAsModified(moduleName: string) {
    console.log(`Module marqué comme modifié : ${moduleName}`);
    this.modifiedModules.add(moduleName);
  }

  getModifiedModulesList(): string {
    return Array.from(this.modifiedModules).join(', ');
  }

  hasChanges(): boolean {
    return this.modifiedModules.size > 0;
  }

  clear() {
    this.modifiedModules.clear();
  }
}
