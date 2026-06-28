<?php
class AdminAuthManagement {

    // Vérifie si la requête courante est authentifiée en tant qu'admin éditeur
    public static function isAuth(): bool {
        $token = self::getAdminToken();
        if (!$token) return false;
        $session = new AdminSession();
        $session->setRange('Token', $token);
        if (!$session->FindFirst()) return false;
        if ($session->Expires_at->value < date('Y-m-d H:i:s')) {
            $session->Delete();
            db->commit();
            return false;
        }
        $admin = new AdminUser();
        return $admin->get($session->Admin_email->value) && $admin->Is_active->value == '1';
    }

    // Retourne l'email de l'admin connecté, ou '' si non auth
    public static function getAdminEmail(): string {
        $token = self::getAdminToken();
        if (!$token) return '';
        $session = new AdminSession();
        $session->setRange('Token', $token);
        if (!$session->FindFirst()) return '';
        return $session->Admin_email->value;
    }

    // Setup initial : crée le premier SUPERADMIN — bloqué si un admin existe déjà
    public static function setup(array $data): string {
        header('Content-Type: application/json');

        $check = new AdminUser();
        if ($check->FindSet())
            return json_encode(['status' => 403, 'message' => 'Un administrateur existe déjà. Utilisez /admin/auth/login.']);

        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $name     = trim($data['full_name'] ?? $email);

        if (IsNullOrEmptyString($email) || IsNullOrEmptyString($password))
            return json_encode(['status' => 400, 'message' => 'Email et mot de passe requis']);
        if (strlen($password) < 8)
            return json_encode(['status' => 400, 'message' => 'Mot de passe trop court (min 8 caractères)']);

        $admin = new AdminUser();
        $admin->Validate('Email',     $email);
        $admin->Validate('Full_name', $name);
        $admin->Validate('Password',  Security::hashPassword($password));
        $admin->Validate('Role',      'SUPERADMIN');
        $admin->Validate('Is_active', '1');
        $admin->Insert();
        db->commit();

        return json_encode(['status' => 201, 'message' => 'Super-administrateur créé. Vous pouvez maintenant vous connecter sur /admin/']);
    }

