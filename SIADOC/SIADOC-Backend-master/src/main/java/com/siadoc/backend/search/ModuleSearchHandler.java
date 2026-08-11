package com.siadoc.backend.search;

import com.siadoc.backend.dto.search.SearchFilterDTO;
import jakarta.persistence.criteria.*;

public interface ModuleSearchHandler {

    Predicate build(
            CriteriaBuilder cb,
            CriteriaQuery<?> query,
            Root<?> militaireRoot,
            SearchFilterDTO filter
    );
}