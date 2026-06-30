<?php

class CompteComptable extends Table {
    public function __construct() {
        parent::__construct(60000, 'compte_comptable');

        $this->field(1,  'Numero',               FieldType::text(20, 'NOT NULL'), caption: 'N° Compte');
        $this->field(2,  'Intitule',              FieldType::text(150),            caption: 'Intitulé');
        $this->field(3,  'Classe',                FieldType::integer(),             caption: 'Classe SYSCOA');
        $this->field(4,  'Type',                  FieldType::text(30),             caption: 'Type'); // TypeCompte
        $this->field(5,  'Sens_Normal',           FieldType::text(10),             caption: 'Sens normal'); // Debit | Credit
        $this->field(6,  'Est_Collectif',         FieldType::boolean(),            caption: 'Compte collectif');
        $this->field(7,  'Type_TVA',              FieldType::text(20),             caption: 'Type TVA'); // Aucune | Collectee | Deductible
        $this->field(8,  'Taux_TVA',              FieldType::decimal(),            caption: 'Taux TVA (%)');
        $this->field(9,  'Compte_TVA_No',         FieldType::text(20),             caption: 'Compte TVA lié');
        $this->field(10, 'Est_Actif',             FieldType::boolean(),            caption: 'Actif');
        $this->field(11, 'Tenant_code',           FieldType::text(50),             caption: 'Tenant');

        // FlowFields — calculés via CalcFields()
        $this->flowField(20, 'Solde_Debit', FieldType::decimal(),
            CalcFormula::Sum(EcritureComptable::class, 'Debit', ['Compte_No' => 'Numero']),
            caption: 'Total Débit'
        );
        $this->flowField(21, 'Solde_Credit', FieldType::decimal(),
            CalcFormula::Sum(EcritureComptable::class, 'Credit', ['Compte_No' => 'Numero']),
            caption: 'Total Crédit'
        );
        $this->flowField(22, 'Nb_Ecritures', FieldType::integer(),
            CalcFormula::Count(EcritureComptable::class, ['Compte_No' => 'Numero']),
            caption: 'Nb écritures'
        );

        $this->Keys('Numero');
    }

    public function onInsert() {
        $this->Validate('Classe', $this->_detecterClasse());
        $this->Validate('Sens_Normal', $this->_sensNormalParClasse());
        $this->Validate('Type', $this->_typeParClasse());
        $this->Validate('Type_TVA', 'Aucune');
        $this->Validate('Taux_TVA', 0);
        $this->Validate('Est_Actif', true);
        $this->Validate('Est_Collectif', false);
    }

    public function onModify() {
        $this->Validate('Classe', $this->_detecterClasse());
        $this->Validate('Sens_Normal', $this->_sensNormalParClasse());
    }

    public function onDelete() {
        $ec = new EcritureComptable();
        $ec->setRange('Compte_No', $this->Numero->_value);
        if ($ec->FindFirst())
            Error("Impossible de supprimer le compte '".$this->Numero->_value."' : des écritures comptables existent.");
    }

    // --- Helpers privés ---

    private function _detecterClasse(): int {
        return (int)substr($this->Numero->_value ?? '0', 0, 1);
    }

    /**
     * Sens normal du solde selon la classification SYSCOA.
     *
     * Classes Actif (Débit normal)  : 2 Immo, 3 Stocks, 5 Trésorerie, 6 Charges
     * Classes Passif (Crédit normal): 1 Capitaux, 7 Produits
     * Classe 4 (Tiers) : débit pour comptes clients (41x), crédit pour fournisseurs (40x)
     */
    private function _sensNormalParClasse(): string {
        $no = $this->Numero->_value ?? '0';
        $classe = (int)substr($no, 0, 1);
        $sousCl = (int)substr($no, 0, 2);

        return match(true) {
            $classe === 1                   => 'Credit', // Capitaux propres
            $classe === 2                   => 'Debit',  // Actif immobilisé
            $classe === 3                   => 'Debit',  // Stocks
            $sousCl === 40                  => 'Credit', // Fournisseurs
            $sousCl === 41                  => 'Debit',  // Clients
            $classe === 4                   => 'Debit',  // Autres tiers
            $classe === 5                   => 'Debit',  // Trésorerie
            $classe === 6                   => 'Debit',  // Charges
            $classe === 7                   => 'Credit', // Produits
            $classe === 8                   => 'Debit',  // HAO
            default                         => 'Debit',
        };
    }

    private function _typeParClasse(): string {
        $classe = (int)substr($this->Numero->_value ?? '0', 0, 1);
        return match($classe) {
            1, 2, 3, 4 => 'Bilan',
            5           => 'Tresorerie',
            6, 7, 8     => 'Resultat',
            default     => 'Bilan',
        };
    }
}
?>
