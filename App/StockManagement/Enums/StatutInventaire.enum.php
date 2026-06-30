<?php
enum StatutInventaire: string {
    case Brouillon  = 'Brouillon';   // Fiche créée, comptage non démarré
    case EnCours    = 'EnCours';     // Comptage physique en cours
    case Compte     = 'Compte';      // Toutes lignes saisies, en attente de validation
    case Valide     = 'Valide';      // Ajustements générés dans EcritureStock
}
?>
