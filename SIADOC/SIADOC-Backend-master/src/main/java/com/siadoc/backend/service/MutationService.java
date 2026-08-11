package com.siadoc.backend.service;

import com.siadoc.backend.dto.MutationItemDTO;
import com.siadoc.backend.dto.MutationRequestDTO;
import com.siadoc.backend.dto.MutationsModuleDTO;
import com.siadoc.backend.dto.search.RechercheMutationDTO;
import com.siadoc.backend.dto.search.ResultRechercheMutationDTO;
import com.siadoc.backend.model.DossierAdministratif;
import com.siadoc.backend.model.MutationItem;
import com.siadoc.backend.model.MutationsModule;
import com.siadoc.backend.model.TypeMutation;
import com.siadoc.backend.model.UniteMilitaire;
import com.siadoc.backend.model.Militaire;
import com.siadoc.backend.model.Compagnie;
import com.siadoc.backend.repository.CompagnieRepository;
import com.siadoc.backend.repository.MilitaireRepository;
import com.siadoc.backend.repository.DossierAdministratifRepository;
import com.siadoc.backend.repository.MutationItemRepository;
import com.siadoc.backend.repository.MutationsModuleRepository;
import com.siadoc.backend.model.Notification;
import com.siadoc.backend.model.StatutDossier;
import com.siadoc.backend.repository.NotificationRepository;
import jakarta.transaction.Transactional;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.util.List;
import java.util.UUID;
import java.util.stream.Collectors;

@Service
@Transactional
public class MutationService {

    private final MutationsModuleRepository moduleRepository;
    private final MutationItemRepository itemRepository;
    private final DossierAdministratifRepository dossierRepository;
    private final CompagnieRepository compagnieRepository;
    private final MilitaireRepository militaireRepository;
    private final NotificationRepository notificationRepository;
    private final DossierAdministratifService dossierService;

    public MutationService(MutationsModuleRepository moduleRepository,
                          MutationItemRepository itemRepository,
                          DossierAdministratifRepository dossierRepository,
                          CompagnieRepository compagnieRepository,
                          MilitaireRepository militaireRepository,
                          NotificationRepository notificationRepository,
                          DossierAdministratifService dossierService) {
        this.moduleRepository = moduleRepository;
        this.itemRepository = itemRepository;
        this.dossierRepository = dossierRepository;
        this.compagnieRepository = compagnieRepository;
        this.militaireRepository = militaireRepository;
        this.notificationRepository = notificationRepository;
        this.dossierService = dossierService;
    }

    // 1. RECUPERER ET TRIER
    public MutationsModuleDTO getMutationsByMilitaire(UUID militaireId) {
        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId)
                .orElseThrow(() -> new RuntimeException("Dossier introuvable"));

        MutationsModule module = dossier.getMutationsModule(); // À adapter selon ton Dossier (getMutationsModule)
        if (module == null) {
            // Création lazy si inexistant
            module = new MutationsModule();
            module.setDossier(dossier);
            module = moduleRepository.save(module);
        }

        MutationsModuleDTO dto = new MutationsModuleDTO();

