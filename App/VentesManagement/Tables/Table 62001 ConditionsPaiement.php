<?php
/**
 * Conditions de paiement.
 * Détermine la date d'échéance d'une facture.
 * Exemples courants en Côte d'Ivoire : Comptant, Net30, 60JFM (60 jours fin de mois)
 */
class ConditionsPaiement extends Table {
    public function __construct() {
        parent::__construct(62001, 'conditions_paiement');

        $this->field(1, 'Code',                  FieldType::text(10, 'NOT NULL'));
        $this->field(2, 'Intitule',              FieldType::text(100, 'NOT NULL'));
        $this->field(3, 'Type',                  FieldType::text(30),    caption: 'Net | Fin_de_Mois | x_Jours_FM | Comptant | Immediat');
        $this->field(4, 'Duree_Jours',           FieldType::integer(),    caption: 'Nombre de jours');
        $this->field(5, 'Escompte_Pct',          FieldType::decimal(),    caption: 'Escompte accordé si payé tôt (%)');
        $this->field(6, 'Duree_Escompte_Jours',  FieldType::integer(),    caption: 'Délai pour bénéficier de l\'escompte');
        $this->field(7, 'Tenant_code',           FieldType::text(30, 'NOT NULL'));

        $this->Keys('Code');
    }

    /**
     * Calcule la date d'échéance en fonction du type de condition.
     */
    public function calculerEcheance(string $dateDocument): string {
        $date = new DateTime($dateDocument);
        $type = $this->Type->_value;
        $jours= (int)$this->Duree_Jours->_value;

        return match($type) {
            'Immediat', 'Comptant' => $dateDocument,

            'Net' => (clone $date)->modify('+'.$jours.' days')->format('Y-m-d'),

            'Fin_de_Mois' => (clone $date)
                ->modify('last day of this month')
                ->format('Y-m-d'),

            'x_Jours_FM' => (clone $date)
                ->modify('last day of this month')
                ->modify('+'.$jours.' days')
                ->format('Y-m-d'),

            default => (clone $date)->modify('+'.$jours.' days')->format('Y-m-d'),
        };
    }
}
?>
