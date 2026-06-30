<?php

/**
 * Gestion de l'inventaire physique.
 *
 * Cycle :
 *  1. Créer l'inventaire (Brouillon)
 *  2. Générer les lignes depuis le stock théorique → EnCours
 *  3. L'opérateur saisit les quantités comptées → Compte
 *  4. Valider : génère les écritures d'ajustement → Valide
 */
class InventaireManagement {

    // ─────────────────────────────────────────────
    //  INITIALISER LES LIGNES
    // ─────────────────────────────────────────────

    /**
     * Génère les lignes d'inventaire depuis le stock théorique actuel.
     * Crée une ligne par (Article × Emplacement) ayant du stock > 0.
     *
     * @param string $inventaireNo  N° de l'inventaire (doit être Brouillon)
     */
    public static function initialiserLignes(string $inventaireNo): int {
        $inventaire = new InventaireEnTete();
        if (!$inventaire->get($inventaireNo))
            Error("Inventaire '".$inventaireNo."' introuvable.");
        if ($inventaire->Statut->_value !== StatutInventaire::Brouillon->value)
            Error("L'inventaire n'est pas en statut Brouillon.");

        // Supprimer les lignes existantes éventuelles
        $lignesExist = new InventaireLigne();
        $lignesExist->setRange('Inventaire_No', $inventaireNo);
        if ($lignesExist->FindAll())
            foreach ($lignesExist->recordSet as $l) $l->Delete(false);

        // Récupérer les positions de stock non nulles dans l'entrepôt
        $query = 'SELECT es.Article_Code, es.Emplacement_Code, es.No_Lot,'
               . ' SUM(es.Quantite_Nette) AS qte_theorique,'
               . ' a.Description, a.CMUP_Actuel, a.Cout_Standard, a.Methode_Valorisation'
               . ' FROM ecriture_stock es'
               . ' JOIN article a ON a.Code = es.Article_Code'
               . ' WHERE es.Entrepot_Code = "'.$inventaire->Entrepot_Code->_value.'"'
               . '   AND a.Type = "Stock"'
               . '   AND (es.deleted_at = "0000-00-00 00:00:00" OR es.deleted_at IS NULL)';

        if (!empty($inventaire->Filtre_Categorie->_value))
            $query .= ' AND a.Categorie_Code = "'.$inventaire->Filtre_Categorie->_value.'"';

        $query .= ' GROUP BY es.Article_Code, es.Emplacement_Code, es.No_Lot,'
               .  ' a.Description, a.CMUP_Actuel, a.Cout_Standard, a.Methode_Valorisation'
               .  ' HAVING qte_theorique >= 0'  // inclure les 0 pour qu'ils soient comptés
               .  ' ORDER BY es.Article_Code, es.Emplacement_Code';

        $rows     = db->getResultAssoc($query);
        $ligneNo  = 1;

        foreach ($rows as $row) {
            $methode = MethodeValorisation::from($row['Methode_Valorisation']);
            $cu = $methode === MethodeValorisation::Standard
                  ? (float)$row['Cout_Standard']
                  : (float)$row['CMUP_Actuel'];

            $ligne = new InventaireLigne();
            $ligne->Validate('Inventaire_No',   $inventaireNo);
            $ligne->Validate('Ligne_No',        $ligneNo++);
            $ligne->Validate('Article_Code',    $row['Article_Code']);
            $ligne->Validate('Description',     $row['Description']);
            $ligne->Validate('Entrepot_Code',   $inventaire->Entrepot_Code->_value);
            $ligne->Validate('Emplacement_Code',$row['Emplacement_Code']);
            $ligne->Validate('No_Lot',          $row['No_Lot'] ?? '');
            $ligne->Validate('Qte_Theorique',   (float)$row['qte_theorique']);
            $ligne->Validate('Qte_Comptee',     0); // à saisir
            $ligne->Validate('Cout_Unitaire',   $cu);
            $ligne->Validate('Tenant_code',     $inventaire->Tenant_code->_value);
            $ligne->Insert(false);
        }

        // Passer en "En cours"
        $inventaire->Validate('Statut', StatutInventaire::EnCours->value);
        $inventaire->Modify();

        return $ligneNo - 1;
    }

    // ─────────────────────────────────────────────
    //  VALIDER L'INVENTAIRE
    // ─────────────────────────────────────────────

