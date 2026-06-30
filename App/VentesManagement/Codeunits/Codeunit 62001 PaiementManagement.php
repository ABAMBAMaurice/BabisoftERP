<?php

/**
 * Gestion des paiements clients.
 *
 * Flux :
 *  1. Enregistrer le paiement → EcritureClient (type Paiement, sens Crédit)
 *  2. Appliquer le paiement sur une ou plusieurs factures → lettrage
 *  3. Quand Montant_Restant facture = 0 → Statut_Paiement = Paye
 *
 * Le lettrage BC est appliqué : une écriture de paiement peut couvrir
 * plusieurs factures, et une facture peut être payée par plusieurs paiements.
 */
class PaiementManagement {

    // ─────────────────────────────────────────────
    //  ENREGISTRER UN PAIEMENT
    // ─────────────────────────────────────────────

    /**
     * Enregistre un règlement client dans EcritureClient.
     *
     * @param string $clientNo
     * @param float  $montant            Montant encaissé (positif)
     * @param string $date               Date du paiement
     * @param string $modeReglementCode
     * @param string $reference          Référence chèque, virement, transaction mobile money
     * @param string $tenantCode
     * @return EcritureClient
     */
    public static function enregistrerPaiement(
        string $clientNo,
        float  $montant,
        string $date,
        string $modeReglementCode,
        string $reference = '',
        string $tenantCode = ''
    ): EcritureClient {
        if ($montant <= 0)
            Error("Le montant du paiement doit être positif.");

        $client = new Client();
        if (!$client->get($clientNo))
            Error("Client '".$clientNo."' introuvable.");
        if ($client->Bloque->_value)
            Error("Le client '".$clientNo."' est bloqué.");

        $modeRegl = new ModeReglement();
        if (!$modeRegl->get($modeReglementCode))
            Error("Mode de règlement '".$modeReglementCode."' introuvable.");

        $paymentNo = 'PYM-'.date('ymdHis').'-'.rand(100,999);
        $description = "Règlement ".$modeRegl->Intitule->_value
                     . (!empty($reference) ? " réf. ".$reference : '')
                     . " - ".$client->Nom->_value;

        // Écriture client créditrice (réduit le solde client)
        $ec = new EcritureClient();
        $max = $ec->aggregateSQL('MAX', 'No_');
        $ec->Validate('No_',                 ((int)$max) + 1);
        $ec->Validate('Type_Ecriture',       TypeEcritureClient::Paiement->value);
        $ec->Validate('Client_No',           $clientNo);
        $ec->Validate('Nom_Client',          $client->Nom->_value);
        $ec->Validate('Date_Ecriture',       $date);
        $ec->Validate('Date_Echeance',       $date);
        $ec->Validate('Document_Type',       'Paiement');
        $ec->Validate('Document_No',         $paymentNo);
        $ec->Validate('Description',         $description);
        $ec->Validate('Montant_Original',    $montant);
        $ec->Validate('Montant_Restant',     $montant);
        $ec->Validate('Sens',                'Credit');
        $ec->Validate('Mode_Reglement_Code', $modeReglementCode);
        $ec->Validate('Tenant_code',         $tenantCode ?: ($client->Tenant_code->_value ?? ''));
        $ec->Insert(false);

        // Générer l'écriture comptable (débit banque/caisse, crédit 411xxx)
        self::_genererPieceComptablePaiement($ec, $client, $modeRegl, $paymentNo);

        return $ec;
    }

    // ─────────────────────────────────────────────
    //  APPLIQUER UN PAIEMENT SUR DES FACTURES
    // ─────────────────────────────────────────────

