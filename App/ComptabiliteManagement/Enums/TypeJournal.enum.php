<?php
/**
 * Type d'un journal comptable.
 * Chaque type impose des règles de saisie et de numérotation spécifiques.
 */
enum TypeJournal: string {
    case Ventes             = 'Ventes';    // Journal des ventes (VTE)
    case Achats             = 'Achats';    // Journal des achats (ACH)
    case Tresorerie         = 'Tresorerie';// Journal de banque/caisse (BQ, CA)
    case Operations_Diverses = 'OD';       // Opérations diverses (OD)
    case A_Nouveaux         = 'AN';        // À nouveaux — ouverture d'exercice
}
?>
