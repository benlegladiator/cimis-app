// --- GESTION DE LA SOUMISSION DU FORMULAIRE ---
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du type de personnel (MILITAIRE/CIVIL)
    const typePersonnelSelect = document.getElementById('type_personnel');
    const gradeField = document.getElementById('grade');
    const matriculeMilitaireField = document.getElementById('matricule_militaire');
    const anneeDernierGalonField = document.getElementById('annee_dernier_galon');
    
    // Fonction pour gérer l'affichage des champs militaires
    function toggleMilitaryFields() {
        const isCivil = typePersonnelSelect && typePersonnelSelect.value === 'CIVIL';
        
        if (gradeField) {
            gradeField.disabled = isCivil;
            gradeField.required = !isCivil;
            gradeField.value = isCivil ? '' : gradeField.value;
            
            // Ajouter un style visuel pour indiquer le champ désactivé
            if (isCivil) {
                gradeField.style.opacity = '0.5';
                gradeField.style.backgroundColor = '#f5f5f5';
                gradeField.placeholder = 'Non applicable pour le personnel civil';
            } else {
                gradeField.style.opacity = '1';
                gradeField.style.backgroundColor = '';
                gradeField.placeholder = 'EX: CAPITAINE, COLONEL, LIEUTENANT...';
            }
        }
        
        if (matriculeMilitaireField) {
            matriculeMilitaireField.disabled = isCivil;
            matriculeMilitaireField.required = !isCivil;
            matriculeMilitaireField.value = isCivil ? '' : matriculeMilitaireField.value;
            
            // Ajouter un style visuel pour indiquer le champ désactivé
            if (isCivil) {
                matriculeMilitaireField.style.opacity = '0.5';
                matriculeMilitaireField.style.backgroundColor = '#f5f5f5';
                matriculeMilitaireField.placeholder = 'Non applicable pour le personnel civil';
            } else {
                matriculeMilitaireField.style.opacity = '1';
                matriculeMilitaireField.style.backgroundColor = '';
                matriculeMilitaireField.placeholder = 'EX: 23456 (Gendarmerie Nationale), T17/23456, A17/23456 (Terre/Air/Marine)';
            }
        }
        
        // Gérer le champ de catégorie pour le personnel civil
        const categorieCivilGroup = document.getElementById('categorie_civil_group');
        const categorieCivilField = document.getElementById('categorie_civil');
        
        if (categorieCivilGroup && categorieCivilField) {
            if (isCivil) {
                categorieCivilGroup.style.display = 'block';
                categorieCivilField.required = true;
                categorieCivilField.style.opacity = '1';
                categorieCivilField.style.backgroundColor = '';
            } else {
                categorieCivilGroup.style.display = 'none';
                categorieCivilField.required = false;
                categorieCivilField.value = '';
            }
        }
        
        if (anneeDernierGalonField) {
            anneeDernierGalonField.disabled = isCivil;
            anneeDernierGalonField.required = !isCivil;
            anneeDernierGalonField.value = isCivil ? '' : anneeDernierGalonField.value;
            
            // Ajouter un style visuel pour indiquer le champ désactivé
            if (isCivil) {
                anneeDernierGalonField.style.opacity = '0.5';
                anneeDernierGalonField.style.backgroundColor = '#f5f5f5';
                anneeDernierGalonField.placeholder = 'Non applicable pour le personnel civil';
            } else {
                anneeDernierGalonField.style.opacity = '1';
                anneeDernierGalonField.style.backgroundColor = '';
                anneeDernierGalonField.placeholder = 'EX: 2023';
            }
        }
        
        // Afficher un message informatif
        const messageDiv = document.getElementById('type-personnel-message');
        if (!messageDiv && typePersonnelSelect) {
            const newMessage = document.createElement('div');
            newMessage.id = 'type-personnel-message';
            newMessage.style.cssText = `
                margin-top: 10px;
                padding: 10px;
                border-radius: 5px;
                font-size: 0.9rem;
                transition: all 0.3s ease;
            `;
            
            if (isCivil) {
                newMessage.style.backgroundColor = '#e3f2fd';
                newMessage.style.color = '#1565c0';
                newMessage.style.border = '1px solid #90caf9';
                newMessage.innerHTML = '<i class="fa-solid fa-info-circle"></i> Les champs militaires (grade, matricule militaire, date de la dernière promotion au grade) ne sont pas applicables au personnel civil.';
            } else {
                newMessage.style.backgroundColor = '#f3e5f5';
                newMessage.style.color = '#7b1fa2';
                newMessage.style.border = '1px solid #ce93d8';
                newMessage.innerHTML = '<i class="fa-solid fa-shield-alt"></i> Veuillez remplir tous les champs militaires pour le personnel militaire.';
            }
            
            typePersonnelSelect.parentNode.appendChild(newMessage);
        } else if (messageDiv) {
            if (isCivil) {
                messageDiv.style.backgroundColor = '#e3f2fd';
                messageDiv.style.color = '#1565c0';
                messageDiv.style.border = '1px solid #90caf9';
                messageDiv.innerHTML = '<i class="fa-solid fa-info-circle"></i> Les champs militaires (grade, matricule militaire, date de la dernière promotion au grade) ne sont pas applicables au personnel civil.';
            } else {
                messageDiv.style.backgroundColor = '#f3e5f5';
                messageDiv.style.color = '#7b1fa2';
                messageDiv.style.border = '1px solid #ce93d8';
                messageDiv.innerHTML = '<i class="fa-solid fa-shield-alt"></i> Veuillez remplir tous les champs militaires pour le personnel militaire.';
            }
        }
    }
    
    // Ajouter l'écouteur d'événement pour le changement de type de personnel
    if (typePersonnelSelect) {
        typePersonnelSelect.addEventListener('change', toggleMilitaryFields);
        
        // Initialiser l'état des champs au chargement
        toggleMilitaryFields();
    }
    
    const enrollmentForm = document.getElementById('enrollmentForm');
    if (!enrollmentForm) {
        console.error('Formulaire d\'enrôlement non trouvé');
        return;
    }

    // Intercepter la soumission du formulaire
    enrollmentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validation côté client avant envoi
        if (!validateForm()) {
            return;
        }
        
        // Désactiver le bouton et afficher l'indicateur
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ENRÔLEMENT EN COURS... <span id="progress-text">(0%)</span>';
        
        // Simulation de progression
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            const progressText = document.getElementById('progress-text');
            if (progressText) {
                progressText.textContent = `(${Math.round(progress)}%)`;
            }
        }, 200);
        
        // Créer FormData pour l'envoi
        const formData = new FormData(this);
        
        // Debug: Vérifier les données envoyées
        console.log('FormData avant envoi:');
        for (let [key, value] of formData.entries()) {
            console.log(`${key}:`, value);
        }
        
        // Envoyer via AJAX au backend
        fetch('../backend/enrolement_traitement.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.json();
        })
        .then(data => {
            clearInterval(progressInterval);
            
            // Progression à 100%
            const progressText = document.getElementById('progress-text');
            if (progressText) {
                progressText.textContent = '(100%)';
            }
            
            if (data.success) {
                // Afficher la modal de succès avec les données du candidat
                showCandidatModal(data.candidat || data);
                
                // Réinitialiser le formulaire
                this.reset();
                
                // Réinitialiser la preview photo
                resetPhotoPreview();
                
                // Réinitialiser le matricule preview
                const matriculePreview = document.getElementById('matricule-preview');
                if (matriculePreview) {
                    matriculePreview.textContent = 'Génération en cours...';
                }
                
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            clearInterval(progressInterval);
            console.error('Erreur:', error);
            showNotification('Erreur lors de la soumission. Veuillez réessayer.', 'error');
        })
        .finally(() => {
            // Réactiver le bouton
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
});

// --- GESTION BASIQUE DE LA PHOTO ---
document.addEventListener('DOMContentLoaded', function() {
    const photoUpload = document.getElementById('photo-upload');
    
    if (photoUpload) {
        photoUpload.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                // Validation simple
                const allowedTypes = ["image/jpeg", "image/jpg", "image/png"];
                if (!allowedTypes.includes(file.type)) {
                    showNotification("Format invalide. Seules les images JPEG ou PNG sont autorisées.", 'error');
                    this.value = "";
                    resetPhotoPreview();
                    return;
                }
                
                const maxSize = 2 * 1024 * 1024; // 2 Mo
                if (file.size > maxSize) {
                    showNotification("La photo est trop volumineuse. Taille maximale: 2 Mo.", 'error');
                    this.value = "";
                    resetPhotoPreview();
                    return;
                }
                
                // Prévisualisation de la photo
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('photo-preview');
                    if (preview) {
                        preview.innerHTML = `
                            <img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 10px;">
                            <span style="position: absolute; bottom: 10px; right: 10px; background: rgba(0,255,0,0.8); color: black; padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;">
                                ✓ Photo OK
                            </span>
                        `;
                        preview.style.border = '3px solid #00ff00';
                    }
                };
                reader.readAsDataURL(file);
                
                console.log('Photo sélectionnée:', file.name, file.size, file.type);
            } else {
                resetPhotoPreview();
            }
        });
    }
});

