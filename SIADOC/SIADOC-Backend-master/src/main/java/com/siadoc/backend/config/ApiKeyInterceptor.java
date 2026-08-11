package com.siadoc.backend.config;

import com.siadoc.backend.service.ApiKeyService;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Component;
import org.springframework.web.servlet.HandlerInterceptor;

@Component
@RequiredArgsConstructor
public class ApiKeyInterceptor implements HandlerInterceptor {

    private final ApiKeyService apiKeyService;
    private final com.siadoc.backend.security.UserSession userSession;

    @Override
    public boolean preHandle(HttpServletRequest request, HttpServletResponse response, Object handler) throws Exception {
        String uri = request.getRequestURI();

        // Routes protégées par API Key :
        //   - /api/export/**  → export des données vers les applications partenaires (ex: CIMIS)
        //   - /api/import/cimis/**  → réception native des données biométriques depuis CIMIS
        //   - /api/cimis/** → contrôleur passerelle (webhook d'intégration directe avec CIMIS)
        boolean estRouteProtegee = uri.startsWith("/api/export") 
                                || uri.startsWith("/api/import/cimis")
                                || uri.startsWith("/api/cimis");

        if (estRouteProtegee) {

            // Si l'utilisateur est déjà connecté via la session (Interface SIADOC), on laisse passer
            if (userSession.isLoggedIn()) {
                return true;
            }

            String apiKey = request.getHeader("X-API-KEY");

            if (apiKey == null || !apiKeyService.isValid(apiKey)) {
                response.setStatus(HttpServletResponse.SC_UNAUTHORIZED);
                response.setHeader("Content-Type", "text/plain;charset=UTF-8");
                response.getWriter().write("Clé d'API invalide ou manquante (X-API-KEY)");
                return false;
            }
        }
        return true;
    }
}
