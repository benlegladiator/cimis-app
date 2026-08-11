package com.siadoc.backend.repository;

import com.siadoc.backend.model.Notification;
import com.siadoc.backend.model.Utilisateur;
import com.siadoc.backend.model.Bataillon;
import com.siadoc.backend.model.Compagnie;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import java.util.List;
import java.util.UUID;

public interface NotificationRepository extends JpaRepository<Notification, UUID> {
    @Query("SELECT n FROM Notification n LEFT JOIN FETCH n.militaire LEFT JOIN FETCH n.dossierConcerne WHERE n.destinataire = :destinataire ORDER BY n.dateCreation DESC")
    List<Notification> findByDestinataireOrderByDateCreationDesc(Utilisateur destinataire);

    @Query("SELECT n FROM Notification n LEFT JOIN FETCH n.militaire LEFT JOIN FETCH n.dossierConcerne WHERE n.bataillonConcerne = :bataillon AND n.lu = false ORDER BY n.dateCreation DESC")
    List<Notification> findByBataillonConcerneAndLuFalseOrderByDateCreationDesc(Bataillon bataillon);

    @Query("SELECT n FROM Notification n LEFT JOIN FETCH n.militaire LEFT JOIN FETCH n.dossierConcerne WHERE n.brigadeConcernee = :brigade AND n.lu = false ORDER BY n.dateCreation DESC")
    List<Notification> findByBrigadeConcerneeAndLuFalseOrderByDateCreationDesc(com.siadoc.backend.model.Brigade brigade);

    @Query("SELECT n FROM Notification n LEFT JOIN FETCH n.militaire LEFT JOIN FETCH n.dossierConcerne WHERE n.regionConcernee = :region AND n.lu = false ORDER BY n.dateCreation DESC")
    List<Notification> findByRegionConcerneeAndLuFalseOrderByDateCreationDesc(com.siadoc.backend.model.RegionMilitaire region);

    @Query("SELECT n FROM Notification n LEFT JOIN FETCH n.militaire LEFT JOIN FETCH n.dossierConcerne WHERE n.compagnieConcernee = :compagnie AND n.lu = false ORDER BY n.dateCreation DESC")
    List<Notification> findByCompagnieConcerneeAndLuFalseOrderByDateCreationDesc(Compagnie compagnie);
    @Query("SELECT n FROM Notification n WHERE n.dossierConcerne = :dossier AND n.type = :type AND n.lu = false")
    List<Notification> findByDossierConcerneAndTypeAndLuFalse(com.siadoc.backend.model.DossierAdministratif dossier, com.siadoc.backend.model.TypeNotification type);

    @Query("SELECT n FROM Notification n WHERE n.militaire = :militaire AND n.type = :type AND n.lu = false")
    List<Notification> findByMilitaireAndTypeAndLuFalse(com.siadoc.backend.model.Militaire militaire, com.siadoc.backend.model.TypeNotification type);
}