// Fonction pour réinitialiser la prévisualisation de la photo
function resetPhotoPreview() {
    const preview = document.getElementById('photo-preview');
    if (preview) {
        preview.innerHTML = `
            <i class="fa-solid fa-camera" style="font-size: 3rem; color: var(--neon-green); opacity: 0.7;"></i>
            <span style="position: absolute; bottom: 10px; font-size: 0.8rem; color: var(--neon-green);">PHOTO ID</span>
        `;
        preview.style.border = '3px dashed var(--neon-green)';
    }
}

// --- MODAL CANDIDAT ---
function showCandidatModal(candidatData) {
    console.log('Données candidat reçues:', candidatData);
    
    // Remplir les champs de la modal avec les données du candidat
    const modal = document.getElementById('candidatModal');
    
    // Photo du personnel
    const modalPhoto = document.getElementById('modal-photo');
    console.log('Photo dans les données:', candidatData.photo);
    
    if (candidatData.photo && candidatData.photo !== '') {
        // Utiliser le chemin tel quel pour l'affichage
        let photoPath = candidatData.photo;
        console.log('Tentative de chargement photo:', photoPath);
        
        modalPhoto.src = photoPath;
        modalPhoto.style.display = 'block';
        
        // Gérer l'erreur de chargement sans utiliser d'avatar par défaut
        modalPhoto.onerror = function() {
            console.log('Erreur chargement photo, affichage d\'un placeholder');
            // Afficher un placeholder simple sans image
            this.style.display = 'none';
            const placeholder = document.createElement('div');
            placeholder.style.cssText = `
                width: 150px; 
                height: 180px; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                color: white; 
                font-size: 3rem; 
                border-radius: 10px;
                border: 3px solid var(--neon-green);
            `;
            placeholder.innerHTML = '<i class="fa-solid fa-user"></i>';
            this.parentNode.insertBefore(placeholder, this);
        };
        
        // Vérifier si l'image se charge correctement
        modalPhoto.onload = function() {
            console.log('Photo chargée avec succès');
        };
    } else {
        // Afficher un placeholder si pas de photo
        console.log('Pas de photo dans les données, affichage placeholder');
        modalPhoto.style.display = 'none';
        const placeholder = document.createElement('div');
        placeholder.style.cssText = `
            width: 150px; 
            height: 180px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            font-size: 3rem; 
            border-radius: 10px;
            border: 3px solid var(--neon-green);
        `;
        placeholder.innerHTML = '<i class="fa-solid fa-user"></i>';
        modalPhoto.parentNode.insertBefore(placeholder, modalPhoto);
    }
    
    // Afficher le QR code dans la modal
    const modalQR = document.getElementById('modal-qr');
    if (modalQR && candidatData.code_qr) {
        modalQR.innerHTML = `<img src="${candidatData.code_qr}" style="width: 100px; height: 100px; object-fit: contain; border: 1px solid #ddd; border-radius: 5px;">`;
    }
    
    document.getElementById('modal-matricule').textContent = candidatData.matricule || 'N/A';
    document.getElementById('modal-nom').textContent = candidatData.nom || '-';
    document.getElementById('modal-prenom').textContent = candidatData.prenom || '-';
    document.getElementById('modal-date-naissance').textContent = candidatData.date_naissance || '-';
    document.getElementById('modal-sexe').textContent = candidatData.sexe || '-';
    document.getElementById('modal-unite').textContent = candidatData.unite || '-';
    document.getElementById('modal-grade').textContent = candidatData.grade || '-';
    document.getElementById('modal-cni').textContent = candidatData.numero_cni || '-';
    
    // Afficher la modal
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Animation d'apparition
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
}

