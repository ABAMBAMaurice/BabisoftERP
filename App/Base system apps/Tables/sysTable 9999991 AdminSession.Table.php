<?php
class AdminSession extends Table {
    public function __construct() {
        parent::__construct(9999991, 'admin_session');

        $this->field(1, 'Token',       FieldType::text(128), caption: 'Token');
        $this->field(2, 'Admin_email', FieldType::text(100), caption: 'Email admin');
        $this->field(3, 'Expires_at',  FieldType::datetime(), caption: 'Expiration');
        $this->field(4, 'Ip_address',  FieldType::text(50),  caption: 'Adresse IP');

        $this->Keys('Token');
    }
    public function onInsert() {
    }
    public function onModify() {
    }
    public function onDelete() {
    }
}
?>
