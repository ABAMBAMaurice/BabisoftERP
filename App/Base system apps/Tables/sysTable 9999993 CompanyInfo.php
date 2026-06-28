<?php
    class CompanyInfo extends Table {
            public function __construct()
            {
                parent::__construct(9999993, 'companyinfo');
    
                $this->field(1, 'Code', FieldType::text(30),caption:'code');
                $this->field(2, 'Company_name', FieldType::text(100),caption:'Raison sociale');
                $this->field(3, 'Address', FieldType::text(100),caption:'Adresse');
                $this->field(4, 'Rccm', FieldType::text(50),caption:'N° RCCM');
                $this->field(5, 'Ncc', FieldType::text(50),caption:'N° Compte Contribuable');
                $this->field(6, 'Telephone_fixe', FieldType::text(30),caption:'N° Téléphone fixe');
                $this->field(7, 'Telephone_mobile', FieldType::text(30),caption:'N° Téléphone mobile');
                $this->field(7, 'Capital', FieldType::decimal('(10,2)'),caption:'Capital');
                $this->field(8, 'Centre_impot', FieldType::text(30),caption:'Centre des impôts');
                $this->field(9, 'Email', FieldType::text(100),caption:'E-mail');
                $this->field(10, 'legal_form', FieldType::text(30),caption:'Forme juridique');
                $this->field(11, 'SIREN', FieldType::text(30),caption:'SIREN');
                $this->field(12, 'SIRET', FieldType::text(30),caption:'SIRET');
                $this->field(13, 'APE',         FieldType::text(30),  caption:'APE');
                $this->field(14, 'Ville',       FieldType::text(50),  caption:'Ville');
                $this->field(15, 'Pays',        FieldType::text(50),  caption:'Pays');
                $this->field(16, 'Tenant_code', FieldType::text(50),  caption:'Tenant');

                $this->Keys('Code');
            }
            public function onInsert() {
            }
            public function onModify() {
            }
            public function onDelete() {
            }
    }

?>