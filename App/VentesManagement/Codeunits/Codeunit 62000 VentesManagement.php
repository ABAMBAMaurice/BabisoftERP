<?php

/**
 * Moteur central du cycle de vente.
 *
 * Flux BC-style :
 *   Devis ──(convertir)──→ Commande ──(libérer)──→
 *   ──(validerLivraison)──→ LivraisonEnTete + EcritureStock ──(validerFacture)──→
 *   ──→ FactureValideeEnTete + EcritureClient + PièceComptable
 *
 *   Avoir ──(validerAvoir)──→ FactureValideeEnTete (Avoir) + EcritureClient + Stock inversé
 */
class VentesManagement {

    // ─────────────────────────────────────────────
    //  DEVIS → COMMANDE
    // ─────────────────────────────────────────────

    /**
     * Convertit un devis en commande de vente.
     * Le devis est conservé pour l'historique (statut Converti).
     */
    public static function convertirDevisEnCommande(string $devisNo): string {
        $devis = new EnTeteVente();
        if (!$devis->get($devisNo))
            Error("Devis '".$devisNo."' introuvable.");
        if ($devis->Type_Document->_value !== TypeDocumentVente::Devis->value)
            Error("'".$devisNo."' n'est pas un devis.");
        if ($devis->Statut->_value !== StatutDocumentVente::Ouvert->value)
            Error("Le devis '".$devisNo."' n'est plus en statut Ouvert.");

        $commandeNo = self::_genererNumero(TypeDocumentVente::Commande);

        // Dupliquer l'en-tête
        $cmd = new EnTeteVente();
        $cmd->Validate('No_',                   $commandeNo);
        $cmd->Validate('Type_Document',          TypeDocumentVente::Commande->value);
        $cmd->Validate('Client_No',              $devis->Client_No->_value);
        $cmd->Validate('Date_Document',          date('Y-m-d'));
        $cmd->Validate('Date_Livraison_Prevue',  $devis->Date_Livraison_Prevue->_value);
        $cmd->Validate('Conditions_Paiement_Code',$devis->Conditions_Paiement_Code->_value);
        $cmd->Validate('Mode_Reglement_Code',    $devis->Mode_Reglement_Code->_value);
        $cmd->Validate('Remise_Globale_Pct',     $devis->Remise_Globale_Pct->_value);
        $cmd->Validate('Entrepot_Code',          $devis->Entrepot_Code->_value);
        $cmd->Validate('No_Reference_Client',    $devis->No_Reference_Client->_value);
        $cmd->Validate('Responsable_Code',       $devis->Responsable_Code->_value);
        $cmd->Validate('Notes',                  $devis->Notes->_value);
        $cmd->Validate('Devis_No',               $devisNo);
        $cmd->Validate('Tenant_code',            $devis->Tenant_code->_value);
        $cmd->Insert(false); // onInsert gère le reste

        // Dupliquer les lignes
        $lignes = new LigneVente();
        $lignes->setRange('Document_No', $devisNo);
        if ($lignes->FindAll()) {
            foreach ($lignes->recordSet as $l) {
                $newL = new LigneVente();
                $newL->Validate('Document_Type',     TypeDocumentVente::Commande->value);
                $newL->Validate('Document_No',       $commandeNo);
                $newL->Validate('Ligne_No',          $l->Ligne_No->_value);
                $newL->Validate('Type_Ligne',        $l->Type_Ligne->_value);
                $newL->Validate('No_',               $l->No_->_value);
                $newL->Validate('Description',       $l->Description->_value);
                $newL->Validate('UM_Code',           $l->UM_Code->_value);
                $newL->Validate('Quantite',          $l->Quantite->_value);
                $newL->Validate('Prix_Unitaire_HT',  $l->Prix_Unitaire_HT->_value);
                $newL->Validate('Remise_Pct',        $l->Remise_Pct->_value);
                $newL->Validate('Type_TVA',          $l->Type_TVA->_value);
                $newL->Validate('Taux_TVA_Pct',      $l->Taux_TVA_Pct->_value);
                $newL->Validate('Entrepot_Code',     $l->Entrepot_Code->_value);
                $newL->Validate('Compte_Vente_No',   $l->Compte_Vente_No->_value);
                $newL->Validate('Tenant_code',       $l->Tenant_code->_value);
                $newL->Insert(true); // onInsert recalcule les montants
            }
        }

        // Archiver le devis
        $devis->Validate('Statut', 'Converti');
        $devis->Validate('Commande_No', $commandeNo);
        $devis->Modify(false);

        return $commandeNo;
    }

