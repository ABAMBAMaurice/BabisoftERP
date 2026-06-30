<?php
enum TypeArticle: string {
    case Stock      = 'Stock';       // Article physique stocké, valorisé, suivi en quantité
    case Service    = 'Service';     // Prestation — pas de stock, pas de valorisation
    case NonStocke  = 'NonStocke';   // Consommable acheté et utilisé immédiatement
}
?>
