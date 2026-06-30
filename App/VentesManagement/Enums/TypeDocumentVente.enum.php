<?php
enum TypeDocumentVente: string {
    case Devis    = 'Devis';
    case Commande = 'Commande';
    case Facture  = 'Facture';    // Facture directe (sans commande)
    case Avoir    = 'Avoir';      // Note de crédit

    public function prefix(): string {
        return match($this) {
            self::Devis    => 'DV',
            self::Commande => 'CO',
            self::Facture  => 'FV',
            self::Avoir    => 'AV',
        };
    }

    public function estFacturable(): bool {
        return in_array($this, [self::Commande, self::Facture]);
    }

    public function estLivrable(): bool {
        return $this === self::Commande;
    }
}
?>
