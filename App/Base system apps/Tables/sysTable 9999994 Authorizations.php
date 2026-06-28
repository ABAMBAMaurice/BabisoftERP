<?php

class Authorization extends Table {
    public function __construct()
    {
        parent::__construct(9999994, 'authorization');

        $this->field(1, 'Profile', FieldType::text(30, ),caption:'Profile', tableRelation: new Profile());
        $this->field(2, 'Page_Id', FieldType::text(30),caption:'Id page', tableRelation: new Views(), onValidate: function () {
                       // Vérification que la page existe
              $page = new Views();
              if ($page->get($this->Page_Id->value))
                $this->Validate('Page_Name', $page->caption->value);
        });
        $this->field(3, 'Inserting', FieldType::boolean(), caption: 'Insertion');
        $this->field(4, 'Deleting', FieldType::boolean(), caption: 'Suppression');
        $this->field(5, 'Modifying', FieldType::boolean(), caption: 'Modification');
        $this->field(6, 'Viewing', FieldType::boolean(), caption: 'Consultation');
        $this->field(7, 'Page_Name', FieldType::text(100), caption: 'Nom de la page');

        $this->Keys( 'Profile', 'Page_Id');
    }
    public function onInsert() {
    }
    public function onModify() {
    }
    public function onDelete() {
    }
}

?>