    /**
     * Affecte un paiement sur une ou plusieurs factures client.
     * Réduit Montant_Restant de chaque facture et du paiement.
     * Met à jour le statut de paiement.
     *
     * @param int   $paiementNo      N° de l'écriture paiement (Type_Ecriture = Paiement)
     * @param array $factureNos      Tableau de N° d'écritures factures à solder
     */
    public static function appliquerPaiement(int $paiementNo, array $factureNos): void {
        $paiement = new EcritureClient();
        if (!$paiement->get($paiementNo))
            Error("Écriture de paiement N°".$paiementNo." introuvable.");
        if ($paiement->Type_Ecriture->_value !== TypeEcritureClient::Paiement->value)
            Error("L'écriture N°".$paiementNo." n'est pas un paiement.");
        if ((float)$paiement->Montant_Restant->_value <= 0)
            Error("Le paiement N°".$paiementNo." est déjà entièrement affecté.");

        $soldeDisponible = (float)$paiement->Montant_Restant->_value;
        $lettreCode      = self::_genererCodeLettrage($paiement->Client_No->_value);

        foreach ($factureNos as $facNo) {
            if ($soldeDisponible <= 0) break;

            $facture = new EcritureClient();
            if (!$facture->get($facNo))
                Error("Écriture facture N°".$facNo." introuvable.");
            if ($facture->Client_No->_value !== $paiement->Client_No->_value)
                Error("La facture N°".$facNo." n'appartient pas au même client.");
            if ($facture->Type_Ecriture->_value !== TypeEcritureClient::Facture->value
                && $facture->Type_Ecriture->_value !== TypeEcritureClient::Ajustement->value)
                Error("L'écriture N°".$facNo." n'est pas une facture.");
            if ((float)$facture->Montant_Restant->_value <= 0)
                continue; // déjà soldée

            $montantApplique = min($soldeDisponible, (float)$facture->Montant_Restant->_value);
            $soldeDisponible -= $montantApplique;

            // Mettre à jour la facture
            $nouvelleRestant = (float)$facture->Montant_Restant->_value - $montantApplique;
            $facture->Validate('Montant_Restant', $nouvelleRestant);
            $facture->Validate('Statut_Paiement', $nouvelleRestant <= 0
                ? StatutPaiement::Paye->value
                : StatutPaiement::Partiellement_Paye->value);

            if ($nouvelleRestant <= 0) {
                $facture->Validate('Lettrage_Code', $lettreCode);
                $facture->Validate('Date_Lettrage', date('Y-m-d'));
            }
            $facture->Modify(false);

            // Mettre à jour la FactureValideeEnTete correspondante si applicable
            self::_mettreAJourFactureValidee($facture->Document_No->_value, $montantApplique);
        }

        // Réduire le solde du paiement
        $paiement->Validate('Montant_Restant', $soldeDisponible);
        $paiement->Validate('Statut_Paiement', $soldeDisponible <= 0
            ? StatutPaiement::Paye->value
            : StatutPaiement::Partiellement_Paye->value);
        if ($soldeDisponible <= 0) {
            $paiement->Validate('Lettrage_Code', $lettreCode);
            $paiement->Validate('Date_Lettrage', date('Y-m-d'));
        }
        $paiement->Modify(false);
    }

    // ─────────────────────────────────────────────
    //  APPLICATION AUTOMATIQUE (suggestion)
    // ─────────────────────────────────────────────

    /**
     * Applique automatiquement un paiement sur les factures les plus anciennes.
     * Stratégie FIFO : facture la plus ancienne soldée en premier.
     */
    public static function appliquerAutomatiquement(int $paiementNo): array {
        $paiement = new EcritureClient();
        if (!$paiement->get($paiementNo))
            Error("Paiement N°".$paiementNo." introuvable.");

        // Récupérer les factures non soldées dans l'ordre chronologique
        $query = 'SELECT No_ FROM ecriture_client'
               . ' WHERE Client_No = "'.$paiement->Client_No->_value.'"'
               . '   AND Type_Ecriture = "Facture"'
               . '   AND Montant_Restant > 0'
               . '   AND Statut_Paiement != "Paye"'
               . '   AND (deleted_at = "0000-00-00 00:00:00" OR deleted_at IS NULL)'
               . ' ORDER BY Date_Echeance ASC, Date_Ecriture ASC';

        $rows      = db->getResultAssoc($query);
        $factureNos= array_column($rows, 'No_');

        if (empty($factureNos))
            return ['message' => 'Aucune facture en attente pour ce client.', 'affectees' => 0];

        $avantRestant = (float)$paiement->Montant_Restant->_value;
        self::appliquerPaiement($paiementNo, $factureNos);

        $paiement = new EcritureClient();
        $paiement->get($paiementNo);
        $apresRestant = (float)$paiement->Montant_Restant->_value;

        return [
            'message'     => 'Paiement affecté automatiquement.',
            'affecte'     => $avantRestant - $apresRestant,
            'restant'     => $apresRestant,
            'nb_factures' => count($factureNos),
        ];
    }

    // ─────────────────────────────────────────────
    //  SOLDE CLIENT & ÉCHU
    // ─────────────────────────────────────────────