    // ─────────────────────────────────────────────
    //  LIBÉRER UN DOCUMENT
    // ─────────────────────────────────────────────

    /**
     * Libère un document pour traitement (livraison / facturation).
     * Vérifie les lignes, le crédit client, les stocks.
     */
    public static function liberer(string $documentNo): void {
        $doc = new EnTeteVente();
        if (!$doc->get($documentNo))
            Error("Document '".$documentNo."' introuvable.");
        if (!StatutDocumentVente::from($doc->Statut->_value)->peutEtreLibere())
            Error("Le document '".$documentNo."' ne peut pas être libéré (statut actuel : ".$doc->Statut->_value.").");

        $doc->CalcFields('Nb_Lignes', 'Montant_TTC');
        if ((int)$doc->Nb_Lignes->_value === 0)
            Error("Impossible de libérer un document sans lignes.");
        if ((float)$doc->Montant_TTC->_value <= 0)
            Error("Le montant total du document est nul.");

        // Vérification des stocks pour les commandes (articles stockés)
        if ($doc->Type_Document->_value === TypeDocumentVente::Commande->value) {
            $lignes = new LigneVente();
            $lignes->setRange('Document_No', $documentNo);
            $lignes->setRange('Type_Ligne',  'Article');
            if ($lignes->FindAll()) {
                foreach ($lignes->recordSet as $l) {
                    $stock = StockManagement::getStock($l->No_->_value, $l->Entrepot_Code->_value);
                    if ($stock < (float)$l->Qte_A_Livrer->_value)
                        Error("Stock insuffisant pour l'article '".$l->No_->_value."'"
                            ." (dispo : ".$stock.", à livrer : ".$l->Qte_A_Livrer->_value.").");
                }
            }
        }

        $doc->Validate('Statut', StatutDocumentVente::Libere->value);
        $doc->Modify();
    }

    // ─────────────────────────────────────────────
    //  VALIDER UNE LIVRAISON (Commande uniquement)
    // ─────────────────────────────────────────────

