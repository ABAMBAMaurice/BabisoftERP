<?php

/**
 * Gestion des relances clients.
 *
 * Niveaux de relance :
 *   1 → Rappel amiable (J+10 après échéance)
 *   2 → Mise en demeure (J+30 après échéance)
 *   3 → Pré-contentieux (J+60 après échéance, avec frais et intérêts)
 *
 * Règle : on ne relance que les factures avec Montant_Restant > 0
 * et Date_Echeance < date de la relance.
 */
class RelanceManagement {

    const DELAIS_NIVEAUX   = [1 => 10, 2 => 30, 3 => 60]; // jours après échéance
    const FRAIS_NIVEAUX    = [1 => 0,  2 => 5000, 3 => 15000]; // FCFA fixes
    const TAUX_INTERET_PCT = 1.0; // 1% par mois — taux légal Côte d'Ivoire UEMOA

    // ─────────────────────────────────────────────
    //  GÉNÉRER LES RELANCES
    // ─────────────────────────────────────────────

    /**
     * Génère les relances pour tous les clients avec des factures échues.
     * Crée une RelanceEnTete par client avec ses lignes de factures concernées.
     *
     * @param string $dateRelance   Date de la relance (généralement aujourd'hui)
     * @param int    $niveauMin     Niveau minimum de retard (1 par défaut)
     * @return array  Résumé : nb clients relancés, montant total
     */
    public static function genererRelances(string $dateRelance = '', int $niveauMin = 1): array {
        $dateRelance = $dateRelance ?: date('Y-m-d');

        // Chercher toutes les factures échues non soldées
        $query = 'SELECT ec.Client_No, ec.Nom_Client, ec.No_, ec.Document_No,'
               . ' ec.Date_Ecriture, ec.Date_Echeance, ec.Montant_Original, ec.Montant_Restant,'
               . ' DATEDIFF("'.$dateRelance.'", ec.Date_Echeance) AS jours_retard'
               . ' FROM ecriture_client ec'
               . ' WHERE ec.Type_Ecriture = "Facture"'
               . '   AND ec.Statut_Paiement != "Paye"'
               . '   AND ec.Date_Echeance < "'.$dateRelance.'"'
               . '   AND ec.Montant_Restant > 0'
               . '   AND (ec.deleted_at = "0000-00-00 00:00:00" OR ec.deleted_at IS NULL)'
               . ' ORDER BY ec.Client_No, ec.Date_Echeance ASC';

        $rows = db->getResultAssoc($query);
        if (empty($rows)) return ['nb_clients' => 0, 'nb_relances' => 0, 'montant_total' => 0];

        // Grouper par client
        $parClient = [];
        foreach ($rows as $row) {
            $parClient[$row['Client_No']][] = $row;
        }

        $nbRelances   = 0;
        $montantTotal = 0;

        foreach ($parClient as $clientNo => $factures) {
            // Déterminer le niveau de relance selon le retard maximum
            $maxJoursRetard = max(array_column($factures, 'jours_retard'));
            $niveau = self::_niveauPourRetard($maxJoursRetard);

            if ($niveau < $niveauMin) continue;

            // Vérifier qu'une relance récente n'existe pas déjà pour ce client
            $relExist = new RelanceEnTete();
            $relExist->setRange('Client_No', $clientNo);
            $relExist->setFilter('Date_Relance', '>=%1', date('Y-m-d', strtotime($dateRelance.' -7 days')));
            if ($relExist->FindFirst()) continue; // déjà relancé cette semaine

            $relanceNo = self::_genererNumeroRelance();
            $frais      = self::FRAIS_NIVEAUX[$niveau] ?? 0;
            $totalEchu  = array_sum(array_column($factures, 'jours_retard')) > 0
                          ? array_sum(array_column($factures, 'Montant_Restant'))
                          : 0;

            // Calcul des intérêts de retard (niveau 3 uniquement)
            $interets = 0;
            if ($niveau >= 3) {
                foreach ($factures as $f) {
                    $mois = ceil((int)$f['jours_retard'] / 30);
                    $interets += (float)$f['Montant_Restant'] * self::TAUX_INTERET_PCT / 100 * $mois;
                }
                $interets = round($interets, 2);
            }

            // Délai de réponse
            $dateEcheanceRelance = date('Y-m-d', strtotime($dateRelance.' +15 days'));

            // Créer l'en-tête de relance
            $relance = new RelanceEnTete();
            $relance->Validate('No_',              $relanceNo);
            $relance->Validate('Client_No',        $clientNo);
            $relance->Validate('Nom_Client',       $factures[0]['Nom_Client']);
            $relance->Validate('Date_Relance',     $dateRelance);
            $relance->Validate('Date_Echeance',    $dateEcheanceRelance);
            $relance->Validate('Niveau_Relance',   $niveau);
            $relance->Validate('Montant_Echu',     $totalEchu);
            $relance->Validate('Frais_Relance',    $frais);
            $relance->Validate('Interets_Retard',  $interets);
            $relance->Validate('Tenant_code',      ''); // à renseigner via contexte tenant
            $relance->Insert(false);

            // Créer les lignes de relance
            $ligneNo = 1;
            foreach ($factures as $f) {
                $lrl = new RelanceLigne();
                $lrl->Validate('Relance_No',       $relanceNo);
                $lrl->Validate('Ligne_No',         $ligneNo++);
                $lrl->Validate('Type_Ligne',       'Ecriture');
                $lrl->Validate('Ecriture_Client_No',(int)$f['No_']);
                $lrl->Validate('Document_No',      $f['Document_No']);
                $lrl->Validate('Description',      "Facture ".$f['Document_No']);
                $lrl->Validate('Date_Document',    $f['Date_Ecriture']);
                $lrl->Validate('Date_Echeance',    $f['Date_Echeance']);
                $lrl->Validate('Jours_Retard',     (int)$f['jours_retard']);
                $lrl->Validate('Montant_Original', (float)$f['Montant_Original']);
                $lrl->Validate('Montant_Restant',  (float)$f['Montant_Restant']);
                $lrl->Validate('Niveau_Relance',   $niveau);
                $lrl->Validate('Tenant_code',      '');
                $lrl->Insert(false);
            }

            // Ligne de frais si applicable
            if ($frais > 0) {
                $lrf = new RelanceLigne();
                $lrf->Validate('Relance_No',  $relanceNo);
                $lrf->Validate('Ligne_No',    $ligneNo++);
                $lrf->Validate('Type_Ligne',  'Frais');
                $lrf->Validate('Description', 'Frais de relance niveau '.$niveau);
                $lrf->Validate('Montant_Restant', $frais);
                $lrf->Validate('Niveau_Relance',  $niveau);
                $lrf->Validate('Tenant_code', '');
                $lrf->Insert(false);
            }

            if ($interets > 0) {
                $lri = new RelanceLigne();
                $lri->Validate('Relance_No',  $relanceNo);
                $lri->Validate('Ligne_No',    $ligneNo++);
                $lri->Validate('Type_Ligne',  'Interet');
                $lri->Validate('Description', 'Intérêts de retard ('.self::TAUX_INTERET_PCT.'%/mois)');
                $lri->Validate('Montant_Interet', $interets);
                $lri->Validate('Montant_Restant', $interets);
                $lri->Validate('Niveau_Relance',  $niveau);
                $lri->Validate('Tenant_code', '');
                $lri->Insert(false);
            }

            $nbRelances++;
            $montantTotal += $totalEchu;
        }

        return [
            'nb_clients'    => count($parClient),
            'nb_relances'   => $nbRelances,
            'montant_total' => $montantTotal,
            'date_relance'  => $dateRelance,
        ];
    }

