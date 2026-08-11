package com.siadoc.backend.service;

import com.siadoc.backend.dto.search.*;
import com.siadoc.backend.model.Militaire;
import com.siadoc.backend.search.ModuleSearchDispatcher;
import jakarta.persistence.EntityManager;
import jakarta.persistence.criteria.*;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;

import java.util.*;

@Service
@RequiredArgsConstructor
public class RechercheService {

    private final EntityManager em;

    private final ModuleSearchDispatcher dispatcher =
            new ModuleSearchDispatcher();

    public List<Militaire> search(SearchRequestDTO request) {

        CriteriaBuilder cb = em.getCriteriaBuilder();
        CriteriaQuery<Militaire> query =
                cb.createQuery(Militaire.class);

        Root<Militaire> militaire = query.from(Militaire.class);

        List<Predicate> predicates = new ArrayList<>();

        for (SearchFilterDTO filter : request.getFilters()) {

            // ===== CHAMPS MILITAIRE =====
            if (filter.getModule() == null) {

                Path<?> path = militaire.get(filter.getField());

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
                }
            }

            // ===== MODULES =====
            else {
                predicates.add(
                        dispatcher.getHandler()
                                .build(cb, query, militaire, filter)
                );
            }
        }

        query.where(cb.and(predicates.toArray(new Predicate[0])));
        query.distinct(true);

        return em.createQuery(query).getResultList();
    }
}