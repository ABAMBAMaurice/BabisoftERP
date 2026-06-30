<?php

/**
 * Champ calculé non persisté en base de données.
 * La valeur est calculée à la demande via Table::CalcFields('NomDuChamp').
 *
 * Usage dans une Table :
 *   $this->flowField(10, 'Nb_Autorisations', FieldType::integer(),
 *       CalcFormula::Count(Authorization::class, ['Profile' => 'Code']),
 *       caption: 'Nb autorisations'
 *   );
 *
 * Usage dans une Page / Codeunit :
 *   $profile->CalcFields('Nb_Autorisations');
 *   echo $profile->Nb_Autorisations->value; // → 5
 *  require('Field/Field.class.php');
 */
class FlowField extends Field {
    public readonly CalcFormula $calcFormula;

    public function __construct(int $id, string $name, string $type, CalcFormula $calcFormula, ?string $caption = null) {
        parent::__construct(
            id:           $id,
            name:         $name,
            type:         $type,
            tableRelation: null,
            editabled:    false,
            enabled:      true,
            onValidate:   null,
            onLookUp:     null,
            caption:      $caption ?? $name,
            visible:      true,
            options:      null
        );
        $this->calcFormula = $calcFormula;
    }
}

?>
