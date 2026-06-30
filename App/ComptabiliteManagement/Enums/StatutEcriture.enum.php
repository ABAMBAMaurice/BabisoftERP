<?php
/**
 * Cycle de vie d'une écriture dans le grand livre.
 */
enum StatutEcriture: string {
    case Valide     = 'Valide';      // Validée, immuable
    case Lettre     = 'Lettre';      // Lettrée (compte de tiers équilibré)
    case Rapproche  = 'Rapproche';   // Rapprochée sur relevé bancaire
}
?>
