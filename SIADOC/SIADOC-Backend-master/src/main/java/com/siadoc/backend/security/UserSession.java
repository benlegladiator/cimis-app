package com.siadoc.backend.security;

import com.siadoc.backend.model.Utilisateur;
import org.springframework.context.annotation.ScopedProxyMode;
import org.springframework.stereotype.Component;
import org.springframework.web.context.annotation.SessionScope;

@Component
@SessionScope(proxyMode = ScopedProxyMode.TARGET_CLASS)
public class UserSession {

    private Utilisateur currentUser;

    public void login(Utilisateur user) {
        this.currentUser = user;
    }

    public Utilisateur getCurrentUser() {
        return currentUser;
    }

    public void logout() {
        this.currentUser = null;
    }

    public boolean isLoggedIn() {
        return currentUser != null;
    }
}
