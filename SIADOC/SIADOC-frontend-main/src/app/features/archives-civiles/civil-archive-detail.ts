import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterModule } from '@angular/router';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { PersonnelCivilService } from '../../core/personnel-civil.service';
import { PersonnelCivil, DocumentCivil } from '../../core/models';

@Component({
  selector: 'app-civil-archive-detail',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './civil-archive-detail.html',
  styleUrls: ['./civil-archive-detail.scss']
})
export class CivilArchiveDetail implements OnInit {
  personnel: PersonnelCivil | null = null;
  documents: DocumentCivil[] = [];
  
  showUploadForm = false;
  newDoc: any = { label: '', file: null };

  documentSelectionne: any = null;

  constructor(
    private route: ActivatedRoute,
    private service: PersonnelCivilService,
    private sanitizer: DomSanitizer
  ) {}

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.service.getById(id).subscribe(data => this.personnel = data);
      this.chargerDocuments(id);
    }
  }

  chargerDocuments(id: string) {
    this.service.listerDocuments(id).subscribe(data => this.documents = data);
  }

  toggleUpload() {
    this.showUploadForm = !this.showUploadForm;
  }

  onFileSelected(event: any) {
    const file = event.target.files[0];
    if (file) this.newDoc.file = file;
  }

  televerser() {
    if (!this.personnel?.id || !this.newDoc.file || !this.newDoc.label) return;

    this.service.ajouterDocument(this.personnel.id, this.newDoc.label, this.newDoc.file)
      .subscribe(() => {
        this.showUploadForm = false;
        this.chargerDocuments(this.personnel!.id!);
        this.newDoc = { label: '', file: null };
      });
  }

  voirDocument(doc: DocumentCivil) {
    const url = this.service.downloadUrl(doc.id);
    this.documentSelectionne = {
      ...doc,
      safeUrl: this.sanitizer.bypassSecurityTrustResourceUrl(url)
    };
    // Scroll smoothly to preview
    setTimeout(() => {
      const el = document.querySelector('.preview-box');
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
  }

  fermerPreview() {
    this.documentSelectionne = null;
  }

  getDownloadUrl(docId: string) {
    return this.service.downloadUrl(docId);
  }
}