function closeCandidatModal() {
    const modal = document.getElementById('candidatModal');
    modal.classList.remove('show');
    
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }, 300);
}

function redirectToImpression() {
    // Récupérer le matricule depuis la modal
    const matricule = document.getElementById('modal-matricule').textContent;
    
    if (matricule && matricule !== 'N/A') {
        // Rediriger vers la page de visualisation de carte avec le matricule
        window.location.href = `visualiser_carte.php?matricule=${encodeURIComponent(matricule)}`;
    } else {
        // Si pas de matricule, rediriger vers la page de visualisation générale
        window.location.href = 'visualiser_carte.php';
    }
}

// --- VALIDATION CÔTÉ CLIENT ---
function validateForm() {
    let isValid = true;
    const errors = [];
    
    // Validation du nom
    const nomElement = document.getElementById('nom');
    if (nomElement) {
        const nom = nomElement.value.trim();
        if (nom.length < 2) {
            errors.push('Le nom doit contenir au moins 2 caractères');
            isValid = false;
        }
    } else {
        errors.push('Le champ nom est introuvable');
        isValid = false;
    }
    
    // Validation du prénom
    const prenomElement = document.getElementById('prenom');
    if (prenomElement) {
        const prenom = prenomElement.value.trim();
        if (prenom.length < 2) {
            errors.push('Le prénom doit contenir au moins 2 caractères');
            isValid = false;
        }
    } else {
        errors.push('Le champ prénom est introuvable');
        isValid = false;
    }
    
    // Validation de la date de naissance
    const dateNaissanceElement = document.getElementById('date_naissance');
    if (dateNaissanceElement) {
        const dateNaissance = dateNaissanceElement.value;
        if (!dateNaissance) {
            errors.push('La date de naissance est requise');
            isValid = false;
        } else {
            const birthDate = new Date(dateNaissance);
            const today = new Date();
            const age = Math.floor((today - birthDate) / (365.25 * 24 * 60 * 60 * 1000));
            
            if (age < 18) {
                errors.push('Le candidat doit avoir au moins 18 ans');
                isValid = false;
            }
        }
    } else {
        errors.push('Le champ date de naissance est introuvable');
        isValid = false;
    }
    
    // Validation du sexe
    const sexeElement = document.getElementById('sexe');
    if (sexeElement) {
        const sexe = sexeElement.value;
        if (!sexe) {
            errors.push('Le sexe doit être sélectionné');
            isValid = false;
        }
    } else {
        errors.push('Le champ sexe est introuvable');
        isValid = false;
    }
    
    // Validation du numéro CNI
    const numeroCniElement = document.getElementById('numero_cni');
    if (numeroCniElement) {
        const numeroCni = numeroCniElement.value.trim();
        const numeroCniClean = numeroCni.replace(/[^A-Z0-9]/g, '').toUpperCase(); // Garder lettres majuscules et chiffres
        
        if (!numeroCni || numeroCniClean.length < 9 || numeroCniClean.length > 20) {
            errors.push('Le numéro CNI doit contenir entre 9 et 20 caractères (chiffres et/ou lettres majuscules)');
            isValid = false;
        }
    } else {
        errors.push('Le champ numéro CNI est introuvable');
        isValid = false;
    }
    
    // Validation de la taille
    const tailleElement = document.getElementById('taille');
    if (tailleElement) {
        const taille = parseInt(tailleElement.value);
        if (isNaN(taille) || taille < 140 || taille > 220) {
            errors.push('La taille doit être comprise entre 140cm et 220cm');
            isValid = false;
        }
    } else {
        errors.push('Le champ taille est introuvable');
        isValid = false;
    }
    
    // Validation du poids
    const poidsElement = document.getElementById('poids');
    if (poidsElement) {
        const poids = parseInt(poidsElement.value);
        if (isNaN(poids) || poids < 45 || poids > 150) {
            errors.push('Le poids doit être compris entre 45kg et 150kg');
            isValid = false;
        }
    } else {
        errors.push('Le champ poids est introuvable');
        isValid = false;
    }
    
    // Validation du matricule militaire selon le corps d'armée
    const matriculeMilitaireElement = document.getElementById('matricule_militaire');
    const uniteElement = document.getElementById('unite');
    
    if (matriculeMilitaireElement && uniteElement) {
        const matriculeMilitaire = matriculeMilitaireElement.value.trim();
        const unite = uniteElement.value;
        
        // Le matricule militaire n'est requis que pour les unités militaires (pas pour CIVIL)
        if (unite !== 'CIVIL' && !matriculeMilitaire) {
            errors.push('Le matricule militaire est requis pour les unités militaires');
            isValid = false;
        }
        
        // Validation du format uniquement si un matricule est fourni
        if (matriculeMilitaire) {
            let formatRequis = '';
            let messageFormat = '';
            
            switch(unite) {
                case 'GENDARMERIE NATIONALE':
                    formatRequis = /^\d{4,6}$/;
                    messageFormat = 'Format: 23456 ou 1234567 (4 à 6 chiffres uniquement)';
                    break;
                case 'ARMÉE DE TERRE':
                    formatRequis = /^T\d{2,4}\/\d{4,6}$/;
                    messageFormat = 'Format: T17/23456 ou T2017/23456 (T + année sur 2-4 chiffres / 4-6 chiffres)';
                    break;
                case 'ARMÉE DE L\'AIR':
                    formatRequis = /^A\d{2,4}\/\d{4,6}$/;
                    messageFormat = 'Format: A17/23456 ou A2017/23456 (A + année sur 2-4 chiffres / 4-6 chiffres)';
                    break;
                case 'MARINE NATIONALE':
                    formatRequis = /^M\d{2,4}\/\d{4,6}$/;
                    messageFormat = 'Format: M17/23456 ou M2017/23456 (M + année sur 2-4 chiffres / 4-6 chiffres)';
                    break;
                default:
                    formatRequis = null;
            }
            
            if (formatRequis && !formatRequis.test(matriculeMilitaire)) {
                errors.push(`Format invalide pour ${unite}. ${messageFormat}`);
                isValid = false;
            }
            
            // Validation de la longueur minimale
            if (matriculeMilitaire.length < 4) {
                errors.push('Le matricule militaire doit contenir au moins 4 caractères');
                isValid = false;
            }
        }
    }
    
    // Validation de l'unité et du grade
    const gradeElement = document.getElementById('grade');
    
    if (uniteElement) {
        const unite = uniteElement.value;
        if (!unite) {
            errors.push('Veuillez sélectionner une unité');
            isValid = false;
        }
    } else {
        errors.push('Le champ unité est introuvable');
        isValid = false;
    }
    
    if (gradeElement) {
        const grade = gradeElement.value;
        if (!grade) {
            errors.push('Veuillez sélectionner un grade');
            isValid = false;
        }
    } else {
        errors.push('Le champ grade est introuvable');
        isValid = false;
    }
    
    // Validation de la photo (obligatoire pour tous)
    const photoUpload = document.getElementById('photo-upload');
    const photoData = document.getElementById('photo-data');
    const photoPreview = document.getElementById('photo-preview');
    
    // Vérifier si une photo a été capturée/importée (webcam ou fichier)
    let hasPhoto = false;
    
    // 1. Vérifier si une photo a été rognée et stockée dans photo-data
    if (photoData && photoData.value && photoData.value.trim() !== '') {
        hasPhoto = true;
    }
    // 2. Vérifier si une photo a été capturée via webcam (preview contient une image)
    else if (photoPreview && photoPreview.querySelector('img')) {
        hasPhoto = true;
    }
    // 3. Vérifier si un fichier a été sélectionné (fallback)
    else if (photoUpload && photoUpload.files && photoUpload.files[0]) {
        hasPhoto = true;
    }
    
    if (!hasPhoto) {
        errors.push('La photo d\'identité est obligatoire pour tous les candidats');
        isValid = false;
    }
    
    // Afficher les erreurs s'il y en a
    if (!isValid) {
        showNotification(errors.join('\\n'), 'error');
    }
    
    return isValid;
}

