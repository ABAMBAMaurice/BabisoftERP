<?php
enum TypeEcritureClient: string {
    case Facture    = 'Facture';
    case Avoir      = 'Avoir';
    case Paiement   = 'Paiement';
    case Ajustement = 'Ajustement';

    public function sensNumerique(): int {
        // Facture augmente la dette client (positif)
        // Avoir et Paiement la diminuent (négatif)
        return match($this) {
            self::Facture    =>  1,
            self::Avoir      => -1,
            self::Paiement   => -1,
            self::Ajustement =>  1,
        };
    }
}
?>
