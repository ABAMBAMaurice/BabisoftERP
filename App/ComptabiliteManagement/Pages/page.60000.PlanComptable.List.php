<?php
/**
 * Descripteur métadonnées — Plan Comptable (Liste)
 *
 * Ce fichier remplace l'ancienne classe PHP PlanComptableList.
 * Il retourne un tableau pur : aucun développement du noyau n'est nécessaire.
 * PageRegistry le découvre automatiquement et MetadataPageEngine le sert.
 *
 * Route API : GET /meta/pages/60000
 */
return [
    // ── Identification ────────────────────────────────────────────────────
    'id'          => 60000,
    'name'        => 'PlanComptableList',
    'type'        => 'List',
    'caption'     => 'Plan Comptable SYSCOA/OHADA',
    'sourceTable' => 'CompteComptable',

    // ── Droits & navigation ───────────────────────────────────────────────
    'editable'    => true,
    'allowInsert' => true,
    'allowDelete' => false,   // un compte utilisé ne peut pas être supprimé
    'cardPage'    => 60001,

    // ── Recherche & tri ───────────────────────────────────────────────────
    'searchFields' => ['Numero', 'Intitule'],
    'defaultSort'  => [['field' => 'Numero', 'dir' => 'ASC']],
    'pageSize'     => 100,
    'key'          => ['Numero'],

    // ── Champs calculés à demander au moteur lors du rendu ────────────────
    'calcFields' => ['Solde_Debit', 'Solde_Credit', 'Nb_Ecritures'],

    // ── Layout ───────────────────────────────────────────────────────────
    'layout' => [
        [
            'type'    => 'repeater',
            'name'    => 'comptes',
            'caption' => 'Plan Comptable',
            'fields'  => [
                ['field' => 'Numero',       'caption' => 'N°',          'editable' => false, 'width' => 90,  'style' => 'strong'],
                ['field' => 'Intitule',     'caption' => 'Intitulé',    'editable' => true,  'width' => 350],
                ['field' => 'Classe',       'caption' => 'Cl.',         'editable' => false, 'width' => 40,  'align' => 'center'],
                ['field' => 'Type',         'caption' => 'Type',        'editable' => true,  'width' => 120],
                ['field' => 'Est_Actif',    'caption' => 'Actif',       'editable' => true,  'width' => 60,  'align' => 'center'],
                ['field' => 'Solde_Debit',  'caption' => 'Débit cumulé','editable' => false, 'width' => 140, 'align' => 'right', 'flowField' => true],
                ['field' => 'Solde_Credit', 'caption' => 'Crédit cumulé','editable'=> false, 'width' => 140, 'align' => 'right', 'flowField' => true],
                ['field' => 'Nb_Ecritures', 'caption' => 'Écritures',   'editable' => false, 'width' => 80,  'align' => 'right', 'flowField' => true],
            ],
        ],
    ],

    // ── Actions ──────────────────────────────────────────────────────────
    'actions' => [
        [
            'name'    => 'GrandLivre',
            'caption' => 'Grand livre',
            'icon'    => 'book',
            'style'   => 'inverse-dark',
            'type'    => 'report',
            'handler' => 'BilanManagement::getGrandLivre',
            'params'  => [
                ['source' => 'record', 'field'  => 'Numero'],
                ['source' => 'post',   'param'  => 'date_debut', 'default' => 'Y-01-01'],
                ['source' => 'post',   'param'  => 'date_fin',   'default' => 'Y-m-d'],
            ],
        ],
        [
            'name'    => 'Balance',
            'caption' => 'Balance de vérification',
            'icon'    => 'scale',
            'style'   => 'inverse-dark',
            'type'    => 'report',
            'handler' => 'BilanManagement::getBalance',
            'params'  => [
                ['source' => 'post', 'param' => 'date_debut', 'default' => 'Y-01-01'],
                ['source' => 'post', 'param' => 'date_fin',   'default' => 'Y-m-d'],
            ],
        ],
        [
            'name'    => 'Bilan',
            'caption' => 'Bilan SYSCOA',
            'icon'    => 'file-text',
            'style'   => 'inverse-primary',
            'type'    => 'report',
            'handler' => 'BilanManagement::getBilan',
            'params'  => [
                ['source' => 'post', 'param' => 'date', 'default' => 'Y-m-d'],
            ],
        ],
        [
            'name'    => 'CompteResultat',
            'caption' => 'Compte de résultat',
            'icon'    => 'trending-up',
            'style'   => 'inverse-primary',
            'type'    => 'report',
            'handler' => 'BilanManagement::getCompteResultat',
            'params'  => [
                ['source' => 'post', 'param' => 'date_debut', 'default' => 'Y-01-01'],
                ['source' => 'post', 'param' => 'date_fin',   'default' => 'Y-m-d'],
            ],
        ],
    ],
];
?>