    /**
     * Solde total et échu d'un client (pour affichage et relances).
     */
    public static function getSoldeClient(string $clientNo): array {
        $today  = date('Y-m-d');
        $query  = 'SELECT'
                . ' SUM(CASE WHEN Sens="Debit"  THEN Montant_Restant ELSE -Montant_Restant END) AS solde,'
                . ' SUM(CASE WHEN Sens="Debit" AND Date_Echeance < "'.$today.'"'
                .            ' AND Statut_Paiement != "Paye"'
                .            ' THEN Montant_Restant ELSE 0 END) AS solde_echu,'
                . ' COUNT(CASE WHEN Sens="Debit" AND Statut_Paiement="Non_Paye" THEN 1 END) AS nb_factures_ouvertes'
                . ' FROM ecriture_client'
                . ' WHERE Client_No = "'.$clientNo.'"'
                . ' AND (deleted_at = "0000-00-00 00:00:00" OR deleted_at IS NULL)';

        $rows = db->getResultAssoc($query);
        return [
            'client_no'           => $clientNo,
            'solde'               => (float)($rows[0]['solde'] ?? 0),
            'solde_echu'          => (float)($rows[0]['solde_echu'] ?? 0),
            'nb_factures_ouvertes'=> (int)($rows[0]['nb_factures_ouvertes'] ?? 0),
        ];
    }

    // ─── Helpers privés ───

    private static function _genererCodeLettrage(string $clientNo): string {
        $query = 'SELECT MAX(Lettrage_Code) AS mx FROM ecriture_client'
               . ' WHERE Client_No = "'.$clientNo.'" AND Lettrage_Code != ""'
               . ' AND (deleted_at = "0000-00-00 00:00:00" OR deleted_at IS NULL)';
        $rows  = db->getResultAssoc($query);
        $last  = $rows[0]['mx'] ?? '';
        if (empty($last)) return 'A';
        // Incrémenter lettres (A→B, Z→AA...)
        $code = str_split($last); $i = count($code) - 1;
        while ($i >= 0) {
            if ($code[$i] < 'Z') { $code[$i] = chr(ord($code[$i])+1); return implode('', $code); }
            $code[$i] = 'A'; $i--;
        }
        return 'A'.implode('', $code);
    }

    private static function _mettreAJourFactureValidee(string $factureNo, float $montantApplique): void {
        $fv = new FactureValideeEnTete();
        if ($fv->get($factureNo)) {
            $newRegle   = (float)$fv->Montant_Regle->_value + $montantApplique;
            $newRestant = max(0, (float)$fv->Montant_TTC->_value - $newRegle);
            $fv->Validate('Montant_Regle',   $newRegle);
            $fv->Validate('Montant_Restant', $newRestant);
            $fv->Validate('Statut_Paiement', $newRestant <= 0
                ? StatutPaiement::Paye->value
                : StatutPaiement::Partiellement_Paye->value);
            $fv->Modify(false);
        }
    }

    private static function _genererPieceComptablePaiement(
        EcritureClient $ec,
        Client $client,
        ModeReglement $modeRegl,
        string $paymentNo
    ): void {
        $pieceNo = 'PYM-'.$paymentNo;
        $groupe  = new GroupeComptabilisationClient();
        if (!$groupe->get($client->Groupe_Compta_Code->_value)) return;

        $compteClient = $client->Compte_Client_No->_value ?: $groupe->Compte_Client_No->_value;
        $compteBanque = $modeRegl->Compte_No->_value;

        // Débit Banque / Caisse
        $lp1 = new LignePiece();
        $lp1->Validate('Journal_Code', 'BQ');
        $lp1->Validate('Piece_No',     $pieceNo);
        $lp1->Validate('Ligne_No',     1);
        $lp1->Validate('Date_Ecriture',$ec->Date_Ecriture->_value);
        $lp1->Validate('Compte_No',    $compteBanque);
        $lp1->Validate('Libelle',      $ec->Description->_value);
        $lp1->Validate('Debit',        (float)$ec->Montant_Original->_value);
        $lp1->Validate('Credit',       0);
        $lp1->Validate('Source_Type',  'Paiement');
        $lp1->Validate('Source_No',    $paymentNo);
        $lp1->Validate('Tenant_code',  $ec->Tenant_code->_value);
        $lp1->Insert(false);

        // Crédit Client (411xxx)
        $lp2 = new LignePiece();
        $lp2->Validate('Journal_Code', 'BQ');
        $lp2->Validate('Piece_No',     $pieceNo);
        $lp2->Validate('Ligne_No',     2);
        $lp2->Validate('Date_Ecriture',$ec->Date_Ecriture->_value);
        $lp2->Validate('Compte_No',    $compteClient);
        $lp2->Validate('Libelle',      $ec->Description->_value);
        $lp2->Validate('Debit',        0);
        $lp2->Validate('Credit',       (float)$ec->Montant_Original->_value);
        $lp2->Validate('Source_Type',  'Paiement');
        $lp2->Validate('Source_No',    $paymentNo);
        $lp2->Validate('Tenant_code',  $ec->Tenant_code->_value);
        $lp2->Insert(false);

        try { ComptabiliteManagement::validerPiece('BQ', $pieceNo); }
        catch (\Throwable $e) { /* Période non ouverte : pièce reste en brouillon */ }
    }
}
?>
