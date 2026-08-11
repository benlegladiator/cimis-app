package com.siadoc.backend.dto.search;

import lombok.Data;

@Data
public class SearchFilterDTO {

    private String field;     // sexe, arme, avancementAnnee...
    private SearchOperator operator;
    private String module;
    private String value;

    public String getField() { return field; }
    public void setField(String field) { this.field = field; }
    public SearchOperator getOperator() { return operator; }
    public void setOperator(SearchOperator operator) { this.operator = operator; }
    public String getModule() { return module; }
    public void setModule(String module) { this.module = module; }
    public String getValue() { return value; }
    public void setValue(String value) { this.value = value; }
}