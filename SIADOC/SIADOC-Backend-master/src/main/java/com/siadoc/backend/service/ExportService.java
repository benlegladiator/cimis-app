package com.siadoc.backend.service;

import com.lowagie.text.*;
import com.lowagie.text.pdf.PdfWriter;
import com.siadoc.backend.dto.ExportRequestDTO;
import com.siadoc.backend.model.Militaire;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;

import java.io.ByteArrayOutputStream;
import java.util.List;
import java.util.UUID;

@Service
public class ExportService {

    private static final org.slf4j.Logger log = org.slf4j.LoggerFactory.getLogger(ExportService.class);
    private final MilitaireService militaireService;

    public ExportService(MilitaireService militaireService) {
        this.militaireService = militaireService;
    }

    public byte[] generateArchivePdf(ExportRequestDTO request) {
        log.info("Début de génération PDF pour militaireId: {}", request.getMilitaireId());
        
        Militaire militaire = null;
        if (request.getMilitaireId() != null && !request.getMilitaireId().isEmpty()) {
            try {
                militaire = militaireService.getById(UUID.fromString(request.getMilitaireId()));
                log.info("Militaire trouvé: {} {}", militaire.getNom(), militaire.getPrenom());
            } catch (Exception e) {
                log.error("Erreur lors de la récupération du militaire: {}", e.getMessage());
            }
        }

        ByteArrayOutputStream out = new ByteArrayOutputStream();
        Document document = new Document(PageSize.A4);
        
        try {
            PdfWriter.getInstance(document, out);
            document.open();
            log.info("Document PDF ouvert");
            
            // Polices
            Font titleFont = FontFactory.getFont(FontFactory.HELVETICA_BOLD, 18);
            Font sectionFont = FontFactory.getFont(FontFactory.HELVETICA_BOLD, 12);
            Font normalFont = FontFactory.getFont(FontFactory.HELVETICA, 10);
            
            // Titre
            Paragraph title = new Paragraph("SIADOC - RÉCAPITULATIF D'ARCHIVE", titleFont);
            title.setAlignment(Element.ALIGN_CENTER);
            document.add(title);
            document.add(new Paragraph(" "));
            
            // Infos Militaire
            if (militaire != null) {
                document.add(new Paragraph("IDENTITÉ DU MILITAIRE", sectionFont));
                document.add(new Paragraph("Nom : " + (militaire.getNom() != null ? militaire.getNom() : "N/A"), normalFont));
                document.add(new Paragraph("Prénom : " + (militaire.getPrenom() != null ? militaire.getPrenom() : "N/A"), normalFont));
                document.add(new Paragraph("Matricule : " + (militaire.getMatriculeMilitaire() != null ? militaire.getMatriculeMilitaire() : "N/A"), normalFont));
                document.add(new Paragraph(" "));
            }
            
            // Liste des pièces
            document.add(new Paragraph("PIÈCES JUSTIFICATIVES SÉLECTIONNÉES", sectionFont));
            document.add(new Paragraph(" "));
            
            if (request.getPieces() != null && !request.getPieces().isEmpty()) {
                log.info("Ajout de {} pièces au PDF", request.getPieces().size());
                for (ExportRequestDTO.PieceItem item : request.getPieces()) {
                    String type = item.getType() != null ? item.getType().toUpperCase() : "? / Type inconnu";
                    document.add(new Paragraph("• Type : " + type + " | ID : " + item.getId(), normalFont));
                }
            } else {
                document.add(new Paragraph("Aucune pièce n'a été spécifiée.", normalFont));
            }
            
            document.add(new Paragraph(" "));
            document.add(new Paragraph("--------------------------------------------------", normalFont));
            
            document.close();
            log.info("Document PDF fermé avec succès");
        } catch (Exception e) {
            log.error("EXCEPTION CRITIQUE lors de la génération PDF: {}", e.getMessage(), e);
        }
        
        return out.toByteArray();
    }
}
