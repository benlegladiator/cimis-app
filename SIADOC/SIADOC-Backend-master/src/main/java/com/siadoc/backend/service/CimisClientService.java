package com.siadoc.backend.service;

import com.siadoc.backend.dto.CimisResponseDTO;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.*;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.util.Map;

@Service
public class CimisClientService {

    @Value("${cimis.api.base-url}")
    private String baseUrl;

    @Value("${cimis.api.key}")
    private String apiKey;

    private final RestTemplate restTemplate;

    public CimisClientService() {
        org.springframework.http.client.SimpleClientHttpRequestFactory factory = new org.springframework.http.client.SimpleClientHttpRequestFactory();
        factory.setConnectTimeout(10000);
        factory.setReadTimeout(10000);
        this.restTemplate = new RestTemplate(factory);
    }

    /**
     * Récupère une carte par matricule depuis CIMIS
     * Correction basée sur le code PHP réel : utilisation de action=cartes pour éviter les conflits de slashes
     */
    public CimisResponseDTO getCarte(String matricule) {
        // On utilise action=cartes&matricule= car le matricule contient des slashes (ex: T14/6584)
        // ce qui casse les routes de type /carte/matricule dans leur PHP.
        String url = baseUrl + "?action=cartes&matricule=" + matricule;
        System.out.println("Appel CIMIS URL : " + url);
        
        HttpHeaders headers = new HttpHeaders();
        headers.set("X-API-KEY", apiKey);
        headers.set("Accept", "application/json, text/plain, */*");
        headers.set("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36");
        headers.set("Accept-Language", "fr,fr-FR;q=0.9,fr-CA;q=0.8,en-US;q=0.7,en-GB;q=0.6,en;q=0.5");
        headers.set("Origin", "http://localhost:4200");
        headers.set("Referer", "http://localhost:4200/");
        headers.set("Cache-Control", "no-cache");
        headers.set("Pragma", "no-cache");
        
        HttpEntity<String> entity = new HttpEntity<>(headers);
        
        try {
            ResponseEntity<Map> response = restTemplate.exchange(
                url, 
                HttpMethod.GET, 
                entity, 
                Map.class
            );
            
            Map<String, Object> responseBody = response.getBody();
            
            if (responseBody != null && Boolean.TRUE.equals(responseBody.get("success"))) {
                Map<String, Object> data = (Map<String, Object>) responseBody.get("data");
                
                // Dans le cas de action=cartes, les données sont dans une liste "cartes"
                if (data.containsKey("cartes")) {
                    java.util.List<Map<String, Object>> cartes = (java.util.List<Map<String, Object>>) data.get("cartes");
                    if (!cartes.isEmpty()) {
                        return mapToCimisResponseDTO(cartes.get(0));
                    }
                }
                throw new RuntimeException("Aucun militaire trouvé pour le matricule : " + matricule);
            } else {
                String message = responseBody != null ? (String) responseBody.get("message") : "Erreur inconnue";
                throw new RuntimeException("CIMIS API : " + message);
            }
            
        } catch (org.springframework.web.client.HttpStatusCodeException e) {
            System.err.println("CIMIS API Error (" + e.getStatusCode() + ") : " + e.getResponseBodyAsString());
            throw new RuntimeException("CIMIS a retourné une erreur HTTP : " + e.getStatusCode());
        } catch (Exception e) {
            System.err.println("Erreur critique lors de l'appel CIMIS : " + e.getMessage());
            e.printStackTrace();
            throw new RuntimeException("Erreur de connexion au serveur CIMIS : " + e.getMessage());
        }
    }
    