    // ─────────────────────────────────────────────
    //  ÉMETTRE UNE RELANCE
    // ─────────────────────────────────────────────

    /**
     * Passe une relance au statut Emis (prête à être envoyée).
     * Si des frais de relance sont configurés, crée une facture.
     */
    public static function emettre(string $relanceNo): void {
        $relance = new RelanceEnTete();
        if (!$relance->get($relanceNo))
            Error("Relance '".$relanceNo."' introuvable.");
        if ($relance->Statut->_value !== 'Brouillon')
            Error("La relance '".$relanceNo."' n'est pas en brouillon.");

        $relance->Validate('Statut', 'Emis');
        $relance->Modify();
    }

    // ─────────────────────────────────────────────
    //  RAPPORT CLIENTS EN RETARD
    // ─────────────────────────────────────────────

    /**
     * Balance âgée des créances (Aging Report).
     * Regroupe les montants dus par tranches d'ancienneté.
     */
    public static function getBalanceAgee(string $dateArrete = ''): array {
        $dateArrete = $dateArrete ?: date('Y-m-d');

        $query = 'SELECT c.No_, c.Nom, c.Responsable_Code,'
               . ' SUM(CASE WHEN ec.Date_Echeance >= "'.$dateArrete.'" THEN ec.Montant_Restant ELSE 0 END) AS non_echu,'
               . ' SUM(CASE WHEN DATEDIFF("'.$dateArrete.'", ec.Date_Echeance) BETWEEN 1  AND 30  THEN ec.Montant_Restant ELSE 0 END) AS `0_30`,'
               . ' SUM(CASE WHEN DATEDIFF("'.$dateArrete.'", ec.Date_Echeance) BETWEEN 31 AND 60  THEN ec.Montant_Restant ELSE 0 END) AS `31_60`,'
               . ' SUM(CASE WHEN DATEDIFF("'.$dateArrete.'", ec.Date_Echeance) BETWEEN 61 AND 90  THEN ec.Montant_Restant ELSE 0 END) AS `61_90`,'
               . ' SUM(CASE WHEN DATEDIFF("'.$dateArrete.'", ec.Date_Echeance) > 90              THEN ec.Montant_Restant ELSE 0 END) AS `plus_90`,'
               . ' SUM(ec.Montant_Restant) AS total'
               . ' FROM ecriture_client ec'
               . ' JOIN client c ON c.No_ = ec.Client_No'
               . ' WHERE ec.Type_Ecriture = "Facture"'
               . '   AND ec.Statut_Paiement != "Paye"'
               . '   AND ec.Montant_Restant > 0'
               . '   AND (ec.deleted_at = "0000-00-00 00:00:00" OR ec.deleted_at IS NULL)'
               . ' GROUP BY c.No_, c.Nom, c.Responsable_Code'
               . ' HAVING total > 0'
               . ' ORDER BY `plus_90` DESC, total DESC';

        $rows = db->getResultAssoc($query);
        $totaux = ['non_echu'=>0, '0_30'=>0, '31_60'=>0, '61_90'=>0, 'plus_90'=>0, 'total'=>0];
        foreach ($rows as $r) {
            foreach (array_keys($totaux) as $k) $totaux[$k] += (float)($r[$k] ?? 0);
        }

        return [
            'date_arrete' => $dateArrete,
            'clients'     => $rows,
            'totaux'      => $totaux,
        ];
    }

    // ─── Helpers ───

    private static function _niveauPourRetard(int $joursRetard): int {
        foreach (array_reverse(self::DELAIS_NIVEAUX, true) as $niveau => $delai) {
            if ($joursRetard >= $delai) return $niveau;
        }
        return 0; // pas encore en retard selon les seuils
    }

    private static function _genererNumeroRelance(): string {
        $rel = new RelanceEnTete();
        $max = $rel->aggregateSQL('MAX', 'No_');
        $next = ((int)str_replace('REL-', '', $max ?? 'REL-0')) + 1;
        return 'REL-'.str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
?>
