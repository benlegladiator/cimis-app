package com.siadoc.backend.dto.search;

import lombok.Data;
import java.util.List;

@Data
public class SearchRequestDTO {

    private List<SearchFilterDTO> filters;

    public List<SearchFilterDTO> getFilters() { return filters; }
    public void setFilters(List<SearchFilterDTO> filters) { this.filters = filters; }
}