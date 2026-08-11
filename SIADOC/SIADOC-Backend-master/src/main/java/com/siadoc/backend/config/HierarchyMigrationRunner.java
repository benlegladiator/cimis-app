package com.siadoc.backend.config;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.siadoc.backend.model.UniteOrganisationnelle;
import com.siadoc.backend.repository.UniteOrganisationnelleRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.CommandLineRunner;
import org.springframework.stereotype.Component;

import java.io.File;
import java.nio.file.Files;
import java.nio.file.Paths;

@Component
public class HierarchyMigrationRunner implements CommandLineRunner {

    @Autowired
    private UniteOrganisationnelleRepository uniteRepository;

    @Autowired
    private com.siadoc.backend.repository.ApiKeyRepository apiKeyRepository;

    @Override
    public void run(String... args) throws Exception {
        try {
            // 1. Initialisation de la clé API CIMIS si elle n'existe pas
            String cimisKey = "siadoc-2026-cimis-integration";
            if (apiKeyRepository.findByKeyValueAndActiveTrue(cimisKey).isEmpty()) {
                com.siadoc.backend.model.ApiKey key = new com.siadoc.backend.model.ApiKey();
                key.setAppName("CIMIS Integration");
                key.setKeyValue(cimisKey);
                key.setActive(true);
                apiKeyRepository.save(key);
                System.out.println("CIMIS API Key initialized successfully!");
            }

            // La migration de la hiérarchie est désormais effectuée via des scripts externes
            // ou des seeders plus adaptés à l'environnement.
            // On laisse l'initialisation de la clé API si besoin.
            
            // System.out.println("Starting hierarchy migration check...");
            // ... (logique supprimée car basée sur un chemin local)
        } catch (Exception e) {
            System.err.println("Migration failed: " + e.getMessage());
            e.printStackTrace();
            throw e;
        }
    }


    private void saveNode(JsonNode node, String type, UniteOrganisationnelle parent) {
        UniteOrganisationnelle u = new UniteOrganisationnelle();
        u.setNom(node.get("label").asText());
        u.setType(type);
        u.setDescription(node.has("description") ? node.get("description").asText() : "");
        u.setIcon(node.has("icon") ? node.get("icon").asText() : null);
        u.setParent(parent);

        uniteRepository.save(u);

        if (node.has("children") && node.get("children").isArray()) {
            for (JsonNode childNode : node.get("children")) {
                saveNode(childNode, type, u);
            }
        }
    }
}