        // Stream pour séparer les listes
        if (module.getItems() != null) {
            dto.setAffectations(module.getItems().stream()
                    .filter(i -> i.getType() == TypeMutation.AFFECTATION)
                    .map(this::mapToItemDTO).collect(Collectors.toList()));

            dto.setFonctions(module.getItems().stream()
                    .filter(i -> i.getType() == TypeMutation.FONCTION)
                    .map(this::mapToItemDTO).collect(Collectors.toList()));
        }
        return dto;
    }

    private String like(String v) {
        if (v == null || v.trim().isEmpty()) return null;
        return "%" + v.trim() + "%";
    }

    public List<ResultRechercheMutationDTO> rechercher(RechercheMutationDTO dto) {
        return itemRepository.rechercherMutations(
                like(dto.getNom()),
                like(dto.getPrenom()),
                dto.getGrade(),
                dto.getArme(),
                like(dto.getProvenance()),
                like(dto.getDestination()),
                dto.getAnnee()
        );
    }

    // 2. AJOUTER (Affectation OU Fonction)
    public MutationItemDTO addItem(UUID militaireId, MutationRequestDTO request, MultipartFile file) throws IOException {
        DossierAdministratif dossier = dossierRepository.findByMilitaireId(militaireId).orElseThrow();
        MutationsModule module = dossier.getMutationsModule(); 

        MutationItem item = new MutationItem();
        item.setModule(module);
        if (module.getItems() == null) module.setItems(new java.util.ArrayList<>());
        module.getItems().add(item);
        item.setType(request.getType()); 
        mapRequestToEntity(item, request, file);

        // LOGIQUE DE TRANSFERT REEL
        if (request.getType() == TypeMutation.AFFECTATION && request.getCompagnieId() != null) {
            Compagnie dest = compagnieRepository.findById(request.getCompagnieId())
                    .orElseThrow(() -> new RuntimeException("Compagnie de destination introuvable"));
            
            item.setCompagnie(dest); 
            dossier.setCompagnie(dest); 
            dossier.setStatut(StatutDossier.EN_ATTENTE_VALIDATION); 
            
            Notification notif = Notification.builder()
                .message("Mutation : Le dossier de " + dossier.getMilitaire().getNom() + " " + dossier.getMilitaire().getPrenom() + " est arrivé.")
                .dateCreation(java.time.LocalDateTime.now())
                .compagnieConcernee(dest)
                .militaire(dossier.getMilitaire())
                .lu(false)
                .build();
            notificationRepository.save(notif);
        }

        MutationItem saved = itemRepository.save(item);
        dossierRepository.save(dossier);
        dossierService.notifierModification(dossier, "Affectations/Fonctions");
        return mapToItemDTO(saved);
    }

    // 3. MODIFIER
    public MutationItemDTO updateItem(UUID itemId, MutationRequestDTO request, MultipartFile file) throws IOException {
        MutationItem item = itemRepository.findById(itemId)
                .orElseThrow(() -> new RuntimeException("Item introuvable"));

        // On ne change pas le TYPE ni le MODULE lors d'une update
        mapRequestToEntity(item, request, file);

        MutationItem saved = itemRepository.save(item);
        dossierService.notifierModification(item.getModule().getDossier(), "Affectations/Fonctions");
        return mapToItemDTO(saved);
    }

    // 4. SUPPRIMER
    public void deleteItem(UUID itemId) {
        itemRepository.deleteById(itemId);
    }

    // --- HELPER MAPPING ---
    private void mapRequestToEntity(MutationItem item, MutationRequestDTO dto, MultipartFile file) throws IOException {
        item.setEmploi(dto.getEmploi());
        item.setUnite(dto.getUnite());
        item.setVille(dto.getVille());
        item.setNumeroTexte(dto.getNumeroTexte());
        item.setDateTexte(dto.getDateTexte());

        if (file != null && !file.isEmpty()) {
            item.setDocumentData(file.getBytes());
            item.setDocumentNom(file.getOriginalFilename());
            item.setDocumentType(file.getContentType());
        }
    }

    private MutationItemDTO mapToItemDTO(MutationItem item) {
        MutationItemDTO dto = new MutationItemDTO();
        dto.setId(item.getId());
        dto.setEmploi(item.getEmploi());
        dto.setUnite(item.getUnite());
        dto.setVille(item.getVille());
        dto.setNumeroTexte(item.getNumeroTexte());
        dto.setDateTexte(item.getDateTexte() != null ? item.getDateTexte().toString() : null);
        dto.setType(item.getType() != null ? item.getType().name() : null);
        if (item.getCompagnie() != null) dto.setCompagnieNom(item.getCompagnie().getNom());
        dto.setDocumentNom(item.getDocumentNom());
        dto.setDocumentType(item.getDocumentType());
        return dto;
    }

    // =============================
// RECUPERER UN ITEM (DOCUMENT)
// =============================
    public MutationItem getItem(UUID itemId) {
        return itemRepository.findById(itemId)
                .orElseThrow(() -> new RuntimeException("Mutation introuvable"));
    }

    @Transactional
    public void affecterMilitaire(UUID militaireId, UUID compagnieId){

        Militaire militaire = militaireRepository.findById(militaireId)
                .orElseThrow(() -> new RuntimeException("Militaire introuvable"));

        Compagnie compagnie = compagnieRepository.findById(compagnieId)
                .orElseThrow(() -> new RuntimeException("Compagnie introuvable"));

        DossierAdministratif dossier = militaire.getDossier();

        MutationsModule module = dossier.getMutationsModule();

        // création mutation
        MutationItem mutation = new MutationItem();
        mutation.setModule(module);
        mutation.setType(TypeMutation.AFFECTATION);
        mutation.setUnite(UniteMilitaire.COMPAGNIE);
        mutation.setCompagnie(compagnie);

        itemRepository.save(mutation);
    }

}