    /**
     * Valide l'inventaire : génère les écritures d'ajustement pour les écarts.
     * Seules les lignes avec Ecart ≠ 0 produisent une écriture.
     */
    public static function valider(string $inventaireNo): array {
        $inventaire = new InventaireEnTete();
        if (!$inventaire->get($inventaireNo))
            Error("Inventaire '".$inventaireNo."' introuvable.");

        if (!in_array($inventaire->Statut->_value, [
            StatutInventaire::EnCours->value, StatutInventaire::Compte->value
        ])) Error("L'inventaire doit être en cours ou compté pour être validé.");

        $lignes = new InventaireLigne();
        $lignes->setRange('Inventaire_No', $inventaireNo);
        if (!$lignes->FindAll())
            Error("Aucune ligne d'inventaire à valider.");

        $ajustementsGeneres = 0;
        $totalEcart = 0.0;

        foreach ($lignes->recordSet as $ligne) {
            $ecart = (float)$ligne->Ecart->_value;
            if (abs($ecart) < 0.001) continue;

            // Générer l'écriture d'ajustement
            StockManagement::ajuster(
                articleCode:     $ligne->Article_Code->_value,
                entrepotCode:    $ligne->Entrepot_Code->_value,
                quantite:        $ecart,
                motif:           'Inventaire '.$inventaireNo,
                emplacementCode: $ligne->Emplacement_Code->_value,
                coutUnitaire:    (float)$ligne->Cout_Unitaire->_value
            );

            $totalEcart += (float)$ligne->Valeur_Ecart->_value;
            $ajustementsGeneres++;
        }

        // Clore l'inventaire
        $inventaire->Validate('Statut',          StatutInventaire::Valide->value);
        $inventaire->Validate('Date_Validation', date('Y-m-d'));
        $inventaire->Modify();

        return [
            'inventaire_no'       => $inventaireNo,
            'ajustements_generes' => $ajustementsGeneres,
            'total_ecart_valeur'  => $totalEcart,
            'date_validation'     => date('Y-m-d'),
        ];
    }

    // ─────────────────────────────────────────────
    //  RAPPORT D'INVENTAIRE
    // ─────────────────────────────────────────────

    /**
     * Rapport détaillé de l'inventaire avec écarts et valeurs.
     */
    public static function getRapport(string $inventaireNo): array {
        $inventaire = new InventaireEnTete();
        if (!$inventaire->get($inventaireNo))
            Error("Inventaire '".$inventaireNo."' introuvable.");

        $lignes = new InventaireLigne();
        $lignes->setRange('Inventaire_No', $inventaireNo);
        $lignes->FindAll();

        $lignesOk    = 0;
        $lignesEcart = 0;
        $valeurEcartPos = 0.0;
        $valeurEcartNeg = 0.0;

        $data = [];
        foreach ($lignes->recordSet ?? [] as $l) {
            $ecart = (float)$l->Ecart->_value;
            if (abs($ecart) < 0.001) $lignesOk++;
            else {
                $lignesEcart++;
                if ($ecart > 0) $valeurEcartPos += (float)$l->Valeur_Ecart->_value;
                else            $valeurEcartNeg += (float)$l->Valeur_Ecart->_value;
            }
            $data[] = [
                'article'       => $l->Article_Code->_value,
                'description'   => $l->Description->_value,
                'emplacement'   => $l->Emplacement_Code->_value,
                'no_lot'        => $l->No_Lot->_value,
                'qte_theorique' => (float)$l->Qte_Theorique->_value,
                'qte_comptee'   => (float)$l->Qte_Comptee->_value,
                'ecart'         => $ecart,
                'cout_unitaire' => (float)$l->Cout_Unitaire->_value,
                'valeur_ecart'  => (float)$l->Valeur_Ecart->_value,
                'statut'        => $ecart == 0 ? 'OK' : ($ecart > 0 ? 'Surplus' : 'Manque'),
            ];
        }

        return [
            'inventaire'        => [
                'no'          => $inventaireNo,
                'entrepot'    => $inventaire->Entrepot_Code->_value,
                'date'        => $inventaire->Date_Inventaire->_value,
                'statut'      => $inventaire->Statut->_value,
            ],
            'lignes'            => $data,
            'nb_lignes_ok'      => $lignesOk,
            'nb_lignes_ecart'   => $lignesEcart,
            'valeur_ecart_pos'  => $valeurEcartPos,
            'valeur_ecart_neg'  => $valeurEcartNeg,
            'valeur_ecart_net'  => $valeurEcartPos + $valeurEcartNeg,
        ];
    }
}
?>
