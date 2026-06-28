<?php 

    
class NoSerie extends Table {
    public function __construct()
    {
        parent::__construct(id: 9999996, name: 'noseries');
        
        $this->field(1, 'Code', FieldType::text(30),caption:'Code', onValidate: function(){                        
            
        });
        $this->field(2,  'No_debut', FieldType::text(30),caption:'N° début', onValidate: function(){                        
            
        });
        $this->field( 3,  'No_fin', FieldType::text(30),caption:'N° fin', onValidate: function(){                        
            
        });
        $this->field(4,  'Last_Used_No', FieldType::text(30),caption:'Dernier N° utilisé', onValidate: function(){                        
            
        });
        $this->field(5,  'Start_date', FieldType::date(),caption:'Date début', onValidate: function(){                        
            
        });
        $this->field(6,  'End_date', FieldType::date(),caption:'Date fin', onValidate: function(){                        
            
        });
        $this->field(7,  'Prefix', FieldType::text(20),caption:'Préfix', onValidate: function(){                        
            
        });
        $this->field(8,  'Suffix', FieldType::text(20),caption:'Suffix', onValidate: function(){                        
            
        });
        $this->field(9,  'IsAuto', FieldType::boolean(),caption:'Automatique', onValidate: function(){                        
            
        });

        $this->Keys('Code');
    }
    public function onInsert() {
        $this->Validate('Last_Used_No', $this->No_debut->value);
    }
    public function onModify() {
        if(isNullOrEmptyString($this->Last_Used_No->value))
            $this->Validate('Last_Used_No', $this->No_debut->value);
    }
    public function onDelete() {
    }
}

?>