    /**
     * Valide l'expédition des articles d'une commande libérée.
     * Pour chaque ligne avec Qte_A_Livrer > 0 :
     *  1. Crée une EcritureStock (sortie)
     *  2. Met à jour Qte_Livree + Qte_A_Livrer sur la ligne
     *  3. Crée LivraisonEnTete + LivraisonLigne
     *  4. Met à jour le statut de la commande
     */
    public static function validerLivraison(string $commandeNo): string {
        $cmd = new EnTeteVente();
        if (!$cmd->get($commandeNo))
            Error("Commande '".$commandeNo."' introuvable.");
        if ($cmd->Type_Document->_value !== TypeDocumentVente::Commande->value)
            Error("'".$commandeNo."' n'est pas une commande.");
        if (!in_array($cmd->Statut->_value, [
            StatutDocumentVente::Libere->value,
            StatutDocumentVente::Partiellement_Livre->value
        ])) Error("La commande '".$commandeNo."' doit être libérée pour être livrée.");

        $lignes = new LigneVente();
        $lignes->setRange('Document_No', $commandeNo);
        $lignes->setRange('Type_Ligne',  'Article');
        if (!$lignes->FindAll()) Error("Aucune ligne article à livrer.");

        $aQqchoseLivrer = false;
        foreach ($lignes->recordSet as $l) {
            if ((float)$l->Qte_A_Livrer->_value > 0) { $aQqchoseLivrer = true; break; }
        }
        if (!$aQqchoseLivrer)
            Error("Aucune quantité à livrer (Qte_A_Livrer = 0 sur toutes les lignes).");

        $livraisonNo = self::_genererNumeroDirect('LV');
        $dateLivraison = date('Y-m-d');

        // En-tête de livraison
        $livraison = new LivraisonEnTete();
        $livraison->Validate('No_',               $livraisonNo);
        $livraison->Validate('Commande_No',       $commandeNo);
        $livraison->Validate('Client_No',         $cmd->Client_No->_value);
        $livraison->Validate('Nom_Client',        $cmd->Nom_Client->_value);
        $livraison->Validate('Date_Livraison',    $dateLivraison);
        $livraison->Validate('Entrepot_Code',     $cmd->Entrepot_Code->_value);
        $livraison->Validate('Adresse_Livraison', $cmd->Adresse_Livraison->_value);
        $livraison->Validate('Responsable_Code',  $cmd->Responsable_Code->_value);
        $livraison->Validate('No_Reference_Client',$cmd->No_Reference_Client->_value);
        $livraison->Validate('Tenant_code',       $cmd->Tenant_code->_value);
        $livraison->Insert(false);

        $ligneNo         = 1;
        $toutesLivrees   = true;

        foreach ($lignes->recordSet as $l) {
            $qteLivrer = (float)$l->Qte_A_Livrer->_value;
            if ($qteLivrer <= 0) continue;

            // Écriture de stock (sortie Vente)
            $docProvisoire = new MouvementStockProvisoire();
            $docProvisoire->Validate('Document_Type',   'Vente');
            $docProvisoire->Validate('Document_No',     'SHIP-'.$livraisonNo);
            $docProvisoire->Validate('Ligne_No',        $ligneNo);
            $docProvisoire->Validate('Date_Mouvement',  $dateLivraison);
            $docProvisoire->Validate('Article_Code',    $l->No_->_value);
            $docProvisoire->Validate('Type_Mouvement',  TypeMouvement::Vente->value);
            $docProvisoire->Validate('Entrepot_Code',   $l->Entrepot_Code->_value);
            $docProvisoire->Validate('Emplacement_Code',$l->Emplacement_Code->_value);
            $docProvisoire->Validate('Quantite',        $qteLivrer);
            $docProvisoire->Validate('No_Lot',          $l->No_Lot->_value);
            $docProvisoire->Validate('No_Serie',        $l->No_Serie->_value);
            $docProvisoire->Validate('Tenant_code',     $l->Tenant_code->_value);
            $docProvisoire->Insert(false);

            // Ligne de livraison archivée
            $lligne = new LivraisonLigne();
            $lligne->Validate('Livraison_No',      $livraisonNo);
            $lligne->Validate('Ligne_No',          $ligneNo++);
            $lligne->Validate('Commande_No',       $commandeNo);
            $lligne->Validate('Commande_Ligne_No', $l->Ligne_No->_value);
            $lligne->Validate('Article_Code',      $l->No_->_value);
            $lligne->Validate('Description',       $l->Description->_value);
            $lligne->Validate('UM_Code',           $l->UM_Code->_value);
            $lligne->Validate('Quantite_Livree',   $qteLivrer);
            $lligne->Validate('Entrepot_Code',     $l->Entrepot_Code->_value);
            $lligne->Validate('Prix_Unitaire_HT',  $l->Prix_Unitaire_HT->_value);
            $lligne->Validate('No_Lot',            $l->No_Lot->_value);
            $lligne->Validate('Tenant_code',       $l->Tenant_code->_value);
            $lligne->Insert(false);

            // Mettre à jour la ligne de commande
            $nouvelleQteLivree = (float)$l->Qte_Livree->_value + $qteLivrer;
            $l->Validate('Qte_Livree',     $nouvelleQteLivree);
            $l->Validate('Qte_A_Livrer',   max(0, (float)$l->Quantite->_value - $nouvelleQteLivree));
            $l->Validate('Qte_A_Facturer', $qteLivrer); // à facturer = ce qui vient d'être livré
            $l->Modify(false);

            if ($nouvelleQteLivree < (float)$l->Quantite->_value)
                $toutesLivrees = false;
        }

        // Valider les mouvements stock
        StockManagement::validerMouvement('Vente', 'SHIP-'.$livraisonNo);

        // Mettre à jour le statut de la commande
        $cmd->Validate('Statut', $toutesLivrees
            ? StatutDocumentVente::Livre->value
            : StatutDocumentVente::Partiellement_Livre->value);
        $cmd->Validate('Date_Livraison_Prevue', $dateLivraison);
        $cmd->Modify(false);

        return $livraisonNo;
    }

