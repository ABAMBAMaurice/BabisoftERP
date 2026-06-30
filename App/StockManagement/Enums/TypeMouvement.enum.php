<?php
/**
 * Types de mouvements de stock.
 * Entrées (sens positif) et Sorties (sens négatif).
 */
enum TypeMouvement: string {
    // ── Entrées ──────────────────────────────────
    case Achat              = 'Achat';              // Réception fournisseur
    case Retour_Client      = 'Retour_Client';      // Retour d'un client
    case Transfert_Entrant  = 'Transfert_Entrant';  // Réception inter-entrepôt
    case Inventaire_Plus    = 'Inventaire_Plus';    // Écart positif inventaire
    case Ajustement_Plus    = 'Ajustement_Plus';    // Ajustement manuel +
    case Production         = 'Production';         // Entrée de fabrication

    // ── Sorties ──────────────────────────────────
    case Vente              = 'Vente';              // Livraison client
    case Retour_Fournisseur = 'Retour_Fournisseur'; // Retour à un fournisseur
    case Transfert_Sortant  = 'Transfert_Sortant';  // Expédition inter-entrepôt
    case Inventaire_Moins   = 'Inventaire_Moins';   // Écart négatif inventaire
    case Ajustement_Moins   = 'Ajustement_Moins';   // Ajustement manuel -
    case Consommation       = 'Consommation';       // Usage interne / production

    public function estEntree(): bool {
        return in_array($this, [
            self::Achat, self::Retour_Client, self::Transfert_Entrant,
            self::Inventaire_Plus, self::Ajustement_Plus, self::Production,
        ]);
    }

    public function sensNumerique(): int {
        return $this->estEntree() ? 1 : -1;
    }
}
?>
