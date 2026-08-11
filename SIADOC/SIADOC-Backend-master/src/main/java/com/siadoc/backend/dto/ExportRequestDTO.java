package com.siadoc.backend.dto;

import lombok.Data;
import java.util.List;

@Data
public class ExportRequestDTO {
    private String militaireId;
    private List<PieceItem> pieces;

    public String getMilitaireId() { return militaireId; }
    public void setMilitaireId(String militaireId) { this.militaireId = militaireId; }
    public List<PieceItem> getPieces() { return pieces; }
    public void setPieces(List<PieceItem> pieces) { this.pieces = pieces; }

    @Data
    public static class PieceItem {
        private String type;
        private String id;

        public String getType() { return type; }
        public void setType(String type) { this.type = type; }
        public String getId() { return id; }
        public void setId(String id) { this.id = id; }
    }
}
