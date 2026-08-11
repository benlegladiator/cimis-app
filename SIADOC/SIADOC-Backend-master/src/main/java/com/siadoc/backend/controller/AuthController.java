package com.siadoc.backend.controller;

import com.siadoc.backend.model.Utilisateur;
import com.siadoc.backend.repository.UtilisateurRepository;
import com.siadoc.backend.security.UserSession;
import com.siadoc.backend.dto.LoginRequest;
import jakarta.servlet.http.HttpServletRequest;

import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/auth")
public class AuthController {

    private final UtilisateurRepository utilisateurRepository;
    private final UserSession userSession;

    public AuthController(UtilisateurRepository utilisateurRepository,
                          UserSession userSession) {
        this.utilisateurRepository = utilisateurRepository;
        this.userSession = userSession;
    }

    @PostMapping("/login")
    public org.springframework.http.ResponseEntity<?> login(@RequestBody LoginRequest request,
                                                           HttpServletRequest httpRequest) {

        Utilisateur user = utilisateurRepository
                .findByUsername(request.getUsername())
                .orElse(null);

        if (user == null || !user.getPassword().equals(request.getPassword())) {
            return org.springframework.http.ResponseEntity.status(org.springframework.http.HttpStatus.UNAUTHORIZED)
                    .body("Identifiant ou mot de passe incorrect");
        }

        // 🔥 FORCER LA CREATION DE SESSION
        httpRequest.getSession(true);
        userSession.login(user);

        return org.springframework.http.ResponseEntity.ok(user);
    }


    @PostMapping("/logout")
    public org.springframework.http.ResponseEntity<?> logout(HttpServletRequest httpRequest) {
        userSession.logout();
        jakarta.servlet.http.HttpSession session = httpRequest.getSession(false);
        if (session != null) {
            session.invalidate();
        }
        return org.springframework.http.ResponseEntity.ok("Déconnecté");
    }

    @PutMapping("/me/password")
    public Utilisateur changePassword(@RequestBody com.siadoc.backend.dto.ChangePasswordRequest request) {
        Utilisateur currentUser = userSession.getCurrentUser();
        if (currentUser == null) {
            throw new RuntimeException("Non autorisé - Session expirée");
        }
        
        // Fetch fresh entity to ensure we can save
        Utilisateur user = utilisateurRepository.findById(currentUser.getId())
            .orElseThrow(() -> new RuntimeException("Utilisateur introuvable"));
            
        if (!user.getPassword().equals(request.getOldPassword())) {
            throw new RuntimeException("Ancien mot de passe incorrect");
        }
        
        user.setPassword(request.getNewPassword());
        utilisateurRepository.save(user);
        
        // Update session user to prevent logging them out immediately due to stale data if there are checks
        // We will log them out in the frontend anyway 
        userSession.login(user);
        
        return user;
    }

    @GetMapping("/me")
    public Utilisateur currentUser() {
        return userSession.getCurrentUser();
    }
}
