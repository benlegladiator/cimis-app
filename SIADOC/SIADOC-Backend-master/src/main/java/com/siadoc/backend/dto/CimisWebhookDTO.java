package com.siadoc.backend.dto;

import lombok.Data;

@Data
public class CimisWebhookDTO {
    private String source;
    private String type;
    private String timestamp;
    private CimisCarteDataDTO data;
}
