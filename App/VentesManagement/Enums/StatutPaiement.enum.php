<?php
enum StatutPaiement: string {
    case Non_Paye          = 'Non_Paye';
    case Partiellement_Paye= 'Partiellement_Paye';
    case Paye              = 'Paye';
    case En_Litige         = 'En_Litige';
}
?>
