package com.siadoc.backend.dto;

import lombok.Data;
import java.util.UUID;

@Data
public class MappingGesmilRequestDTO {
    private String codeGesmil;
    private UUID compagnieId;
}
