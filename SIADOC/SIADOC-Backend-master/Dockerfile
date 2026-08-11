# 1. Étape de compilation (Build)
FROM maven:3.9.6-eclipse-temurin-21-jammy AS build
WORKDIR /app
COPY . .
RUN mvn clean package -DskipTests -B

# 2. Étape d'exécution (Run)
FROM eclipse-temurin:21-jre-jammy
WORKDIR /app
COPY --from=build /app/target/*.jar app.jar
# Utilisation du port injecté par Render ($PORT) ou 8080 par défaut
EXPOSE 8080
ENTRYPOINT ["java", "-Xmx400m", "-Dserver.port=${PORT:8080}", "-jar", "app.jar"]