    public static function login(array $data): string {
        header('Content-Type: application/json');
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        if (IsNullOrEmptyString($email) || IsNullOrEmptyString($password))
            return json_encode(['status' => 400, 'message' => 'Email et mot de passe requis']);

        $admin = new AdminUser();
        if (!$admin->get($email))
            return json_encode(['status' => 401, 'message' => 'Identifiants incorrects']);
        if ($admin->Is_active->value != '1')
            return json_encode(['status' => 403, 'message' => 'Compte désactivé']);
        if (!Security::verifyPassword($password, $admin->Password->value))
            return json_encode(['status' => 401, 'message' => 'Identifiants incorrects']);

        // Supprimer les anciens tokens de cette admin
        $old = new AdminSession();
        $old->setRange('Admin_email', $email);
        if ($old->FindSet()) {
            foreach ($old->recordSet as $rec) {
                $s = new AdminSession();
                if ($s->get($rec->Token->value)) $s->Delete();
            }
        }

        $token = Security::generateSecureToken(64);
        $sess  = new AdminSession();
        $sess->Validate('Token',       $token);
        $sess->Validate('Admin_email', $email);
        $sess->Validate('Expires_at',  date('Y-m-d H:i:s', strtotime('+8 hours')));
        $sess->Validate('Ip_address',  $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $sess->Insert();

        db->commit();
        return json_encode([
            'status' => 200,
            'message' => 'Connecté',
            'result'  => [
                'token'     => $token,
                'email'     => $admin->Email->value,
                'full_name' => $admin->Full_name->value,
                'role'      => $admin->Role->value,
            ],
        ]);
    }

    public static function logout(): string {
        header('Content-Type: application/json');
        $token = self::getAdminToken();
        if ($token) {
            $sess = new AdminSession();
            if ($sess->get($token)) $sess->Delete();
            db->commit();
        }
        return json_encode(['status' => 200, 'message' => 'Déconnecté']);
    }

    // Lister les admins (SUPERADMIN uniquement)
    public static function listerAdmins(): string {
        header('Content-Type: application/json');
        if (!self::isAuth()) { http_response_code(403); return json_encode(['status'=>403,'message'=>'Accès refusé']); }
        if (!self::isSuperAdmin()) { http_response_code(403); return json_encode(['status'=>403,'message'=>'Réservé au super-admin']); }

        $admin  = new AdminUser();
        $result = [];
        if ($admin->FindSet())
            foreach ($admin->recordSet as $rec)
                $result[] = ['Email' => $rec->Email->value, 'Full_name' => $rec->Full_name->value, 'Role' => $rec->Role->value, 'Is_active' => $rec->Is_active->value];

        db->commit();
        return json_encode(['status' => 200, 'result' => $result]);
    }

    public static function creerAdmin(array $data): string {
        header('Content-Type: application/json');
        if (!self::isAuth()) { http_response_code(403); return json_encode(['status'=>403,'message'=>'Accès refusé']); }
        if (!self::isSuperAdmin()) { http_response_code(403); return json_encode(['status'=>403,'message'=>'Réservé au super-admin']); }

        $email = trim($data['email'] ?? '');
        if (IsNullOrEmptyString($email)) return json_encode(['status'=>400,'message'=>'Email requis']);
        $admin = new AdminUser();
        if ($admin->get($email)) return json_encode(['status'=>409,'message'=>'Email déjà utilisé']);

        $admin->Validate('Email',     $email);
        $admin->Validate('Full_name', $data['full_name'] ?? $email);
        $admin->Validate('Password',  Security::hashPassword($data['password'] ?? 'Admin@2024!'));
        $admin->Validate('Role',      in_array($data['role'] ?? '', ['ADMIN','SUPERADMIN']) ? $data['role'] : 'ADMIN');
        $admin->Insert();
        db->commit();
        return json_encode(['status' => 201, 'message' => 'Admin créé']);
    }

    public static function modifierAdmin(string $email, array $data): string {
        header('Content-Type: application/json');
        if (!self::isAuth()) { http_response_code(403); return json_encode(['status'=>403,'message'=>'Accès refusé']); }
        if (!self::isSuperAdmin()) { http_response_code(403); return json_encode(['status'=>403,'message'=>'Réservé au super-admin']); }

        $admin = new AdminUser();
        if (!$admin->get($email)) { http_response_code(404); return json_encode(['status'=>404,'message'=>'Admin introuvable']); }

        if (!IsNullOrEmptyString($data['full_name'] ?? '')) $admin->Validate('Full_name', $data['full_name']);
        if (!IsNullOrEmptyString($data['role'] ?? ''))      $admin->Validate('Role', $data['role']);
        if (isset($data['is_active']))                       $admin->Validate('Is_active', $data['is_active'] ? '1' : '0');
        if (!IsNullOrEmptyString($data['password'] ?? ''))  $admin->Validate('Password', Security::hashPassword($data['password']));
        $admin->Modify();
        db->commit();
        return json_encode(['status' => 200, 'message' => 'Admin modifié']);
    }

    public static function supprimerAdmin(string $email): string {
        header('Content-Type: application/json');
        if (!self::isAuth()) { http_response_code(403); return json_encode(['status'=>403,'message'=>'Accès refusé']); }
        if (!self::isSuperAdmin()) { http_response_code(403); return json_encode(['status'=>403,'message'=>'Réservé au super-admin']); }
        if ($email === self::getAdminEmail()) return json_encode(['status'=>400,'message'=>'Impossible de supprimer son propre compte']);

        $admin = new AdminUser();
        if (!$admin->get($email)) { http_response_code(404); return json_encode(['status'=>404,'message'=>'Admin introuvable']); }
        $admin->Delete();
        db->commit();
        return json_encode(['status' => 200, 'message' => 'Admin supprimé']);
    }

    // Vérifie si l'admin connecté a le rôle SUPERADMIN
    private static function isSuperAdmin(): bool {
        $email = self::getAdminEmail();
        if (!$email) return false;
        $admin = new AdminUser();
        if (!$admin->get($email)) return false;
        return $admin->Role->value === 'SUPERADMIN';
    }

    private static function getAdminToken(): ?string {
        $headers = getallheaders();
        $auth = $headers['X-Admin-Token'] ?? $headers['x-admin-token'] ?? '';
        if (!empty($auth)) return $auth;
        // Fallback: Bearer token
        $bearer = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (preg_match('/Bearer\s+(.+)$/i', $bearer, $m)) return $m[1];
        return null;
    }
}
?>