// --- GESTION DE LA RÉPONSE D'ENRÔLEMENT ---
function handleEnrollmentResponse(data, submitBtn, originalText) {
    if (data.success) {
        // Succès - afficher notification
        showNotification(data.message, 'success');
        
        // Rediriger vers la page de visualisation
        setTimeout(() => {
            window.location.href = data.redirect || 'visualiser_carte.php';
        }, 1500);
        
    } else {
        // Erreur - afficher le message
        showNotification(data.message, 'error');
        
        // Réactiver le bouton
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// --- MODAL DE SUCCÈS ---
function showSuccessModal(candidatData) {
    // Mettre à jour les informations dans la modal
    document.getElementById('modal-matricule').textContent = candidatData.matricule || 'N/A';
    document.getElementById('modal-nom').textContent = candidatData.nom || 'N/A';
    document.getElementById('modal-prenom').textContent = candidatData.prenom || 'N/A';
    document.getElementById('modal-date-naissance').textContent = candidatData.date_naissance || 'N/A';
    document.getElementById('modal-sexe').textContent = candidatData.sexe || 'N/A';
    document.getElementById('modal-unite').textContent = candidatData.unite || 'N/A';
    document.getElementById('modal-grade').textContent = candidatData.grade || 'N/A';
    document.getElementById('modal-cni').textContent = candidatData.numero_cni || 'N/A';
    
    // Afficher la modal
    const modal = document.getElementById('candidatModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

// --- FONCTIONS UTILITAIRES ---
function showNotification(message, type = 'info') {
    // Créer ou mettre à jour la notification
    let notification = document.getElementById('notification');
    
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'notification';
        notification.className = 'notification';
        document.body.appendChild(notification);
    }
    
    // Définir le contenu et le style
    notification.textContent = message;
    notification.className = `notification notification-${type}`;
    
    // Auto-suppression après 5 secondes
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

function closeCandidatModal() {
    const modal = document.getElementById('candidatModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// --- FONCTIONS DE NAVIGATION ---
function redirectToImpression() {
    // Récupérer le matricule depuis la modal
    const matricule = document.getElementById('modal-matricule').textContent;
    
    if (matricule && matricule !== 'N/A') {
        // Rediriger vers la page de visualisation de carte avec le matricule
        window.location.href = `visualiser_carte.php?matricule=${encodeURIComponent(matricule)}`;
    } else {
        // Si pas de matricule, rediriger vers la page de visualisation générale
        window.location.href = 'visualiser_carte.php';
    }
}

// --- GESTION DES GRADES PAR UNITÉ (RDG Cameroun - FORMAT UNDERSCORE) ---
const gradesParUnite = {
    'GENDARMERIE NATIONALE': [
        // OFFICIERS GENERAUX (4)
        'Général d\'Armée',
        'Général de Corps d\'Armée',
        'Général de Division',
        'Général de Brigade',
        
        // OFFICIERS SUPERIEURS (3)
        'Colonel',
        'Lieutenant-Colonel',
        'Chef d\'Escadron',
        
        // OFFICIERS SUBALTERNES (3)
        'Capitaine',
        'Lieutenant',
        'Sous-Lieutenant',
        
        // ASPIRANTS (1)
        'Aspirant',
        
        // SOUS OFFICIERS SUPERIEURS (3)
        'Adjudant-Chef Major',
        'Adjudant-Chef',
        'Adjudant',
        
        // SOUS OFFICIERS SUBALTERNES (3)
        'Maréchal des Logis-Chef',
        'Maréchal des Logis',
        'Gendarme Major',
        'Gendarme',
        'Élève-Gendarme'
    ],
    'ARMÉE DE TERRE': [
        // OFFICIERS GENERAUX (4)
        'Général d\'Armée',
        'Général de Corps d\'Armée',
        'Général de Division',
        'Général de Brigade',
        
        // OFFICIERS SUPERIEURS (3)
        'Colonel',
        'Lieutenant-Colonel',
        'Chef de Bataillon',
        
        // OFFICIERS SUBALTERNES (3)
        'Capitaine',
        'Lieutenant',
        'Sous-Lieutenant',
        
        // ASPIRANTS (1)
        'Aspirant',
        
        // SOUS OFFICIERS SUPERIEURS (3)
        'Adjudant-Chef Major',
        'Adjudant-Chef',
        'Adjudant',
        
        // SOUS OFFICIERS SUBALTERNES (4)
        'Sergent-Chef',
        'Sergent',
        'Caporal-Chef',
        'Caporal',
        
        // MILITAIRES DU RANG (2)
        'Soldat de 1E Classe',
        'Soldat de 2E Classe'
    ],
    'MARINE NATIONALE': [
        // OFFICIERS GENERAUX (4)
        'Amiral d\'Escadre',
        'Vice-Amiral d\'Escadre',
        'Vice-Amiral',
        'Contre-Amiral',
        
        // OFFICIERS SUPERIEURS (3)
        'Capitaine de Vaisseau',
        'Capitaine de Frégate',
        'Capitaine de Corvette',
        
        // OFFICIERS SUBALTERNES (3)
        'Lieutenant de Vaisseau',
        'Enseigne de Vaisseau de 1E Classe',
        'Enseigne de Vaisseau de 2E Classe',
        
        // ASPIRANTS ET ÉLÈVES OFFICIERS (1)
        'Aspirant',
        
        // OFFICIERS MARINIERS (SOUS-OFFICIERS) (5)
        'Maître Principal Major',
        'Maître Principal',
        'Premier Maître',
        'Maître',
        'Second Maître',
        
        // ÉQUIPAGE (MILITAIRES DU RANG) (4)
        'Quartier-Maître de 1E Classe',
        'Quartier-Maître de 2E Classe',
        'Matelot de 1E Classe',
        'Matelot de 2E Classe'
    ],
    'ARMÉE DE L\'AIR': [
        // OFFICIERS GENERAUX (4)
        'Général d\'Armée Aérienne',
        'Général de Corps Aérien',
        'Général de Division Aérienne',
        'Général de Brigade Aérienne',
        
        // OFFICIERS SUPERIEURS (3)
        'Colonel',
        'Lieutenant-Colonel',
        'Commandant',
        
        // OFFICIERS SUBALTERNES (3)
        'Capitaine',
        'Lieutenant',
        'Sous-Lieutenant',
        
        // ASPIRANTS (1)
        'Aspirant',
        
        // SOUS OFFICIERS SUPERIEURS (4)
        'Adjudant-Chef Major',
        'Adjudant-Chef',
        'Adjudant',
        'Sergent-Chef',
        
        // SOUS OFFICIERS SUBALTERNES (3)
        'Sergent',
        'Caporal-Chef',
        'Caporal',
        
        // MILITAIRES DU RANG (2)
        'Soldat de 1E Classe',
        'Soldat de 2E Classe'
    ],
    'CIVIL': [
        'AGENT', 'AGENT PRINCIPAL',
        'CHEF DE SERVICE', 'DIRECTEUR ADJOINT',
        'DIRECTEUR', 'DIRECTEUR GENERAL',
        // Métiers populaires collaborant avec l'armée
        'ENSEIGNANT', 'AVOCAT', 'MEDÉCIN',
        'ENSEIGNANT', 'AVOCAT', 'MÉDECIN',
        'INFIRMIER', 'INGÉNIEUR', 'MÉCANICIEN',
        'TECHNICIEN', 'INFORMATICIEN',
        'COMPTABLE', 'CHERCHEUR',
        'ENTREPRENEUR', 'ARTISAN', 'COMMERÇANT',
        'CHEF TRADITIONNEL',
        // Catégorie ouverte
        'AUTRE'
    ]
};

function updateCivilCategory() {
    const unite = document.getElementById('unite').value;
    const categorieCivilGroup = document.getElementById('categorie_civil_group');
    const categorieCivilField = document.getElementById('categorie_civil');
    const gradeField = document.getElementById('grade');
    
    // Afficher ou cacher le champ catégorie selon l'unité
    if (unite === 'CIVIL') {
        if (categorieCivilGroup) {
            categorieCivilGroup.style.display = 'block';
        }
        if (categorieCivilField) {
            categorieCivilField.required = true;
        }
        // Désactiver le grade pour les civils jusqu'à ce qu'une catégorie soit sélectionnée
        if (gradeField) {
            gradeField.disabled = true;
            gradeField.required = false;
            gradeField.innerHTML = '<option value="">Sélectionner d\'abord la catégorie... / Select category first...</option>';
        }
    } else {
        if (categorieCivilGroup) {
            categorieCivilGroup.style.display = 'none';
        }
        if (categorieCivilField) {
            categorieCivilField.required = false;
            categorieCivilField.value = '';
        }
        // Activer le grade pour les militaires
        if (gradeField) {
            gradeField.disabled = false;
            gradeField.required = true;
        }
        // Mettre à jour les grades pour les militaires
        updateGrades();
    }
}

function updateGrades() {
    const unite = document.getElementById('unite').value;
    const categorieCivil = document.getElementById('categorie_civil') ? document.getElementById('categorie_civil').value : null;
    const gradeSelect = document.getElementById('grade');
    
    // Vider le select
    gradeSelect.innerHTML = '<option value="">Sélectionner...</option>';
    
    // Cas spécial pour le personnel civil
    if (unite === 'CIVIL') {
        if (!categorieCivil) {
            gradeSelect.innerHTML = '<option value="">Sélectionner d\'abord la catégorie... / Select category first...</option>';
            gradeSelect.disabled = true;
            gradeSelect.required = false;
            return;
        }
        
        // Activer le champ grade
        gradeSelect.disabled = false;
        gradeSelect.required = true;
        
        // Grades selon la catégorie civile
        const gradesParCategorie = {
            'FONCTIONNAIRE': ['AGENT', 'CADRE', 'INGÉNIEUR', 'TECHNICIEN', 'MÉCANICIEN', 'CHAUFFEUR', 'INFORMATICIEN', 'CUISINIER', 'COUTURIER', 'COMPTABLE', 'CONSULTANT', 'PSYCHOLOGUE', 'MÉDECIN', 'OPÉRATEUR', 'CONSEILLER', 'COACH', 'EXPERT', 'ENSEIGNANT', 'ASSISTANT', 'COIFFEUR', 'JURISTE', 'RÉGISSEUR', 'ANALYSTE', 'CONTRÔLEUR', 'AUTRE'],
            'CADRE_CONTRACTUEL': ['AGENT', 'CADRE', 'INGÉNIEUR', 'TECHNICIEN', 'MÉCANICIEN', 'CHAUFFEUR', 'INFORMATICIEN', 'CUISINIER', 'COUTURIER', 'COMPTABLE', 'CONSULTANT', 'PSYCHOLOGUE', 'MÉDECIN', 'OPÉRATEUR', 'CONSEILLER', 'COACH', 'EXPERT', 'ENSEIGNANT', 'ASSISTANT', 'COIFFEUR', 'JURISTE', 'RÉGISSEUR', 'ANALYSTE', 'CONTRÔLEUR', 'AUTRE'],
            'AGENT_CONTRACTUEL': ['AGENT', 'CADRE', 'INGÉNIEUR', 'TECHNICIEN', 'MÉCANICIEN', 'CHAUFFEUR', 'INFORMATICIEN', 'CUISINIER', 'COUTURIER', 'COMPTABLE', 'CONSULTANT', 'PSYCHOLOGUE', 'MÉDECIN', 'OPÉRATEUR', 'CONSEILLER', 'COACH', 'EXPERT', 'ENSEIGNANT', 'ASSISTANT', 'COIFFEUR', 'JURISTE', 'RÉGISSEUR', 'ANALYSTE', 'CONTRÔLEUR', 'AUTRE'],
            'AGENT_DECISION': ['AGENT', 'CADRE', 'INGÉNIEUR', 'TECHNICIEN', 'MÉCANICIEN', 'CHAUFFEUR', 'INFORMATICIEN', 'CUISINIER', 'COUTURIER', 'COMPTABLE', 'CONSULTANT', 'PSYCHOLOGUE', 'MÉDECIN', 'OPÉRATEUR', 'CONSEILLER', 'COACH', 'EXPERT', 'ENSEIGNANT', 'ASSISTANT', 'COIFFEUR', 'JURISTE', 'RÉGISSEUR', 'ANALYSTE', 'CONTRÔLEUR', 'AUTRE']
        };
        
        if (gradesParCategorie[categorieCivil]) {
            gradesParCategorie[categorieCivil].forEach(grade => {
                const option = document.createElement('option');
                option.value = grade;
                option.textContent = grade;
                gradeSelect.appendChild(option);
            });
        }
        return;
    }
    
    // Cas normal pour le personnel militaire
    if (unite && gradesParUnite[unite]) {
        gradesParUnite[unite].forEach(grade => {
            const option = document.createElement('option');
            option.value = grade;
            option.textContent = grade;
            gradeSelect.appendChild(option);
        });
    }
}
