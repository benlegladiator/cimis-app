package com.siadoc.backend.service;

import com.siadoc.backend.model.ApiKey;
import com.siadoc.backend.repository.ApiKeyRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.security.SecureRandom;
import java.time.LocalDateTime;
import java.util.Base64;
import java.util.List;
import java.util.UUID;

@Service
@RequiredArgsConstructor
public class ApiKeyService {

    private final ApiKeyRepository apiKeyRepository;
    private static final SecureRandom secureRandom = new SecureRandom();
    private static final Base64.Encoder base64Encoder = Base64.getUrlEncoder().withoutPadding();

    @Transactional
    public ApiKey generateKey(String appName) {
        if (apiKeyRepository.existsByAppName(appName)) {
            throw new RuntimeException("Une clé existe déjà pour l'application : " + appName);
        }

        byte[] randomBytes = new byte[32];
        secureRandom.nextBytes(randomBytes);
        String key = "siadoc_sk_" + base64Encoder.encodeToString(randomBytes);

        ApiKey apiKey = ApiKey.builder()
                .appName(appName)
                .keyValue(key)
                .active(true)
                .build();

        return apiKeyRepository.save(apiKey);
    }

    public List<ApiKey> findAll() {
        return apiKeyRepository.findAll();
    }

    @Transactional
    public void deleteKey(UUID id) {
        apiKeyRepository.deleteById(id);
    }

    @Transactional
    public boolean isValid(String keyValue) {
        return apiKeyRepository.findByKeyValueAndActiveTrue(keyValue)
                .map(key -> {
                    key.setLastUsedAt(LocalDateTime.now());
                    apiKeyRepository.save(key);
                    return true;
                }).orElse(false);
    }
}