    // ─────────────────────────────────────────────
    //  VALIDER UNE FACTURE
    // ─────────────────────────────────────────────

    /**
     * Valide la facturation d'un document (Commande livrée ou Facture directe).
     * Génère : FactureValideeEnTete + FactureValideeLigne + EcritureClient + PièceComptable
     */
    public static function validerFacture(string $documentNo): string {
        $doc = new EnTeteVente();
        if (!$doc->get($documentNo))
            Error("Document '".$documentNo."' introuvable.");

        $typeDoc = TypeDocumentVente::from($doc->Type_Document->_value);
        if (!$typeDoc->estFacturable())
            Error("Un devis ne peut pas être directement facturé. Convertissez-le en commande.");

        if ($typeDoc === TypeDocumentVente::Commande
            && !in_array($doc->Statut->_value, [
                StatutDocumentVente::Livre->value,
                StatutDocumentVente::Partiellement_Livre->value,
                StatutDocumentVente::Libere->value
            ])) Error("La commande doit être au moins libérée pour être facturée.");

        $doc->CalcFields('Montant_HT', 'Montant_TVA', 'Montant_TTC', 'Montant_Remise');

        $factureNo = self::_genererNumeroDirect('FV');
        $today     = date('Y-m-d');

        // Calculer l'échéance
        $echeance = $doc->Date_Echeance->_value ?: $today;
        if (!empty($doc->Conditions_Paiement_Code->_value)) {
            $cond = new ConditionsPaiement();
            if ($cond->get($doc->Conditions_Paiement_Code->_value))
                $echeance = $cond->calculerEcheance($today);
        }

        // Créer la facture validée
        $fv = new FactureValideeEnTete();
        $fv->Validate('No_',                    $factureNo);
        $fv->Validate('Type_Document',          TypeDocumentVente::Facture->value);
        $fv->Validate('Client_No',              $doc->Client_No->_value);
        $fv->Validate('Nom_Client',             $doc->Nom_Client->_value);
        $fv->Validate('NIF_Client',             $doc->NIF_Client->_value);
        $fv->Validate('Date_Document',          $today);
        $fv->Validate('Date_Echeance',          $echeance);
        $fv->Validate('No_Reference_Client',    $doc->No_Reference_Client->_value);
        $fv->Validate('Commande_No',            $typeDoc === TypeDocumentVente::Commande ? $documentNo : '');
        $fv->Validate('Conditions_Paiement_Code',$doc->Conditions_Paiement_Code->_value);
        $fv->Validate('Mode_Reglement_Code',    $doc->Mode_Reglement_Code->_value);
        $fv->Validate('Montant_HT',             (float)$doc->Montant_HT->_value);
        $fv->Validate('Montant_Remise',         (float)$doc->Montant_Remise->_value);
        $fv->Validate('Montant_TVA',            (float)$doc->Montant_TVA->_value);
        $fv->Validate('Montant_TTC',            (float)$doc->Montant_TTC->_value);
        $fv->Validate('Montant_Restant',        (float)$doc->Montant_TTC->_value);
        $fv->Validate('Responsable_Code',       $doc->Responsable_Code->_value);
        $fv->Validate('Tenant_code',            $doc->Tenant_code->_value);
        $fv->Insert(false);

        // Dupliquer les lignes dans FactureValideeLigne
        $lignes = new LigneVente();
        $lignes->setRange('Document_No', $documentNo);
        if ($lignes->FindAll()) {
            $ligneNo = 1;
            foreach ($lignes->recordSet as $l) {
                $fl = new FactureValideeLigne();
                $fl->Validate('Facture_No',        $factureNo);
                $fl->Validate('Ligne_No',          $ligneNo++);
                $fl->Validate('Type_Ligne',        $l->Type_Ligne->_value);
                $fl->Validate('No_',               $l->No_->_value);
                $fl->Validate('Description',       $l->Description->_value);
                $fl->Validate('UM_Code',           $l->UM_Code->_value);
                $fl->Validate('Quantite',          $l->Qte_A_Facturer->_value ?: $l->Quantite->_value);
                $fl->Validate('Prix_Unitaire_HT',  $l->Prix_Unitaire_HT->_value);
                $fl->Validate('Remise_Pct',        $l->Remise_Pct->_value);
                $fl->Validate('Montant_Remise',    $l->Montant_Remise->_value);
                $fl->Validate('Montant_Ligne_HT',  $l->Montant_Ligne_HT->_value);
                $fl->Validate('Taux_TVA_Pct',      $l->Taux_TVA_Pct->_value);
                $fl->Validate('Type_TVA',          $l->Type_TVA->_value);
                $fl->Validate('Montant_TVA',       $l->Montant_TVA->_value);
                $fl->Validate('Montant_Ligne_TTC', $l->Montant_Ligne_TTC->_value);
                $fl->Validate('Compte_Vente_No',   $l->Compte_Vente_No->_value);
                $fl->Validate('Tenant_code',       $l->Tenant_code->_value);
                $fl->Insert(false);

                // Mettre à jour Qte_Facturee
                $l->Validate('Qte_Facturee', (float)$l->Qte_Facturee->_value + (float)($l->Qte_A_Facturer->_value ?: $l->Quantite->_value));
                $l->Validate('Qte_A_Facturer', 0);
                $l->Modify(false);
            }
        }

        // Créer l'écriture client (grand livre client)
        $ecClient = self::_creerEcritureClient(
            $doc->Client_No->_value,
            $doc->Nom_Client->_value,
            TypeEcritureClient::Facture,
            $factureNo,
            "Facture ".$factureNo." - ".$doc->Nom_Client->_value,
            (float)$doc->Montant_TTC->_value,
            $today,
            $echeance,
            $doc->Mode_Reglement_Code->_value,
            $doc->Tenant_code->_value
        );

        // Génération de la pièce comptable
        $pieceNo = self::_genererPieceComptable($doc, $fv, $factureNo, $ecClient);

        $fv->Validate('Piece_Compta_No', $pieceNo);
        $fv->Modify(false);

        // Mettre à jour le statut du document source
        $doc->Validate('Statut', StatutDocumentVente::Facture->value);
        $doc->Modify(false);

        return $factureNo;
    }

