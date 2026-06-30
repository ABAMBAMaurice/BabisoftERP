<?php

/**
 * Définit la formule de calcul d'un FlowField.
 * Inspiré du CalcFormula de Microsoft Business Central.
 *
 * Usage :
 *   CalcFormula::Count(Authorization::class, ['Profile' => 'Code'])
 *   CalcFormula::Sum(EcheanceLine::class, 'Montant', ['Bail_No' => 'No_'])
 *   CalcFormula::Exist(Session::class, ['user_email' => 'Email'])
 *   CalcFormula::Lookup(CompanyInfo::class, 'Company_name', ['Code' => 'Tenant_code'])
 */
class CalcFormula {
    public readonly string  $type;
    public readonly string  $tableClass;
    public readonly ?string $field;
    public readonly array   $filters;

    private function __construct(string $type, string $tableClass, ?string $field, array $filters) {
        $this->type       = $type;
        $this->tableClass = $tableClass;
        $this->field      = $field;
        $this->filters    = $filters;
    }

    /** Somme d'un champ numérique sur la table liée. */
    public static function Sum(string $tableClass, string $field, array $filters = []): self {
        return new self('Sum', $tableClass, $field, $filters);
    }

    /** Nombre d'enregistrements sur la table liée. */
    public static function Count(string $tableClass, array $filters = []): self {
        return new self('Count', $tableClass, null, $filters);
    }

    /** Booléen — vrai si au moins un enregistrement existe dans la table liée. */
    public static function Exist(string $tableClass, array $filters = []): self {
        return new self('Exist', $tableClass, null, $filters);
    }

    /** Valeur d'un champ sur le premier enregistrement trouvé dans la table liée. */
    public static function Lookup(string $tableClass, string $field, array $filters = []): self {
        return new self('Lookup', $tableClass, $field, $filters);
    }

    /** Valeur minimale d'un champ numérique sur la table liée. */
    public static function Min(string $tableClass, string $field, array $filters = []): self {
        return new self('Min', $tableClass, $field, $filters);
    }

    /** Valeur maximale d'un champ numérique sur la table liée. */
    public static function Max(string $tableClass, string $field, array $filters = []): self {
        return new self('Max', $tableClass, $field, $filters);
    }

    /** Moyenne d'un champ numérique sur la table liée. */
    public static function Average(string $tableClass, string $field, array $filters = []): self {
        return new self('Average', $tableClass, $field, $filters);
    }
}

?>
