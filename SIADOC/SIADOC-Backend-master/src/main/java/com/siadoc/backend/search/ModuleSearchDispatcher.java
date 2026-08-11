package com.siadoc.backend.search;

import com.siadoc.backend.model.*;

import java.util.HashMap;
import java.util.Map;

public class ModuleSearchDispatcher {

    private final ModuleSearchHandler handler;

    public ModuleSearchDispatcher() {

        Map<String, Class<?>> moduleItemMap = new HashMap<>();

        moduleItemMap.put("campagne", CampagneMilitaireItem.class);
        moduleItemMap.put("recompense", RecompenseItem.class);
        moduleItemMap.put("punition", PunitionItem.class);
        moduleItemMap.put("stage", StageItem.class);
        moduleItemMap.put("avancement", Avancement.class);
        moduleItemMap.put("carriere", Carriere.class);
        moduleItemMap.put("diplome", DiplomeItem.class);
        moduleItemMap.put("identification", Identification.class);
        moduleItemMap.put("mutation", MutationItem.class);

        // 👉 ajoute simplement tes autres modules ici

        handler = new GenericModuleItemHandler(moduleItemMap);
    }

    public ModuleSearchHandler getHandler() {
        return handler;
    }
}