    // ─────────────────────────────────────────────
    //  VALIDER UN AVOIR
    // ─────────────────────────────────────────────

    /**
     * Valide un avoir de vente.
     * Génère : FactureValideeEnTete (Avoir) + EcritureClient crédit + EcritureStock retour.
     */
    public static function validerAvoir(string $avoirNo): string {
        $doc = new EnTeteVente();
        if (!$doc->get($avoirNo))
            Error("Avoir '".$avoirNo."' introuvable.");
        if ($doc->Type_Document->_value !== TypeDocumentVente::Avoir->value)
            Error("'".$avoirNo."' n'est pas un avoir.");

        $doc->CalcFields('Montant_TTC');
        $today    = date('Y-m-d');
        $avoirValNo = self::_genererNumeroDirect('AV-V');

        // Créer l'avoir validé
        $av = new FactureValideeEnTete();
        $av->Validate('No_',               $avoirValNo);
        $av->Validate('Type_Document',     TypeDocumentVente::Avoir->value);
        $av->Validate('Client_No',         $doc->Client_No->_value);
        $av->Validate('Nom_Client',        $doc->Nom_Client->_value);
        $av->Validate('NIF_Client',        $doc->NIF_Client->_value);
        $av->Validate('Date_Document',     $today);
        $av->Validate('Date_Echeance',     $today);
        $av->Validate('Facture_Origine_No',$doc->Facture_Origine_No->_value);
        $av->Validate('Montant_HT',        (float)$doc->Montant_HT->_value);
        $av->Validate('Montant_TVA',       (float)$doc->Montant_TVA->_value);
        $av->Validate('Montant_TTC',       (float)$doc->Montant_TTC->_value);
        $av->Validate('Montant_Restant',   (float)$doc->Montant_TTC->_value);
        $av->Validate('Tenant_code',       $doc->Tenant_code->_value);
        $av->Insert(false);

        // Écriture client négative (crédit)
        self::_creerEcritureClient(
            $doc->Client_No->_value,
            $doc->Nom_Client->_value,
            TypeEcritureClient::Avoir,
            $avoirValNo,
            "Avoir ".$avoirValNo,
            -(float)$doc->Montant_TTC->_value,
            $today, $today,
            $doc->Mode_Reglement_Code->_value,
            $doc->Tenant_code->_value
        );

        // Retour en stock des articles de l'avoir
        $lignes = new LigneVente();
        $lignes->setRange('Document_No', $avoirNo);
        $lignes->setRange('Type_Ligne',  'Article');
        if ($lignes->FindAll()) {
            $mvtNo = 1;
            foreach ($lignes->recordSet as $l) {
                $retour = new MouvementStockProvisoire();
                $retour->Validate('Document_Type',  'Avoir');
                $retour->Validate('Document_No',    'AV-'.$avoirValNo);
                $retour->Validate('Ligne_No',       $mvtNo++);
                $retour->Validate('Date_Mouvement', $today);
                $retour->Validate('Article_Code',   $l->No_->_value);
                $retour->Validate('Type_Mouvement', TypeMouvement::Retour_Client->value);
                $retour->Validate('Entrepot_Code',  $l->Entrepot_Code->_value);
                $retour->Validate('Quantite',       (float)$l->Quantite->_value);
                $retour->Validate('No_Lot',         $l->No_Lot->_value);
                $retour->Validate('Tenant_code',    $l->Tenant_code->_value);
                $retour->Insert(false);
            }
            StockManagement::validerMouvement('Avoir', 'AV-'.$avoirValNo);
        }

        $doc->Validate('Statut', StatutDocumentVente::Facture->value); // "traité"
        $doc->Modify(false);

        return $avoirValNo;
    }