    /**
     * Récupère la liste paginée des militaires depuis CIMIS avec filtres
     */
    public Map<String, Object> getListeCartes(int page, int limit, String grade, String unite, String search) {
        StringBuilder urlBuilder = new StringBuilder(baseUrl);
        urlBuilder.append("?action=cartes");
        urlBuilder.append("&page=").append(page);
        urlBuilder.append("&limit=").append(limit);
        
        if (grade != null && !grade.isEmpty()) urlBuilder.append("&grade=").append(grade);
        if (unite != null && !unite.isEmpty()) urlBuilder.append("&unite=").append(unite);
        if (search != null && !search.isEmpty()) urlBuilder.append("&search=").append(search);
        
        String url = urlBuilder.toString();
        System.out.println("API SIADOC -> CIMIS : Appel Liste URL : " + url);
        
        HttpHeaders headers = new HttpHeaders();
        headers.set("X-API-KEY", apiKey);
        headers.set("Accept", "application/json, text/plain, */*");
        headers.set("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36");
        headers.set("Accept-Language", "fr,fr-FR;q=0.9,fr-CA;q=0.8,en-US;q=0.7,en-GB;q=0.6,en;q=0.5");
        headers.set("Origin", "http://localhost:4200");
        headers.set("Referer", "http://localhost:4200/");
        headers.set("Connection", "keep-alive");
        headers.set("Cache-Control", "no-cache");
        headers.set("Pragma", "no-cache");
        
        HttpEntity<String> entity = new HttpEntity<>(headers);
        
        try {
            // On demande d'abord la réponse en String pour pouvoir inspecter le contenu si c'est du HTML
            ResponseEntity<String> response = restTemplate.exchange(url, HttpMethod.GET, entity, String.class);
            String responseBody = response.getBody();
            
            if (responseBody == null || responseBody.isEmpty()) {
                System.err.println("API SIADOC <- CIMIS : Réponse vide");
                throw new RuntimeException("Le serveur CIMIS a renvoyé une réponse vide.");
            }

            // Si la réponse commence par '<', c'est probablement du HTML (une page d'erreur)
            if (responseBody.trim().startsWith("<")) {
                System.err.println("API SIADOC <- CIMIS : Erreur HTML reçue : " + responseBody);
                // On extrait un petit bout du HTML pour l'utilisateur
                String snippet = responseBody.length() > 200 ? responseBody.substring(0, 200) : responseBody;
                throw new RuntimeException("Le serveur CIMIS a renvoyé du HTML au lieu de JSON. Début de la réponse : " + snippet);
            }

            // On tente de parser le JSON manuellement via Jackson (ObjectMapper)
            com.fasterxml.jackson.databind.ObjectMapper mapper = new com.fasterxml.jackson.databind.ObjectMapper();
            Map<String, Object> body = mapper.readValue(responseBody, Map.class);
            
            if (Boolean.TRUE.equals(body.get("success"))) {
                Map<String, Object> data = (Map<String, Object>) body.get("data");
                
                // Normalisation : Le frontend attend 'militaires', mais CIMIS renvoie peut-être 'cartes'
                if (data != null && data.containsKey("cartes") && !data.containsKey("militaires")) {
                    data.put("militaires", data.get("cartes"));
                }
                
                return data;
            } else {
                String errorMsg = body.containsKey("message") ? (String) body.get("message") : "Erreur inconnue";
                System.err.println("API SIADOC <- CIMIS : Echec (success=false) : " + errorMsg);
                throw new RuntimeException("CIMIS API : " + errorMsg);
            }
        } catch (org.springframework.web.client.HttpStatusCodeException e) {
            System.err.println("API SIADOC <- CIMIS : Erreur HTTP " + e.getStatusCode() + " : " + e.getResponseBodyAsString());
            throw new RuntimeException("Erreur de communication avec CIMIS (HTTP " + e.getStatusCode() + ")");
        } catch (Exception e) {
            System.err.println("API SIADOC <- CIMIS : Erreur critique : " + e.getMessage());
            throw new RuntimeException("Erreur lors de la récupération de la liste CIMIS : " + e.getMessage());
        }
    }

    /**
     * Récupère les statistiques globales depuis CIMIS
     */
    public Map<String, Object> getStatistiques() {
        String url = baseUrl + "?action=statistiques";
        System.out.println("API SIADOC -> CIMIS : Appel Statistiques URL : " + url);

        HttpHeaders headers = new HttpHeaders();
        headers.set("X-API-KEY", apiKey);
        headers.set("Accept", "application/json");

        HttpEntity<String> entity = new HttpEntity<>(headers);

        try {
            ResponseEntity<Map> response = restTemplate.exchange(url, HttpMethod.GET, entity, Map.class);
            Map<String, Object> body = response.getBody();

            if (body != null && Boolean.TRUE.equals(body.get("success"))) {
                Object data = body.get("data");
                if (data instanceof Map) {
                    return (Map<String, Object>) data;
                }
            }
        } catch (Exception e) {
            System.err.println("Erreur stats CIMIS : " + e.getMessage());
        }
        return new java.util.HashMap<>();
    }

    /**
     * Mapper la réponse de l'API CIMIS vers notre DTO
     */
    private CimisResponseDTO mapToCimisResponseDTO(Map<String, Object> data) {
        CimisResponseDTO dto = new CimisResponseDTO();
        
        dto.setMatricule((String) data.get("matricule"));
        dto.setNom((String) data.get("nom"));
        dto.setPrenom((String) data.get("prenom"));
        dto.setGrade((String) data.get("grade"));
        dto.setUnite((String) data.get("unite"));
        dto.setDateNaissance((String) data.get("date_naissance"));
        dto.setDateEnrolement((String) data.get("date_enrolement"));
        dto.setDateDernierGrade((String) data.get("date_dernier_grade"));
        dto.setSexe((String) data.get("sexe"));
        dto.setMatriculeMilitaire((String) data.get("matricule_militaire"));
        dto.setMatriculeCimis((String) data.get("matricule_cimis"));
        dto.setNumeroCni((String) data.get("numero_cni"));
        dto.setTaille((String) data.get("taille"));
        dto.setPoids((String) data.get("poids"));
        dto.setGroupeSanguin((String) data.get("groupe_sanguin"));
        dto.setPhotoBase64((String) data.get("photo_base64"));
        dto.setEmpreinteData((String) data.get("empreinte_data"));
        dto.setCodeQr((String) data.get("code_qr"));
        dto.setTypePersonnel((String) data.get("type_personnel"));
        dto.setStatut((String) data.get("statut"));
        dto.setSourceSystem((String) data.get("source_system"));
        dto.setDateModification((String) data.get("date_modification"));
        dto.setSyncStatus((String) data.get("sync_status"));
        
        return dto;
    }

    public String getHelp() {
        String url = baseUrl + "?help=true";
        HttpHeaders headers = new HttpHeaders();
        headers.set("X-API-KEY", apiKey);
        
        HttpEntity<String> entity = new HttpEntity<>(headers);
        
        try {
            ResponseEntity<String> response = restTemplate.exchange(url, HttpMethod.GET, entity, String.class);
            return response.getBody();
        } catch (Exception e) {
            return "Erreur connection CIMIS : " + e.getMessage();
        }
    }
}
