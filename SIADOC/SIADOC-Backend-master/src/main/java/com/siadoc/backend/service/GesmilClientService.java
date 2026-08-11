package com.siadoc.backend.service;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.*;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.util.Map;

@Service
public class GesmilClientService {

    @Value("${gesmil.api.base-url}")
    private String baseUrl;

    @Value("${gesmil.api.token}")
    private String token;

    private final RestTemplate restTemplate;

    public GesmilClientService() {
        org.springframework.http.client.SimpleClientHttpRequestFactory factory = new org.springframework.http.client.SimpleClientHttpRequestFactory();
        factory.setConnectTimeout(10000);
        factory.setReadTimeout(10000);
        this.restTemplate = new RestTemplate(factory);
    }

    /**
     * Récupère le fichier de situation d'un militaire depuis GESMIL
     */
    public Map<String, Object> getFichierSituation(String matricule) {
        // Dans l'exemple curl, l'IdMilitaire est utilisé dans l'URL
        String url = baseUrl + "/services/rhsoftmsgap/api/personnel/" + matricule + "/fichier-situation";
        
        System.out.println("Appel GESMIL API : " + url);
        
        HttpHeaders headers = new HttpHeaders();
        headers.set("Authorization", "Bearer " + token);
        headers.set("Accept", "application/json, text/plain, */*");
        headers.set("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36");
        headers.set("Origin", "http://localhost:4200");
        headers.set("Referer", "http://localhost:4200/");
        
        HttpEntity<String> entity = new HttpEntity<>(headers);
        
        try {
            ResponseEntity<Map> response = restTemplate.exchange(
                url, 
                HttpMethod.GET, 
                entity, 
                Map.class
            );
            return response.getBody();
        } catch (Exception e) {
            System.err.println("Erreur appel GESMIL : " + e.getMessage());
            throw new RuntimeException("Erreur de communication avec GESMIL : " + e.getMessage());
        }
    }
}
