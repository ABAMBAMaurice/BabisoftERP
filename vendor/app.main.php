<?php
    $baseDir = 'App';

    $directoryIterator = new RecursiveDirectoryIterator(
        $baseDir,
        RecursiveDirectoryIterator::SKIP_DOTS
    );

    $filterIterator = new RecursiveCallbackFilterIterator(
        $directoryIterator,
        function ($current, $key, $iterator) {
            // Sauter les dossiers nommés "Tables" (chargés par configs.php via glob)
            if ($current->isDir() && $current->getFilename() === 'Tables') {
                return false;
            }
            return true;
        }
    );

    $iterator = new RecursiveIteratorIterator($filterIterator);

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            require_once $file->getPathname();
        }
    }

require_once('vendor/Libs/sysCodeunit APIRestCaller.php');

// ── Abonnements EventDispatcher ────────────────────────────────────────────
// Enregistrés après le chargement complet de toutes les classes
EventDispatcher::subscribe('BailCree',              [ComptabiliteManagement::class,   'onBailCree']);
EventDispatcher::subscribe('BailCree',              [NotificationManagement::class,   'onBailCree']);
EventDispatcher::subscribe('BailSigne',             [GEDManagement::class,            'onBailSigne']);
EventDispatcher::subscribe('BailSigne',             [NotificationManagement::class,   'onBailSigne']);
EventDispatcher::subscribe('BailActive',            [EcheancierManagement::class,     'onBailActive']);
// BailResilie → unite.Statut=Disponible géré directement dans BailManagement::resilier()
EventDispatcher::subscribe('BailResilie',           [NotificationManagement::class,   'onBailResilie']);
EventDispatcher::subscribe('PaiementConfirme',      [EcheancierManagement::class,     'onPaiementConfirme']);
EventDispatcher::subscribe('PaiementConfirme',      [ComptabiliteManagement::class,   'onPaiementConfirme']);
EventDispatcher::subscribe('PaiementConfirme',      [QuittanceManagement::class,      'onPaiementConfirme']);
EventDispatcher::subscribe('PaiementConfirme',      [NotificationManagement::class,   'onPaiementConfirme']);
EventDispatcher::subscribe('EcheanceEnRetard',      [RelanceManagement::class,        'onEcheanceEnRetard']);
EventDispatcher::subscribe('MobileMoneyInitie',     [NotificationManagement::class,   'onMobileMoneyInitie']);
EventDispatcher::subscribe('OITermine',             [ComptabiliteManagement::class,   'onOITermine']);
EventDispatcher::subscribe('OITermine',             [NotificationManagement::class,   'onOITermine']);
EventDispatcher::subscribe('KYCValide',             [SolvabiliteManagement::class,    'onKYCValide']);
EventDispatcher::subscribe('KYCValide',             [NotificationManagement::class,   'onKYCValide']);
EventDispatcher::subscribe('ProspectConverti',      [PartieManagement::class,         'onProspectConverti']);
EventDispatcher::subscribe('ChargeCoproRepartie',   [EcheancierManagement::class,     'onChargeCoproRepartie']);
EventDispatcher::subscribe('ChargeCoproRepartie',   [NotificationManagement::class,   'onChargeCoproRepartie']);

// ── WhatsApp Business ──────────────────────────────────────────────────────
// Abonnements ajoutés après NotificationManagement pour ne pas bloquer les SMS/emails
EventDispatcher::subscribe('PaiementConfirme',      [WhatsAppManagement::class,        'onPaiementConfirme']);
EventDispatcher::subscribe('EcheanceEnRetard',      [WhatsAppManagement::class,        'onEcheanceEnRetard']);
EventDispatcher::subscribe('OICree',                [WhatsAppManagement::class,        'onOICree']);
EventDispatcher::subscribe('OIEnCours',             [WhatsAppManagement::class,        'onOIEnCours']);
EventDispatcher::subscribe('OITermine',             [WhatsAppManagement::class,        'onOITermine']);
?>


