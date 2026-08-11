package com.siadoc.backend.config;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.web.multipart.support.StandardServletMultipartResolver;
import org.springframework.web.servlet.config.annotation.*;

@Configuration
public class WebConfig implements WebMvcConfigurer {

    private final ApiKeyInterceptor apiKeyInterceptor;
    private final CacheControlInterceptor cacheControlInterceptor;

    @Value("${ALLOWED_ORIGINS:http://localhost:4200}")
    private String allowedOrigins;

    public WebConfig(ApiKeyInterceptor apiKeyInterceptor, CacheControlInterceptor cacheControlInterceptor) {
        this.apiKeyInterceptor = apiKeyInterceptor;
        this.cacheControlInterceptor = cacheControlInterceptor;
    }

    @Override
    public void addCorsMappings(CorsRegistry registry) {
        String[] origins = allowedOrigins.split(",");
        System.out.println("CORS CONFIG: Autorisation des origines : " + java.util.Arrays.toString(origins));
        
        registry.addMapping("/**")
                .allowedOrigins(origins)
                .allowedMethods("GET", "POST", "PUT", "DELETE", "OPTIONS", "PATCH")
                .allowedHeaders("*")
                .allowCredentials(true)
                .maxAge(3600);
    }

    @Override
    public void addInterceptors(InterceptorRegistry registry) {
        registry.addInterceptor(apiKeyInterceptor);
        registry.addInterceptor(cacheControlInterceptor);
    }

    @Bean
    public StandardServletMultipartResolver multipartResolver() {
        return new StandardServletMultipartResolver();
    }
}
