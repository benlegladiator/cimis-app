package com.siadoc.backend.search;

import com.siadoc.backend.dto.search.SearchFilterDTO;
import com.siadoc.backend.dto.search.SearchOperator;
import jakarta.persistence.criteria.*;

import java.util.*;

public class GenericModuleItemHandler implements ModuleSearchHandler {

    private final Map<String, Class<?>> moduleItemMap;

    public GenericModuleItemHandler(Map<String, Class<?>> moduleItemMap) {
        this.moduleItemMap = moduleItemMap;
    }

    @Override
    public Predicate build(
            CriteriaBuilder cb,
            CriteriaQuery<?> query,
            Root<?> militaireRoot,
            SearchFilterDTO filter
    ) {

        Class<?> itemClass = moduleItemMap.get(filter.getModule());

        if (itemClass == null) {
            throw new RuntimeException("Module inconnu : " + filter.getModule());
        }

        Subquery<Long> sub = query.subquery(Long.class);

        Root<?> item = sub.from(itemClass);

        Join<?, ?> module = item.join("module");
        Join<?, ?> dossier = module.join("dossier");

        List<Predicate> predicates = new ArrayList<>();

        // lien militaire principal
        predicates.add(
                cb.equal(
                        dossier.get("militaire"),
                        militaireRoot
                )
        );

        Path<?> path = item.get(filter.getField());

        switch (filter.getOperator()) {

            case EQUAL ->
                    predicates.add(cb.equal(path, filter.getValue()));

            case LIKE ->
                    predicates.add(
                            cb.like(
                                    cb.lower(path.as(String.class)),
                                    "%" + filter.getValue().toLowerCase() + "%"
                            )
                    );

            case YEAR_EQUAL ->
                    predicates.add(
                            cb.equal(
                                    cb.function(
                                            "DATE_PART",
                                            Integer.class,
                                            cb.literal("year"),
                                            path
                                    ),
                                    Integer.parseInt(filter.getValue())
                            )
                    );
        }

        sub.select(cb.literal(1L))
                .where(predicates.toArray(new Predicate[0]));

        return cb.exists(sub);
    }
}