    // ─────────────────────────────────────────────
    //  ANNULER UN DOCUMENT
    // ─────────────────────────────────────────────

    public static function annuler(string $documentNo, string $motif = ''): void {
        $doc = new EnTeteVente();
        if (!$doc->get($documentNo))
            Error("Document '".$documentNo."' introuvable.");
        if (!StatutDocumentVente::from($doc->Statut->_value)->estModifiable())
            Error("Le document '".$documentNo."' ne peut plus être annulé (statut : ".$doc->Statut->_value.")."
                . " Créez un avoir.");
        $doc->Validate('Statut', StatutDocumentVente::Annule->value);
        $doc->Validate('Notes',  ($doc->Notes->_value ? $doc->Notes->_value."\n" : '')."Annulé : ".$motif);
        $doc->Modify();
    }

    // ─────────────────────────────────────────────
    //  CRÉER UN AVOIR DEPUIS UNE FACTURE VALIDÉE
    // ─────────────────────────────────────────────

    public static function creerAvoirDepuisFacture(string $factureValideeNo): string {
        $fv = new FactureValideeEnTete();
        if (!$fv->get($factureValideeNo))
            Error("Facture validée '".$factureValideeNo."' introuvable.");
        if ($fv->Type_Document->_value === TypeDocumentVente::Avoir->value)
            Error("On ne peut pas créer un avoir d'un avoir.");

        $avoirNo = self::_genererNumero(TypeDocumentVente::Avoir);

        $av = new EnTeteVente();
        $av->Validate('No_',              $avoirNo);
        $av->Validate('Type_Document',    TypeDocumentVente::Avoir->value);
        $av->Validate('Client_No',        $fv->Client_No->_value);
        $av->Validate('Date_Document',    date('Y-m-d'));
        $av->Validate('Facture_Origine_No',$factureValideeNo);
        $av->Validate('Conditions_Paiement_Code',$fv->Conditions_Paiement_Code->_value);
        $av->Validate('Mode_Reglement_Code',$fv->Mode_Reglement_Code->_value);
        $av->Validate('Tenant_code',      $fv->Tenant_code->_value);
        $av->Insert(false);

        // Copier les lignes de la facture validée
        $lignes = new FactureValideeLigne();
        $lignes->setRange('Facture_No', $factureValideeNo);
        if ($lignes->FindAll()) {
            foreach ($lignes->recordSet as $l) {
                $al = new LigneVente();
                $al->Validate('Document_Type',     TypeDocumentVente::Avoir->value);
                $al->Validate('Document_No',       $avoirNo);
                $al->Validate('Ligne_No',          $l->Ligne_No->_value);
                $al->Validate('Type_Ligne',        $l->Type_Ligne->_value);
                $al->Validate('No_',               $l->No_->_value);
                $al->Validate('Description',       $l->Description->_value);
                $al->Validate('UM_Code',           $l->UM_Code->_value);
                $al->Validate('Quantite',          $l->Quantite->_value);
                $al->Validate('Prix_Unitaire_HT',  $l->Prix_Unitaire_HT->_value);
                $al->Validate('Remise_Pct',        $l->Remise_Pct->_value);
                $al->Validate('Type_TVA',          $l->Type_TVA->_value);
                $al->Validate('Taux_TVA_Pct',      $l->Taux_TVA_Pct->_value);
                $al->Validate('Compte_Vente_No',   $l->Compte_Vente_No->_value);
                $al->Validate('Tenant_code',       $l->Tenant_code->_value);
                $al->Insert(true);
            }
        }

        return $avoirNo;
    }

