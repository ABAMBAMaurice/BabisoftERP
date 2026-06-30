<?php
/**
 * État d'un exercice comptable.
 */
enum StatutExercice: string {
    case Ouvert     = 'Ouvert';      // Saisies autorisées
    case EnCloture  = 'EnCloture';   // Clôture en cours — lecture seule
    case Cloture    = 'Cloture';     // Clôturé — aucune saisie possible
}
?>
