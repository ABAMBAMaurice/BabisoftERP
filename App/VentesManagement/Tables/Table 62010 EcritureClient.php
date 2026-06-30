<?php
/**
 * Grand livre client (Customer Ledger Entry).
 * Trace tous les mouvements financiers client : factures, avoirs, paiements.
 *
 * Montant_Original : montant au moment de la création (positif)
 * Montant_Restant  : solde non encore réglé/compensé
 * → Solde client = SUM(Montant_Restant) — peut être + (dû) ou - (avoir)
 */
class EcritureClient extends Table {
    public function __construct() {
        parent::__construct(62010,'ecriture_client');

        $this->field(1,  'No_',                  FieldType::integer('NOT NULL'));
        $this->field(2,  'Type_Ecriture',        FieldType::text(30, 'NOT NULL'));
        $this->field(3,  'Client_No',            FieldType::text(20, 'NOT NULL'));
        $this->field(4,  'Nom_Client',           FieldType::text(150));
        $this->field(5,  'Date_Ecriture',        FieldType::date('NOT NULL'));
        $this->field(6,  'Date_Echeance',        FieldType::date(),   caption: 'Date d\'échéance (factures)');
        $this->field(7,  'Document_Type',        FieldType::text(30), caption: 'Facture | Avoir | Paiement');
        $this->field(8,  'Document_No',          FieldType::text(20, 'NOT NULL'), caption: 'N° document source');
        $this->field(9,  'Description',          FieldType::text(200));

        // ── Montants ─────────────────────────────
        $this->field(20, 'Montant_Original', FieldType::decimal('(15,2) NOT NULL'), caption: 'Montant à l\'origine (positif)');
        $this->field(21, 'Montant_Restant',  FieldType::decimal('(15,2) NOT NULL'), caption: 'Montant non encore réglé');
        $this->field(22, 'Sens',             FieldType::text(10),                   caption: 'Debit (créance) | Credit (avoir/paiement)');

        // ── Paiement / lettrage ──────────────────
        $this->field(30, 'Statut_Paiement',      FieldType::text(30));
        $this->field(31, 'Lettrage_Code',        FieldType::text(20),  caption: 'Code de lettrage paiement');
        $this->field(32, 'Date_Lettrage',        FieldType::date());
        $this->field(33, 'Mode_Reglement_Code',  FieldType::text(10));

        // ── Comptabilisation ────────────────────
        $this->field(40, 'Piece_Compta_No', FieldType::text(30),  caption: 'N° pièce comptable générée');
        $this->field(41, 'Exercice_Code',   FieldType::text(20));
        $this->field(42, 'Periode_No',      FieldType::integer());
        $this->field(43, 'Tenant_code',     FieldType::text(30, 'NOT NULL'));

        $this->Keys('No_');
    }

    public function onInsert(): void {
        if ((float)$this->Montant_Restant->_value == 0)
            $this->Montant_Restant->_value = (float)$this->Montant_Original->_value;
    }

    public function onDelete(): void {
        if (!empty($this->Lettrage_Code->_value))
            Error("Cette écriture est lettrée ('".$this->Lettrage_Code->_value."'). Délettrez d'abord.");
    }
}
?>
