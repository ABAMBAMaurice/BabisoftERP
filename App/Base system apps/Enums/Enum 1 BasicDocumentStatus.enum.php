<?php 
    Enum BasicDocumentStatus:string{
            
        case _ = ' ';
        Case Open='Ouvert';
        Case Released='Lancé';
        Case Validated= 'Validé';
        


        public static function basicDocumentStatus(): array
        {
            return [
                self::_->value,
                self::Open->value,
                self::Released->value,
                self::Validated->value
            ];
        }
    }
?>