    // ─────────────────────────────────────────────
    //  HELPERS PRIVÉS
    // ─────────────────────────────────────────────

    private static function _genererNumero(TypeDocumentVente $type): string {
        // En production : utiliser la table noseries
        $prefix = $type->prefix();
        $max = db->getResultAssoc(
            'SELECT MAX(CAST(SUBSTRING(No_, '.( strlen($prefix)+2).',6) AS UNSIGNED)) AS mx'
           .' FROM entete_vente WHERE No_ LIKE "'.$prefix.'-%"'
        );
        $next = ((int)($max[0]['mx'] ?? 0)) + 1;
        return $prefix.'-'.str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    private static function _genererNumeroDirect(string $prefix): string {
        $safePrefix = preg_replace('/[^A-Z0-9\-]/', '', strtoupper($prefix));
        return $safePrefix.'-'.date('ymd').'-'.str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    private static function _creerEcritureClient(
        string $clientNo, string $nomClient,
        TypeEcritureClient $type, string $documentNo, string $description,
        float $montant, string $dateEcriture, string $dateEcheance,
        string $modeReglCode, string $tenantCode
    ): EcritureClient {
        $ec = new EcritureClient();
        $max = $ec->aggregateSQL('MAX', 'No_');
        $ec->Validate('No_',              ((int)$max) + 1);
        $ec->Validate('Type_Ecriture',    $type->value);
        $ec->Validate('Client_No',        $clientNo);
        $ec->Validate('Nom_Client',       $nomClient);
        $ec->Validate('Date_Ecriture',    $dateEcriture);
        $ec->Validate('Date_Echeance',    $dateEcheance);
        $ec->Validate('Document_Type',    $type->value);
        $ec->Validate('Document_No',      $documentNo);
        $ec->Validate('Description',      $description);
        $ec->Validate('Montant_Original', abs($montant));
        $ec->Validate('Montant_Restant',  abs($montant));
        $ec->Validate('Sens',             $montant >= 0 ? 'Debit' : 'Credit');
        $ec->Validate('Mode_Reglement_Code', $modeReglCode);
        $ec->Validate('Tenant_code',      $tenantCode);
        $ec->Insert(false);
        return $ec;
    }

    private static function _genererPieceComptable(
        EnTeteVente $doc, FactureValideeEnTete $fv,
        string $factureNo, EcritureClient $ecClient
    ): string {
        $pieceNo  = 'VTE-'.$factureNo;
        $date     = date('Y-m-d');
        $client   = new Client();
        $client->get($doc->Client_No->_value);
        $groupe   = new GroupeComptabilisationClient();
        $groupe->get($client->Groupe_Compta_Code->_value);

        $compteClient = $client->Compte_Client_No->_value ?: $groupe->Compte_Client_No->_value;
        $compteTVA    = $groupe->Compte_TVA_Collect_No->_value;

        // Ligne 1 : Débit Client (411xx) — créance
        $lp1 = new LignePiece();
        $lp1->Validate('Journal_Code',  'VTE');
        $lp1->Validate('Piece_No',      $pieceNo);
        $lp1->Validate('Ligne_No',      1);
        $lp1->Validate('Date_Ecriture', $date);
        $lp1->Validate('Compte_No',     $compteClient);
        $lp1->Validate('Libelle',       "Facture ".$factureNo." - ".$doc->Nom_Client->_value);
        $lp1->Validate('Debit',         (float)$fv->Montant_TTC->_value);
        $lp1->Validate('Credit',        0);
        $lp1->Validate('Source_Type',   'Facture');
        $lp1->Validate('Source_No',     $factureNo);
        $lp1->Validate('Tenant_code',   $doc->Tenant_code->_value);
        $lp1->Insert(false);

        // Ligne 2 : Crédit TVA collectée (44310)
        if ((float)$fv->Montant_TVA->_value > 0) {
            $lp2 = new LignePiece();
            $lp2->Validate('Journal_Code',  'VTE');
            $lp2->Validate('Piece_No',      $pieceNo);
            $lp2->Validate('Ligne_No',      2);
            $lp2->Validate('Date_Ecriture', $date);
            $lp2->Validate('Compte_No',     $compteTVA);
            $lp2->Validate('Libelle',       "TVA - Facture ".$factureNo);
            $lp2->Validate('Debit',         0);
            $lp2->Validate('Credit',        (float)$fv->Montant_TVA->_value);
            $lp2->Validate('Type_TVA',      'Collectee');
            $lp2->Validate('Taux_TVA',      18);
            $lp2->Validate('Base_TVA',      (float)$fv->Montant_HT->_value);
            $lp2->Validate('Source_Type',   'Facture');
            $lp2->Validate('Source_No',     $factureNo);
            $lp2->Validate('Tenant_code',   $doc->Tenant_code->_value);
            $lp2->Insert(false);
        }

        // Ligne 3+ : Crédit Produits (70xxx) par ligne
        $lignes = new FactureValideeLigne();
        $lignes->setRange('Facture_No', $factureNo);
        if ($lignes->FindAll()) {
            $n = 3;
            foreach ($lignes->recordSet as $fl) {
                if ((float)$fl->Montant_Ligne_HT->_value == 0) continue;
                $compteVente = $fl->Compte_Vente_No->_value ?: $groupe->Compte_Vente_HT_No->_value;
                if (empty($compteVente)) continue;
                $lp = new LignePiece();
                $lp->Validate('Journal_Code',  'VTE');
                $lp->Validate('Piece_No',      $pieceNo);
                $lp->Validate('Ligne_No',      $n++);
                $lp->Validate('Date_Ecriture', $date);
                $lp->Validate('Compte_No',     $compteVente);
                $lp->Validate('Libelle',       $fl->Description->_value);
                $lp->Validate('Debit',         0);
                $lp->Validate('Credit',        (float)$fl->Montant_Ligne_HT->_value);
                $lp->Validate('Source_Type',   'Facture');
                $lp->Validate('Source_No',     $factureNo);
                $lp->Validate('Tenant_code',   $doc->Tenant_code->_value);
                $lp->Insert(false);
            }
        }

        ComptabiliteManagement::validerPiece('VTE', $pieceNo);
        return $pieceNo;
    }
}
?>
