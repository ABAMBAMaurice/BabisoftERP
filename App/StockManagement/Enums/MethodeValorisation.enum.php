<?php
/**
 * Méthode de valorisation du stock.
 *
 * CMUP : obligatoire SYSCOA/OHADA pour la grande majorité des entreprises.
 * FIFO : admis pour les denrées périssables.
 * Standard : prix fixe pour la production industrielle (écarts analysés séparément).
 */
enum MethodeValorisation: string {
    case CMUP     = 'CMUP';      // Coût Moyen Unitaire Pondéré (défaut SYSCOA)
    case FIFO     = 'FIFO';      // Premier Entré Premier Sorti
    case Standard = 'Standard';  // Prix de revient standard fixe
}
?>
