<?php
enum StatutDocumentVente: string {
    case Ouvert               = 'Ouvert';
    case Libere               = 'Libere';               // Approuvé, prêt à être traité
    case Partiellement_Livre  = 'Partiellement_Livre';  // Livraison partielle
    case Livre                = 'Livre';                // Intégralement livré
    case Partiellement_Facture= 'Partiellement_Facture';
    case Facture              = 'Facture';              // Intégralement facturé
    case Annule               = 'Annule';

    public function estModifiable(): bool {
        return in_array($this, [self::Ouvert, self::Libere]);
    }

    public function peutEtreLibere(): bool {
        return $this === self::Ouvert;
    }
}
?>
