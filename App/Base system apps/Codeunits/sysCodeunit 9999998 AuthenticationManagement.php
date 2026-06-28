<?php
    class AuthenticationManagement{
        public static function auth($token, bool $bypassBlock = false){
            if (empty($token)) {
                Security::logSecurityEvent('auth_empty_token', ['ip' => $_SERVER['REMOTE_ADDR']]);
                UnAuthorized('Clé API manquante');
                return false;
            }
            
            // Validation du format du token
            if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
                Security::logSecurityEvent('auth_invalid_token_format', ['ip' => $_SERVER['REMOTE_ADDR']]);
                UnAuthorized('Format de token invalide');
                return false;
            }
            
            $session = new Session();
            $currentTime = date('Y-m-d H:i:s');
            
            $session->setFilter('start_time','<=%1', $currentTime);
            $session->setFilter('end_time','>%1', $currentTime);
            $session->setFilter('session_token','=%1', $token);
            
            if ($session->Find()) {
                // Mettre à jour la dernière activité
                $session->Validate('last_activity', $currentTime);
                $session->Modify();

                // Vérification de l'accès tenant (abonnement actif)
                $user = new User();
                if ($user->get($session->user_email->value) && !IsNullOrEmptyString($user->Tenant_code->value)) {
                    $tenant = new Tenant();
                    if ($tenant->get($user->Tenant_code->value)) {

                        // Mise à jour automatique du statut si la date est dépassée
                        if (!IsNullOrEmptyString($tenant->Date_expiration->value)
                            && $tenant->Date_expiration->value < date('Y-m-d')
                            && $tenant->Statut->value === 'Actif') {
                            $tenant->Validate('Statut', 'Expiré');
                            $tenant->Modify();
                        }

                        if (!$bypassBlock) {
                            if ($tenant->Statut->value === 'Suspendu') {
                                db->commit();
                                http_response_code(402);
                                die(json_encode([
                                    'status'  => 402,
                                    'message' => 'Votre compte a été suspendu. Contactez le support KILIE IMMO.',
                                    'code'    => 'ACCOUNT_SUSPENDED',
                                ]));
                            }

                            if ($tenant->Statut->value === 'Expiré') {
                                db->commit();
                                http_response_code(402);
                                die(json_encode([
                                    'status'     => 402,
                                    'message'    => 'Votre abonnement a expiré. Renouvelez votre licence pour continuer.',
                                    'code'       => 'SUBSCRIPTION_EXPIRED',
                                    'expired_at' => $tenant->Date_expiration->value,
                                ]));
                            }
                        }
                    }
                }

                return true;
            } else {
                Security::logSecurityEvent('auth_invalid_session', ['token' => substr($token, 0, 8) . '...']);
                UnAuthorized('Session invalide ou expirée');
                return false;
            }
        }

        public static function native_client_auth($token){
            if (empty($token)) {
                Security::logSecurityEvent('native_auth_empty_token', ['ip' => $_SERVER['REMOTE_ADDR']]);
                UnAuthorized('Clé API manquante');
                return false;
            }
            
            $session = new Session();
            $currentTime = date('Y-m-d H:i:s');

            $session->setFilter('start_time','<=%1', $currentTime);
            $session->setFilter('end_time','>%1', $currentTime);
            $session->setFilter('session_token','=%1', $token);

            if ($session->Find()) {
                // Vérifier que l'utilisateur a toujours l'accès mobile actif
                $user = new User();
                if ($user->get($session->user_email->value) && $user->Mobile_access->value != '1') {
                    Security::logSecurityEvent('native_auth_mobile_denied', ['email' => $session->user_email->value]);
                    UnAuthorized('Accès mobile révoqué pour ce compte');
                    return false;
                }
                return true;
            } else {
                Security::logSecurityEvent('native_auth_invalid_session', ['token' => substr($token, 0, 8) . '...']);
                UnAuthorized('Session invalide ou expirée');
                return false;
            }
        }

        public static function is_authorized_to_view($username, $page_ID){
            $user = new User();
            $user->get($username);
            $profile = new Profile();
            $profile->get($user->Profile);
            if($profile->Code == 'SUPER')
                return true;
        
            //LicenceManager::verifyLicence();            
            $authorization = new Authorization();
            $authorization->setRange('Profile', $profile->Code);
            $authorization->setRange('Page_Id', $page_ID);
            if($authorization->FindFirst()){
               if($authorization->Viewing->value == 1){
                    return true;
                }
                else{
                    Error('Vous n\'avez pas les droits pour afficher à cette page');
                }
            }
            else{
                Error('Vous n\'avez pas les droits pour acceder à cette page');
            }

        }
        public static function is_authorized_to_insert($username, $page_ID){
            $user = new User();
            $user->get($username);
            $profile = new Profile();
            $profile->get($user->Profile->value);
            if($profile->Code == 'SUPER')
                return true;

            //LicenceManager::verifyLicence();
            $authorization = new Authorization();
            $authorization->setRange('Profile', $profile->Code);
            $authorization->setRange('Page_Id', $page_ID);
            if($authorization->FindFirst()){
               if($authorization->Inserting->value){
                    return true;
                }
                else{
                    Error('Vous n\'avez pas les droits pour insérer à cette page');
                }
            }
            else{
                Error('Vous n\'avez pas les droits pour acceder à cette page');
            }

        }
        public static function is_authorized_to_delete($username, $page_ID){
            $user = new User();
            $user->get($username);
            $profile = new Profile();
            $profile->get($user->Profile);
            if($profile->Code == 'SUPER')
                return true;

            //LicenceManager::verifyLicence();
            $authorization = new Authorization();
            $authorization->setRange('Profile', $profile->Code);
            $authorization->setRange('Page_Id', $page_ID);
            if($authorization->FindFirst()){
               if($authorization->Deleting->value){
                    return true;
                }
                else{
                    Error('Vous n\'avez pas les droits pour supprimer à cette page');
                }
            }
            else{
                Error('Vous n\'avez pas les droits pour acceder à cette page');
            }

        }
        public static function is_authorized_to_modify($username, $page_ID){
            $user = new User();
            $user->get($username);
            $profile = new Profile();
            $profile->get($user->Profile);
            if($profile->Code == 'SUPER')
                return true;

            //LicenceManager::verifyLicence();
            $authorization = new Authorization();
            $authorization->setRange('Profile', $profile->Code);
            $authorization->setRange('Page_Id', $page_ID);
            if($authorization->FindFirst()){
               if($authorization->Modifying->value){
                    return true;
                }
                else{
                    Error('Vous n\'avez pas les droits pour modifier à cette page');
                }
            }
            else{
                Error('Vous n\'avez pas les droits pour acceder à cette page');
            }

        }

        

        public static function generateCSRFToken($ip_address){
            $currentTime = date('Y-m-d H:i:s');
            $token = bin2hex(random_bytes(32));

            $xCsrfToken = new csrfToken();
            while($xCsrfToken->get($token)){
                $token = bin2hex(random_bytes(32));
            }

            $csrfToken = new csrfToken();

            /*$csrfToken->setFilter('ip_address','=%1', $ip_address);
            $csrfToken->setFilter('start_time','<=%1', $currentTime);
            $csrfToken->setFilter('end_time','>%1', $currentTime);
            if(!$csrfToken->FindFirst()){

                $hCsrfToken = new csrfToken();
                $hCsrfToken->setRange('ip_address', $ip_address);
                if($hCsrfToken->FindSet()){
                    foreach($hCsrfToken->recordSet as $record)
                        $record->Delete();
                }*/

                $csrfToken->Validate('token',$token);
                $csrfToken->Validate('ip_address',$ip_address);
                $csrfToken->Validate('start_time',$currentTime);
                $csrfToken->Validate('end_time',calcDate('1H', $currentTime));
                $csrfToken->Insert();
            /*}else{
                $csrfToken->Validate('token',$token);
                $csrfToken->Validate('start_time',$currentTime);
                $csrfToken->Validate('end_time',calcDate('1H', $currentTime));
                $csrfToken->Modify();           
            }*/
            db->commit();
            return $csrfToken->token->value;     
        }

        public static function verifyCSRFToken($token, $ip_address){
            $currentTime = date('Y-m-d H:i:s');
            $csrfToken = new csrfToken();
            $csrfToken->setFilter('token','=%1', $token);
            $csrfToken->setFilter('ip_address','=%1', $ip_address);
            $csrfToken->setFilter('start_time','<=%1', $currentTime);
            $csrfToken->setFilter('end_time','>%1', $currentTime);
            if($csrfToken->FindFirst()){
                return true;
            }
            else{
                Security::logSecurityEvent('invalid_csrf_token', ['ip' => $ip_address]);
                UnAuthorized('Jeton CSRF invalide ou expiré');
                return false;
            }
        }

        // ── Contrôle d'accès module par plan ────────────────────────────────
        public static function checkModule(string $token, string $moduleField): void {
            $tc = getSessionTenant($token);
            if (IsNullOrEmptyString($tc)) return;
            $tenant = new Tenant();
            if (!$tenant->get($tc)) return;
            $plan = new PlanAbonnement();
            if (!$plan->get($tenant->Plan->value)) return;
            if ($plan->{$moduleField}->value != '1') {
                db->commit();
                http_response_code(403);
                die(json_encode([
                    'status'      => 403,
                    'message'     => 'Ce module n\'est pas inclus dans votre plan. Passez à un plan supérieur pour y accéder.',
                    'code'        => 'MODULE_NOT_INCLUDED',
                    'module'      => $moduleField,
                    'plan_actuel' => $tenant->Plan->value,
                ]));
            }
        }
    }
?>
