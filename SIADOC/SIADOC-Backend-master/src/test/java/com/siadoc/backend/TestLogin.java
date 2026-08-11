package com.siadoc.backend;

import java.io.*;
import java.net.HttpURLConnection;
import java.net.URL;

public class TestLogin {

    public static void main(String[] args) {
        testLogin("cmd_rmia1", "password");
    }

    private static void testLogin(String username, String password) {
        try {
            System.out.println("TESTING LOGIN FOR: " + username);
            URL url = new URL("http://localhost:8080/api/auth/login");
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("POST");
            conn.setRequestProperty("Content-Type", "application/json");
            conn.setDoOutput(true);
            
            String json = "{\"username\":\"" + username + "\", \"password\":\"" + password + "\"}";
            try(OutputStream os = conn.getOutputStream()) {
                byte[] input = json.getBytes("utf-8");
                os.write(input, 0, input.length);			
            }

            int code = conn.getResponseCode();
            System.out.println("RESPONSE CODE: " + code);

            InputStream is = code == 200 ? conn.getInputStream() : conn.getErrorStream();
            BufferedReader br = new BufferedReader(new InputStreamReader(is, "utf-8"));
            StringBuilder response = new StringBuilder();
            String responseLine = null;
            while ((responseLine = br.readLine()) != null) {
                response.append(responseLine.trim());
            }
            System.out.println("RESPONSE BODY: " + response.toString());
            
            if (code == 200) {
                String cookie = conn.getHeaderField("Set-Cookie");
                System.out.println("COOKIE RECEIVED: " + cookie);

                if (cookie != null) {
                    URL api = new URL("http://localhost:8080/api/militaires");
                    HttpURLConnection conn2 = (HttpURLConnection) api.openConnection();
                    conn2.setRequestMethod("GET");
                    conn2.setRequestProperty("Cookie", cookie);

                    int code2 = conn2.getResponseCode();
                    System.out.println("API RESPONSE CODE: " + code2);
                    
                    InputStream is2 = code2 == 200 ? conn2.getInputStream() : conn2.getErrorStream();
                    BufferedReader br2 = new BufferedReader(new InputStreamReader(is2, "utf-8"));
                    StringBuilder resp2 = new StringBuilder();
                    String line2;
                    while ((line2 = br2.readLine()) != null) resp2.append(line2);
                    
                    System.out.println("API BODY: " + resp2.toString().substring(0, Math.min(200, resp2.length())) + "...");
                }
            }

        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}
