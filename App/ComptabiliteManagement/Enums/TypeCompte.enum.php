<?php
/**
 * Classification d'un compte dans le plan comptable SYSCOA/OHADA.
 * Détermine le comportement du compte dans les états financiers.
 */
enum TypeCompte: string {
    case Bilan      = 'Bilan';       // Actif/Passif — reporté d'un exercice à l'autre
    case Resultat   = 'Resultat';    // Charges/Produits — soldé en fin d'exercice
    case Tresorerie = 'Tresorerie';  // Comptes de banque/caisse (classe 5)
    case TVA        = 'TVA';         // Comptes de TVA (443x, 445x)
}
?>
