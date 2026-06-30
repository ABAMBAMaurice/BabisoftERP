<?php
    class UserManagement{

        public static function connexion($data){
            header('Content-type: application/json');

            // Normaliser les clés — le frontend envoie en minuscule, la validation attend PascalCase
            $data['Email']    = $data['Email']    ?? $data['email']    ?? '';
            $data['Password'] = $data['Password'] ?? $data['password'] ?? '';
            // Les clients mobiles envoient X-Mobile-Client: 1 et sont exemptés du CSRF
            // PHP normalise les headers custom via $_SERVER (HTTP_X_MOBILE_CLIENT) — méthode fiable
            $isMobile = ($_SERVER['HTTP_X_MOBILE_CLIENT'] ?? '') === '1';
            if (!$isMobile) {
                $hdrs = array_change_key_case(getallheaders(), CASE_LOWER);
                $data['csrf-token'] = $hdrs['csrf-token'] ?? '';
                $clientIp = ($_SERVER['REMOTE_ADDR'] === '::1') ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
                if (empty($data['csrf-token']) || !AuthenticationManagement::verifyCSRFToken($data['csrf-token'], $clientIp)) {
                    Security::logSecurityEvent('csrf-token_invalid', ['ip' => $clientIp]);
                    http_response_code(403);
                    return json_encode(array("status" => 403, "message" => "Token CSRF invalide"));
                }
            }

            $rules = [
                'Email'    => ['required' => true, 'email' => true, 'max_length' => 255],
                'Password' => ['required' => true, 'min_length' => 1]
            ];
            $validation = Security::validateInput($data, $rules);
            if ($validation !== true) {
                $errorMessages = end($validation);
                return json_encode(array("status" => 400, "message" => $errorMessages, "errors" => "Données invalides"));
            }

            $email    = Security::sanitizeInput($data['Email'], 'email');
            $password = $data['Password'];

            if (!Security::checkLoginAttempts($email)) {
                Security::logSecurityEvent('login_attempt_blocked', ['email' => $email]);
                return json_encode(array("status" => 429, "message" => "Trop de tentatives de connexion. Veuillez attendre avant de réessayer."));
            }
            
            
            $user = new User();
            if ($user->get($email)) {
                if (Security::verifyPassword($password, $user->Password->value)) {

                    // Bloquer les comptes désactivés
                    if ($user->Is_active->value != '1') {
                        Security::logSecurityEvent('login_blocked_inactive', ['email' => $email]);
                        return json_encode(["status" => 403, "message" => "Ce compte est désactivé. Contactez votre administrateur."]);
                    }

                    // Vérifier l'autorisation d'accès mobile
                    if ($isMobile && $user->Mobile_access->value != '1') {
                        Security::logSecurityEvent('login_mobile_denied', ['email' => $email]);
                        return json_encode(["status" => 403, "message" => "Accès mobile non autorisé pour ce compte. Contactez votre administrateur."]);
                    }

                    // Vérifier le statut du tenant (mise à jour auto + blocage des états définitifs)
                    if (!IsNullOrEmptyString($user->Tenant_code->value)) {
                        $tenantCheck = new Tenant();
                        if ($tenantCheck->get($user->Tenant_code->value)) {
                            // Mise à jour automatique si la date d'expiration est dépassée
                            if (!IsNullOrEmptyString($tenantCheck->Date_expiration->value)
                                && $tenantCheck->Date_expiration->value < date('Y-m-d')
                                && $tenantCheck->Statut->value === 'Actif') {
                                $tenantCheck->Validate('Statut', 'Expiré');
                                $tenantCheck->Modify();
                            }
                            $statutTenant = $tenantCheck->Statut->value;
                            // Annulé = état définitif, connexion interdite
                            if ($statutTenant === 'Annulé') {
                                Security::logSecurityEvent('login_blocked_cancelled', ['email' => $email, 'tenant' => $user->Tenant_code->value]);
                                http_response_code(402);
                                return json_encode(['status' => 402, 'message' => 'Votre abonnement a été annulé. Contactez le support KILIE IMMO.', 'code' => 'ACCOUNT_CANCELLED']);
                            }
                            // Suspendu et Expiré : la connexion est autorisée mais l'accès
                            // est restreint après login (géré côté frontend via /tenant).
                        }
                    }

                    if ($user->Profile->value == '') {
                        UserManagement::ensureDefaultProfile();
                        $user->Validate('Profile', 'USER');
                        $user->Modify();
                    }

                    $session3 = UserManagement::createSessionToken($email);

                    $profile     = new Profile();
                    $profileName = 'Utilisateur';
                    $dashboard   = 'dashboard';
                    if ($profile->get($user->Profile->value)) {
                        $profileName = $profile->Name->value;
                        $dashboard   = $profile->Dashboard->value ?: 'dashboard';
                    }

                    Security::recordLoginAttempt($email, true);
                    Security::logSecurityEvent('login_success', ['email' => $email]);

                    db->commit();
                    return json_encode([
                        "status"  => 200,
                        "message" => "success",
                        "result"  => [
                            "utilisateur" => [
                                "Email"       => $user->Email->value,
                                "Full_name"   => $user->Full_name->value,
                                "Profile"     => $profileName,
                                "dashboard"   => $dashboard,
                                "Tenant_code" => $user->Tenant_code->value,
                                "Is_admin"    => $user->Is_admin->value == '1',
                            ],
                            "token"      => $session3->session_token->value,
                            "csrf-token" => $data['csrf-token'] ?? '',
                            //"csrf-token" => Security::generateCSRFToken(),
                        ],
                    ]);
                } else {
                    Security::recordLoginAttempt($email, false);
                    Security::logSecurityEvent('login_failed', ['email' => $email, 'reason' => 'invalid_password']);
                    return json_encode(["status" => 401, "message" => "Mot de passe incorrect"]);
                }
            } else {
                Security::recordLoginAttempt($email, false);
                Security::logSecurityEvent('login_failed', ['email' => $email, 'reason' => 'user_not_found']);
                return json_encode(["status" => 401, "message" => "Utilisateur introuvable"]);
            }
        }

        public static function signUp($data){
            header('Content-type: application/json');

            // Normaliser les clés — accepte minuscule et PascalCase
            $data['Email']        = $data['Email']        ?? $data['email']        ?? '';
            $data['Full_name']    = $data['Full_name']     ?? $data['full_name']    ?? $data['fullName'] ?? '';
            $data['Password']     = $data['Password']      ?? $data['password']     ?? '';
            $data['Company_name'] = $data['Company_name']  ?? $data['company_name'] ?? $data['companyName'] ?? '';

            $isMobile = ($_SERVER['HTTP_X_MOBILE_CLIENT'] ?? '') === '1';
            if (!$isMobile) {
                $hdrs = array_change_key_case(getallheaders(), CASE_LOWER);
                $data['csrf-token'] = $hdrs['csrf-token'] ?? '';
                $clientIp = ($_SERVER['REMOTE_ADDR'] === '::1') ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];
                if (empty($data['csrf-token']) || !AuthenticationManagement::verifyCSRFToken($data['csrf-token'], $clientIp)) {
                    Security::logSecurityEvent('csrf-token_invalid', ['ip' => $clientIp]);
                    http_response_code(403);
                    return json_encode(array("status" => 403, "message" => "Token CSRF invalide"));
                }
            }

            $rules = [
                'Email'        => ['required' => true, 'email' => true, 'max_length' => 255],
                'Full_name'    => ['required' => true, 'min_length' => 2, 'max_length' => 100],
                'Password'     => ['required' => true, 'strong_password' => true],
                'Company_name' => ['required' => true, 'min_length' => 2, 'max_length' => 100],
            ];
            $validation = Security::validateInput($data, $rules);
            if ($validation !== true) {
                $errorMessages = end($validation);
                return json_encode(array("status" => 400, "message" => $errorMessages, "errors" => "Données invalides"));
            }

            $email       = Security::sanitizeInput($data['Email'], 'email');
            $fullName    = Security::sanitizeInput($data['Full_name'], 'string');
            $companyName = Security::sanitizeInput($data['Company_name'], 'string');
            $password    = $data['Password'];

            $user = new User();
            if ($user->get($email))
                return json_encode(array("status" => 409, "message" => "Un utilisateur avec cet email existe déjà"));

            // Créer le tenant de l'entreprise avec toutes les infos
            $tenantCode = self::generateTenantCode($companyName);
            $tenant     = new Tenant();
            if (!$tenant->get($tenantCode)) {
                $tenant->Validate('Code',            $tenantCode);
                $tenant->Validate('Nom',             $companyName);
                $tenant->Validate('Email',           $email);
                $tenant->Validate('Plan',            'Essai');
                $tenant->Validate('Date_expiration', date('Y-m-d', strtotime('+14 days')));
                if (!IsNullOrEmptyString($data['Telephone'] ?? ''))
                    $tenant->Validate('Telephone', Security::sanitizeInput($data['Telephone'], 'string'));
                if (!IsNullOrEmptyString($data['Ville'] ?? ''))
                    $tenant->Validate('Ville', Security::sanitizeInput($data['Ville'], 'string'));
                if (!IsNullOrEmptyString($data['Adresse'] ?? ''))
                    $tenant->Validate('Adresse', Security::sanitizeInput($data['Adresse'], 'string'));
                if (!IsNullOrEmptyString($data['Pays'] ?? ''))
                    $tenant->Validate('Pays', Security::sanitizeInput($data['Pays'], 'string'));
                if (!IsNullOrEmptyString($data['NCC'] ?? ''))
                    $tenant->Validate('Admin_email', $email);
                $tenant->Insert();
            }

            // Créer les paramètres entreprise (CompanyInfo) pour ce tenant
            $info = new CompanyInfo();
            if (!$info->get($tenantCode)) {
                $info->Validate('Code',             $tenantCode);
                $info->Validate('Company_name',     $companyName);
                $info->Validate('Email',            $email);
                $info->Validate('Tenant_code',      $tenantCode);
                if (!IsNullOrEmptyString($data['Telephone'] ?? ''))
                    $info->Validate('Telephone_mobile', Security::sanitizeInput($data['Telephone'], 'string'));
                if (!IsNullOrEmptyString($data['Adresse'] ?? ''))
                    $info->Validate('Address', Security::sanitizeInput($data['Adresse'], 'string'));
                if (!IsNullOrEmptyString($data['Ville'] ?? ''))
                    $info->Validate('Ville', Security::sanitizeInput($data['Ville'], 'string'));
                if (!IsNullOrEmptyString($data['Pays'] ?? ''))
                    $info->Validate('Pays', Security::sanitizeInput($data['Pays'], 'string'));
                if (!IsNullOrEmptyString($data['NCC'] ?? ''))
                    $info->Validate('Ncc', Security::sanitizeInput($data['NCC'], 'string'));
                $info->Insert();
            }

            UserManagement::ensureDefaultProfile();

            $user->Validate('Email',       $email);
            $user->Validate('Full_name',   $fullName);
            $user->Validate('Password',    Security::hashPassword($password));
            $user->Validate('Profile',     'USER');
            $user->Validate('Tenant_code', $tenantCode);
            $user->Validate('Is_admin',    '1');
            $user->Insert();

            $session3 = UserManagement::createSessionToken($email);
            Security::logSecurityEvent('user_registered', ['email' => $email]);

            db->commit();
            return json_encode(array(
                "status"  => 200,
                "message" => "success",
                "result"  => array(
                    "utilisateur" => array(
                        "Email"       => $email,
                        "Full_name"   => $fullName,
                        "Profile"     => "Utilisateur",
                        "dashboard"   => "dashboard",
                        "Tenant_code" => $tenantCode,
                        "Is_admin"    => true,
                    ),
                    "token"      => $session3->session_token->value,
                    "csrf-token" => getallheaders()['csrf-token'] ,
                    //"csrf-token" => Security::generateCSRFToken(),
                )
            ));
        }

        // ── Gestion des utilisateurs du tenant ──────────────────────────────

        public static function listerUsers(): string {
            header('Content-Type: application/json');
            $token = getAthorizationToken();
            if (!AuthenticationManagement::auth($token)) UnAuthorized('Session expirée');
            $tenantCode = getSessionTenant($token);

            $user   = new User();
            $user->setRange('Tenant_code', $tenantCode);
            $result = [];
            if ($user->FindSet())
                foreach ($user->recordSet as $rec)
                    $result[] = [
                        'Email'         => $rec->Email->value,
                        'Full_name'     => $rec->Full_name->value,
                        'Profile'       => $rec->Profile->value,
                        'Is_active'     => $rec->Is_active->value == '1',
                        'Is_admin'      => $rec->Is_admin->value == '1',
                        'Mobile_access' => $rec->Mobile_access->value == '1',
                    ];

            db->commit();
            return json_encode(['status' => 200, 'result' => $result]);
        }

        public static function creerUser(array $data): string {
            header('Content-Type: application/json');
            $token = getAthorizationToken();
            if (!AuthenticationManagement::auth($token)) UnAuthorized('Session expirée');
            if (!self::isTenantAdmin($token))
                return json_encode(['status' => 403, 'message' => 'Réservé à l\'administrateur du compte']);

            $tenantCode = getSessionTenant($token);
            $email      = trim($data['email'] ?? $data['Email'] ?? '');
            if (IsNullOrEmptyString($email)) return json_encode(['status' => 400, 'message' => 'Email requis']);

            // Vérifier la limite d'utilisateurs du plan
            $tenant = new Tenant();
            if ($tenant->get($tenantCode)) {
                $maxUsers = intval($tenant->Nb_users_max->value);
                if ($maxUsers > 0) {
                    $existing = new User();
                    $existing->setRange('Tenant_code', $tenantCode);
                    $nbUsers = $existing->FindSet() ? count($existing->recordSet) : 0;
                    if ($nbUsers >= $maxUsers)
                        return json_encode(['status' => 403, 'message' => "Limite atteinte : votre plan autorise {$maxUsers} utilisateur(s) maximum. Passez à un plan supérieur."]);
                }
            }

            $user = new User();
            if ($user->get($email)) return json_encode(['status' => 409, 'message' => 'Email déjà utilisé']);

            $fullName     = Security::sanitizeInput($data['Full_name'] ?? $data['full_name'] ?? $email, 'string');
            $password     = $data['Password'] ?? $data['password'] ?? '';
            if (strlen($password) < 8)
                return json_encode(['status' => 400, 'message' => 'Le mot de passe doit contenir au moins 8 caractères.']);
            $profile      = Security::sanitizeInput($data['Profile'] ?? $data['Profil'] ?? $data['profile'] ?? 'USER', 'string');
            $isAdmin      = isset($data['Is_admin']) ? ($data['Is_admin'] ? '1' : '0')
                          : (isset($data['is_admin']) ? ($data['is_admin'] ? '1' : '0') : '0');
            $mobileAccess = isset($data['Mobile_access']) ? ($data['Mobile_access'] ? '1' : '0')
                          : (isset($data['mobile_access']) ? ($data['mobile_access'] ? '1' : '0') : '0');

            UserManagement::ensureDefaultProfile();
            $user->Validate('Email',         $email);
            $user->Validate('Full_name',     $fullName);
            $user->Validate('Password',      Security::hashPassword($password));
            $user->Validate('Profile',       $profile);
            $user->Validate('Tenant_code',   $tenantCode);
            $user->Validate('Is_admin',      $isAdmin);
            $user->Validate('Mobile_access', $mobileAccess);
            $user->Insert();
            db->commit();
            return json_encode(['status' => 201, 'message' => 'Utilisateur créé']);
        }

        public static function modifierUser(string $email, array $data): string {
            header('Content-Type: application/json');
            $token = getAthorizationToken();
            if (!AuthenticationManagement::auth($token)) UnAuthorized('Session expirée');
            $tenantCode = getSessionTenant($token);

            $user = new User();
            if (!$user->get($email)) Error404('Utilisateur introuvable');
            if ($user->Tenant_code->value !== $tenantCode)
                return json_encode(['status' => 403, 'message' => 'Accès refusé']);

            // Seul l'admin peut changer Is_admin / Profile / Is_active / Mobile_access
            if (self::isTenantAdmin($token)) {
                $isAdmin = $data['Is_admin'] ?? $data['is_admin'] ?? null;
                if ($isAdmin !== null) $user->Validate('Is_admin', $isAdmin ? '1' : '0');

                $profile = $data['Profile'] ?? $data['Profil'] ?? $data['profile'] ?? null;
                if (!IsNullOrEmptyString($profile)) $user->Validate('Profile', $profile);

                $isActive = $data['Is_active'] ?? $data['is_active'] ?? null;
                if ($isActive !== null) $user->Validate('Is_active', $isActive ? '1' : '0');

                $mobile = $data['Mobile_access'] ?? $data['mobile_access'] ?? null;
                if ($mobile !== null) $user->Validate('Mobile_access', $mobile ? '1' : '0');
            }
            $fullName = $data['Full_name'] ?? $data['full_name'] ?? null;
            if (!IsNullOrEmptyString($fullName)) $user->Validate('Full_name', Security::sanitizeInput($fullName, 'string'));
            $user->Modify();
            db->commit();
            return json_encode(['status' => 200, 'message' => 'Utilisateur modifié']);
        }

        public static function supprimerUser(string $email): string {
            header('Content-Type: application/json');
            $token = getAthorizationToken();
            if (!AuthenticationManagement::auth($token)) UnAuthorized('Session expirée');
            if (!self::isTenantAdmin($token))
                return json_encode(['status' => 403, 'message' => 'Réservé à l\'administrateur du compte']);

            $tenantCode = getSessionTenant($token);
            $user = new User();
            if (!$user->get($email)) Error404('Utilisateur introuvable');
            if ($user->Tenant_code->value !== $tenantCode)
                return json_encode(['status' => 403, 'message' => 'Accès refusé']);

            // Vérifier qu'on ne supprime pas le dernier admin
            $sameUser = new Session();
            $sameUser->setRange('session_token', $token);
            if ($sameUser->FindFirst() && $sameUser->user_email->value === $email)
                return json_encode(['status' => 400, 'message' => 'Impossible de supprimer votre propre compte']);

            $user->Delete();
            db->commit();
            return json_encode(['status' => 200, 'message' => 'Utilisateur supprimé']);
        }

        public static function toggleMobileAccess(string $email): string {
            header('Content-Type: application/json');
            $token = getAthorizationToken();
            if (!AuthenticationManagement::auth($token)) UnAuthorized('Session expirée');
            if (!self::isTenantAdmin($token))
                return json_encode(['status' => 403, 'message' => 'Réservé à l\'administrateur du compte']);

            $tenantCode = getSessionTenant($token);
            $user = new User();
            if (!$user->get($email)) return json_encode(['status' => 404, 'message' => 'Utilisateur introuvable']);
            if ($user->Tenant_code->value !== $tenantCode)
                return json_encode(['status' => 403, 'message' => 'Accès refusé']);

            $newValue = $user->Mobile_access->value == '1' ? '0' : '1';
            $user->Validate('Mobile_access', $newValue);
            $user->Modify();
            db->commit();
            return json_encode(['status' => 200, 'mobile_access' => $newValue == '1', 'message' => $newValue == '1' ? 'Accès mobile activé' : 'Accès mobile désactivé']);
        }

        // ── Mot de passe / profil ─────────────────────────────────────────

        public static function changePassword(array $data): string {
            header('Content-Type: application/json');
            $token = getAthorizationToken();
            if (!AuthenticationManagement::auth($token)) UnAuthorized('Session expirée');

            $old_pwd = $data['old_password'] ?? '';
            $new_pwd = $data['new_password'] ?? '';
            if (empty($old_pwd) || empty($new_pwd))
                return json_encode(['status' => 400, 'message' => 'Ancien et nouveau mot de passe requis']);

            $session = new Session();
            $session->setRange('session_token', $token);
            if (!$session->FindFirst())
                return json_encode(['status' => 401, 'message' => 'Session invalide']);

            $user = new User();
            if (!$user->get($session->user_email->value))
                return json_encode(['status' => 404, 'message' => 'Utilisateur introuvable']);

            if (!Security::verifyPassword($old_pwd, $user->Password->value))
                return json_encode(['status' => 400, 'message' => 'Mot de passe actuel incorrect']);

            $rules = ['new_password' => ['required' => true, 'strong_password' => true]];
            $v = Security::validateInput($data, $rules);
            if ($v !== true)
                return json_encode(['status' => 400, 'message' => end($v)]);

            $user->Validate('Password', Security::hashPassword($new_pwd));
            $user->Modify();
            db->commit();
            return json_encode(['status' => 200, 'message' => 'Mot de passe modifié avec succès']);
        }

        public static function getCurrentUser(): string {
            header('Content-Type: application/json');
            $token = getAthorizationToken();
            if (!AuthenticationManagement::auth($token)) UnAuthorized('Session expirée');

            $session = new Session();
            $session->setRange('session_token', $token);
            if (!$session->FindFirst()) UnAuthorized('Session invalide');

            $user = new User();
            if (!$user->get($session->user_email->value)) Error404('Utilisateur introuvable');

            $tenantCode = $user->Tenant_code->value ?? '';
            $tenantData = [];

            if (!IsNullOrEmptyString($tenantCode)) {
                $tenant = new Tenant();
                if ($tenant->get($tenantCode)) {
                    $tenantData = [
                        'Plan'            => $tenant->Plan->value,
                        'Statut'          => $tenant->Statut->value,
                        'Date_expiration' => $tenant->Date_expiration->value,
                        'Nb_users_max'    => $tenant->Nb_users_max->value,
                        'Nb_projets_max'  => $tenant->Nb_projets_max->value,
                        'Nom_entreprise'  => $tenant->Nom->value,
                        'Montant_mensuel' => $tenant->Montant_mensuel->value,
                    ];
                }
            }

            db->commit();
            return json_encode(['status' => 200, 'result' => array_merge([
                'Email'       => $user->Email->value,
                'Full_name'   => $user->Full_name->value,
                'Profile'     => $user->Profile->value,
                'Tenant_code' => $tenantCode,
                'Is_admin'    => $user->Is_admin->value == '1',
            ], $tenantData)]);
        }

        // ── Helpers privés ───────────────────────────────────────────────

        private static function isTenantAdmin(string $token): bool {
            $session = new Session();
            $session->setRange('session_token', $token);
            if (!$session->FindFirst()) return false;
            $user = new User();
            if (!$user->get($session->user_email->value)) return false;
            return $user->Is_admin->value == '1';
        }

        private static function generateTenantCode(string $companyName): string {
            $base    = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $companyName));
            $base    = substr($base, 0, 8);
            $suffix  = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            $code    = 'T-' . ($base ?: 'CIE') . '-' . $suffix;
            // Vérifier unicité
            $tenant = new Tenant();
            while ($tenant->get($code)) {
                $suffix = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
                $code   = 'T-' . ($base ?: 'CIE') . '-' . $suffix;
            }
            return $code;
        }

        private static function ensureDefaultProfile(): void {
            $profile = new Profile();
            if (!$profile->get('USER')) {
                $profile->Validate('Code',      'USER');
                $profile->Validate('Name',      'Utilisateur');
                $profile->Validate('Caption',   'Utilisateur KilieBTP');
                $profile->Validate('Dashboard', 'dashboard');
                $profile->Insert();
            }
        }

        private static function createSessionToken($userEmail = null){
            $sessID = UserManagement::generateSessionToken();
            $session = new Session();
            $session->Validate('session_id',    $sessID);
            $session->Validate('session_token', $sessID);
            $session->Validate('ip_adress',     UserManagement::getUserIPAdress());
            $session->Validate('user_email',    $userEmail);
            $time = date('Y-m-d H:i:s');
            $session->Validate('start_time', $time);
            $session->Validate('end_time',   calcDate('+24H', $time));
            $session->Insert();
            return $session;
        }

        private static function getUserIPAdress(){
            $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
            foreach ($ipKeys as $key) {
                if (!empty($_SERVER[$key])) {
                    $ips = explode(',', $_SERVER[$key]);
                    $ip  = trim($ips[0]);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
                        return $ip;
                }
            }
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }

        public static function deconnexion(?string $token = null): string {
            header('Content-Type: application/json');
            if (IsNullOrEmptyString($token))
                return json_encode(['status' => 200, 'message' => 'Déconnecté']);

            $session = new Session();
            $session->setRange('session_token', $token);
            if ($session->FindSet())
                foreach ($session->recordSet as $rec) $rec->Delete();

            db->commit();
            return json_encode(['status' => 200, 'message' => 'Déconnecté avec succès']);
        }

        private static function generateSessionToken(){
            return Security::generateSecureToken(64);
        }
    }
?>
