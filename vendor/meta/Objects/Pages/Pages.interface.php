<?php
    interface Pages
    {

        public function OnOpenPage();

        public function OnClosePage();

        function layout();
        function setActions();

        public function Validate($field, $value);

        public function show();
        
        public function OnNewRecord(& $record);

    }

?>