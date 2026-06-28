<?php
class AdminPlanManagement {

    // ── Données par défaut ────────────────────────────────────────
    private static function defaults(): array {
        return [
            'Starter' => [
                'Nom'              => 'Starter',
                'Type'             => 'Starter',
                'Prix_mensuel'     => 25000,
                'Prix_annuel'      => 250000,
                'Max_users'        => 3,
                'Max_biens'        => 20,
                'Max_unites'       => 0,
                'Module_ged'       => '1',
                'Module_crm'       => '0',
                'Module_ventes'    => '0',
                'Module_copro'     => '0',
                'Module_ia'        => '0',
                'Module_reporting' => '0',
                'Api_access'       => '0',
                'Description'      => 'Idéal pour les petites agences. Gestion de base des biens et baux.',
            ],
            'Pro' => [
                'Nom'              => 'Pro',
                'Type'             => 'Pro',
                'Prix_mensuel'     => 75000,
                'Prix_annuel'      => 750000,
                'Max_users'        => 10,
                'Max_biens'        => 100,
                'Max_unites'       => 50,
                'Module_ged'       => '1',
                'Module_crm'       => '1',
                'Module_ventes'    => '1',
                'Module_copro'     => '0',
                'Module_ia'        => '0',
                'Module_reporting' => '1',
                'Api_access'       => '0',
                'Description'      => 'Pour les agences en croissance. CRM, ventes et reporting avancé.',
            ],
            'Enterprise' => [
                'Nom'              => 'Enterprise',
                'Type'             => 'Enterprise',
                'Prix_mensuel'     => 150000,
                'Prix_annuel'      => 1500000,
                'Max_users'        => 999,
                'Max_biens'        => 999999,
                'Max_unites'       => 9999,
                'Module_ged'       => '1',
                'Module_crm'       => '1',
                'Module_ventes'    => '1',
                'Module_copro'     => '1',
                'Module_ia'        => '1',
                'Module_reporting' => '1',
                'Api_access'       => '1',
                'Description'      => 'Solution complète sans limites. Tous les modules, IA et support dédié.',
            ],
        ];
    }

    // Initialise les plans par défaut si la table est vide
    public static function seederDefaults(): void {
        foreach (self::defaults() as $code => $data) {
            $p = new PlanAbonnement();
            if (!$p->get($code)) {
                $p->Validate('Code', $code);
                foreach ($data as $field => $value)
                    $p->Validate($field, $value);
                $p->Insert();
            }
        }
        db->commit();
    }

    // ── Lister les plans ──────────────────────────────────────────
    public static function lister(): string {
        header('Content-Type: application/json');
        if (!AdminAuthManagement::isAuth()) {
            http_response_code(401);
            return json_encode(['status' => 401, 'message' => 'Non authentifié']);
        }

        $check = new PlanAbonnement();
        if (!$check->FindFirst()) self::seederDefaults();

        $plan   = new PlanAbonnement();
        $result = [];
        if ($plan->FindSet()) {
            foreach ($plan->recordSet as $r) {
                $result[] = self::_serialize($r);
            }
        }
        db->commit();
        return json_encode(['status' => 200, 'result' => $result]);
    }

    // ── Modifier un plan ──────────────────────────────────────────
    public static function modifier(string $code, array $data): string {
        header('Content-Type: application/json');
        if (!AdminAuthManagement::isAuth()) {
            http_response_code(401);
            return json_encode(['status' => 401, 'message' => 'Non authentifié']);
        }
        // SUPERADMIN uniquement
        $email = AdminAuthManagement::getAdminEmail();
        $au = new AdminUser();
        if (!$au->get($email) || $au->Role->value !== 'SUPERADMIN') {
            http_response_code(403);
            return json_encode(['status' => 403, 'message' => 'Réservé au super-admin']);
        }

        $plan = new PlanAbonnement();
        if (!$plan->get($code)) {
            http_response_code(404);
            return json_encode(['status' => 404, 'message' => 'Plan introuvable']);
        }

        $stringFields = ['Nom', 'Description'];
        $decimalFielsds  = ['Prix_mensuel', 'Prix_annuel'];
        $intFields    = ['Max_users', 'Max_biens', 'Max_unites'];
        $boolFields   = ['Module_ged', 'Module_crm', 'Module_ventes', 'Module_copro',
                         'Module_ia', 'Module_reporting', 'Api_access', 'Is_actif'];

        foreach ($stringFields as $f)
            if (isset($data[$f]) && $data[$f] !== '') $plan->Validate($f, Security::sanitizeInput((string)$data[$f], 'string'));
        foreach ($intFields as $f)
            if (isset($data[$f])) $plan->Validate($f, (string)max(0, (int)$data[$f]));
        foreach ($boolFields as $f)
            if (isset($data[$f])) $plan->Validate($f, $data[$f] ? '1' : '0');
        
        foreach ($decimalFielsds as $f)
            if (isset($data[$f])) $plan->Validate($f, (string)max(0, (float)$data[$f]));
               
        $plan->Modify();
        db->commit();

        $plan2 = new PlanAbonnement();
        $plan2->get($code);
        return json_encode(['status' => 200, 'message' => 'Plan mis à jour', 'result' => self::_serialize($plan2)]);
    }

