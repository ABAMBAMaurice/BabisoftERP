<?php
    enum FieldType: string{
        case text = 'VARCHAR';
        case integer = 'INT';
        case date = 'DATE';
        case time = 'TIME';
        case datetime = 'DATETIME';
        case decimal = 'DECIMAL';
        case boolean = 'BOOLEAN';

        // Static methods to define each case with a message format
        public static function text($length, $details=''):string
        {
            return 'VARCHAR('.$length.') '.$details;
        }

        public static function integer($details=''):string{
            return 'INT'.' '.$details;
        }
        public static function date($details=''):string{
            return 'DATE'.' '.$details;
        }
        public static function time($details=''):string{
            return 'TIME'.' '.$details;
        }
        public static function datetime($details=''):string{
            return 'DATETIME'.' '.$details;
        }
        public static function decimal($details=''):string{
            return 'DECIMAL'.' '.$details;
        }
        public static function boolean($details=''):string{
            return 'BOOLEAN'.' '.$details;
        }

    }

?>
