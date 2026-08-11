package com.siadoc.backend.dto;

import lombok.Data;
import java.util.List;

@Data
public class MedicalGlobalDTO {
    private List<BlessureDTO> blessures;
    private List<PensionDTO> pensions;
    private List<DocumentMedicalDTO> documents;

    public List<BlessureDTO> getBlessures() { return blessures; }
    public void setBlessures(List<BlessureDTO> blessures) { this.blessures = blessures; }
    public List<PensionDTO> getPensions() { return pensions; }
    public void setPensions(List<PensionDTO> pensions) { this.pensions = pensions; }
    public List<DocumentMedicalDTO> getDocuments() { return documents; }
    public void setDocuments(List<DocumentMedicalDTO> documents) { this.documents = documents; }
}