    // ── Créer un nouveau plan ─────────────────────────────────────
    public static function creer(array $data): string {
        header('Content-Type: application/json');
        if (!AdminAuthManagement::isAuth()) {
            http_response_code(401);
            return json_encode(['status' => 401, 'message' => 'Non authentifié']);
        }
        $email = AdminAuthManagement::getAdminEmail();
        $au = new AdminUser();
        if (!$au->get($email) || $au->Role->value !== 'SUPERADMIN') {
            http_response_code(403);
            return json_encode(['status' => 403, 'message' => 'Réservé au super-admin']);
        }

        $code = strtoupper(trim(Security::sanitizeInput($data['Code'] ?? '', 'string')));
        if (IsNullOrEmptyString($code))
            return json_encode(['status' => 400, 'message' => 'Le code du plan est requis']);
        if (!preg_match('/^[A-Z0-9_-]{2,30}$/', $code))
            return json_encode(['status' => 400, 'message' => 'Code invalide : majuscules, chiffres, tirets uniquement (2-30 caractères)']);

        $existing = new PlanAbonnement();
        if ($existing->get($code))
            return json_encode(['status' => 409, 'message' => "Un plan avec le code '$code' existe déjà"]);

        $plan = new PlanAbonnement();
        $plan->Validate('Code', $code);
        $plan->Validate('Nom',  Security::sanitizeInput($data['Nom'] ?? $code, 'string'));
        $plan->Validate('Type', $code);
        $plan->Validate('Prix_mensuel', (string)max(0, (int)($data['Prix_mensuel'] ?? 0)));
        $plan->Validate('Prix_annuel',  (string)max(0, (int)($data['Prix_annuel']  ?? 0)));
        $plan->Validate('Max_users',    (string)max(1, (int)($data['Max_users']    ?? 5)));
        $plan->Validate('Max_biens',    (string)max(1, (int)($data['Max_biens']    ?? 50)));
        $plan->Validate('Max_unites',   (string)max(0, (int)($data['Max_unites']   ?? 0)));

        $boolFields = ['Module_ged', 'Module_crm', 'Module_ventes', 'Module_copro',
                       'Module_ia', 'Module_reporting', 'Api_access'];
        foreach ($boolFields as $f)
            if (isset($data[$f])) $plan->Validate($f, $data[$f] ? '1' : '0');

        if (!IsNullOrEmptyString($data['Description'] ?? ''))
            $plan->Validate('Description', Security::sanitizeInput($data['Description'], 'string'));

        $plan->Insert();
        db->commit();

        $p2 = new PlanAbonnement();
        $p2->get($code);
        return json_encode(['status' => 201, 'message' => "Plan '$code' créé", 'result' => self::_serialize($p2)]);
    }

    // ── Supprimer un plan ─────────────────────────────────────────
    public static function supprimer(string $code): string {
        header('Content-Type: application/json');
        if (!AdminAuthManagement::isAuth()) {
            http_response_code(401);
            return json_encode(['status' => 401, 'message' => 'Non authentifié']);
        }
        $email = AdminAuthManagement::getAdminEmail();
        $au = new AdminUser();
        if (!$au->get($email) || $au->Role->value !== 'SUPERADMIN') {
            http_response_code(403);
            return json_encode(['status' => 403, 'message' => 'Réservé au super-admin']);
        }

        if (in_array($code, ['Starter', 'Pro', 'Enterprise']))
            return json_encode(['status' => 403, 'message' => "Le plan '$code' est un plan système, il ne peut pas être supprimé"]);

        $plan = new PlanAbonnement();
        if (!$plan->get($code)) {
            http_response_code(404);
            return json_encode(['status' => 404, 'message' => 'Plan introuvable']);
        }

        $tenant = new Tenant();
        $tenant->setRange('Plan', $code);
        if ($tenant->FindFirst())
            return json_encode(['status' => 409, 'message' => "Impossible : des tenants utilisent encore ce plan"]);

        $plan->Delete();
        db->commit();
        return json_encode(['status' => 200, 'message' => "Plan '$code' supprimé"]);
    }

    // ── Retourner les limites depuis la DB (appelé par AdminTenantManagement) ──
    public static function getLimits(string $planCode): array {
        $p = new PlanAbonnement();
        if ($p->get($planCode) && (int)$p->Max_users->value > 0) {
            return [
                'users'   => (int)$p->Max_users->value,
                'projets' => (int)$p->Max_biens->value,
                'prix'    => (int)$p->Prix_mensuel->value,
            ];
        }
        // Fallback hardcodé si table non initialisée
        return match ($planCode) {
            'Pro'        => ['users' => 10,  'projets' => 100,    'prix' => 75000],
            'Enterprise' => ['users' => 999, 'projets' => 999999, 'prix' => 150000],
            default      => ['users' => 3,   'projets' => 20,     'prix' => 25000],
        };
    }

    // ── Sérialiser un enregistrement ──────────────────────────────
    private static function _serialize(object $r): array {
        return [
            'Code'             => $r->Code->value,
            'Nom'              => $r->Nom->value,
            'Type'             => $r->Type->value,
            'Prix_mensuel'     => (int)$r->Prix_mensuel->value,
            'Prix_annuel'      => (int)$r->Prix_annuel->value,
            'Max_users'        => (int)$r->Max_users->value,
            'Max_biens'        => (int)$r->Max_biens->value,
            'Max_unites'       => (int)$r->Max_unites->value,
            'Module_ged'       => (bool)(int)$r->Module_ged->value,
            'Module_crm'       => (bool)(int)$r->Module_crm->value,
            'Module_ventes'    => (bool)(int)$r->Module_ventes->value,
            'Module_copro'     => (bool)(int)$r->Module_copro->value,
            'Module_ia'        => (bool)(int)$r->Module_ia->value,
            'Module_reporting' => (bool)(int)$r->Module_reporting->value,
            'Api_access'       => (bool)(int)$r->Api_access->value,
            'Description'      => $r->Description->value,
            'Is_actif'         => (bool)(int)$r->Is_actif->value,
        ];
    }
}
?>
