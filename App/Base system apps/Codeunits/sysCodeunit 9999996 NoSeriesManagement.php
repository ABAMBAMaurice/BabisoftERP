<?php
    class NoSeriesManagement{  
        public static function GetNextNo($NoSerieCode){    
            //var
                $NoSerie = New NoSerie();
            //

            //Récupération de la souche
            NoSeriesManagement::getNoSeries($NoSerieCode, $NoSerie);

            //Vérification de la validité de la souche
            NoSeriesManagement::checkNoSeriesDates($NoSerie);

            //Vérification de la disponibilité de N°
            if($NoSerie->No_fin->value != '')
                if($NoSerie->Last_Used_No->value == $NoSerie->No_fin->value)
                    Error('La souche est arrivée à la fin des numéros');
                       
            //Evaluation des paramètres de la souche
            return NoSeriesManagement::EvaluateNoSeriesParameter($NoSerie);

            

        }

        static function getNoSeries(string $NoSerieCode, NoSerie &$NoSerie ){               
            //Récupération de la souche
            if(!$NoSerie->get($NoSerieCode))
                Error('La souche de N° '. $NoSerieCode .' n\'existe pas');
        }

        static function checkNoSeriesDates(NoSerie &$NoSerie){            
            if($NoSerie->Start_date->value != '0000-00-00' && $NoSerie->Start_date->value != ''){                            
                if(DateCompare(Str2Date($NoSerie->Start_date->value), Str2Date(date('Y-m-d')),'>'))
                    Error('La date de début de la souche de N° n\'est pas encore atteinte');
            }         
            if($NoSerie->End_date->value != '0000-00-00' && $NoSerie->End_date->value != ''){                 
                if(DateCompare(Str2Date($NoSerie->End_date->value), Str2Date(date('Y-m-d')),'<'))
                    Error('La période de validité de la souche est terminée');
            }
        }
        
        // ── CRUD management (API) ─────────────────────────────────────────────

        public static function lister(): string {
            header('Content-Type: application/json');
            $token = getAthorizationToken();
            if (!AuthenticationManagement::auth($token)) UnAuthorized('Session expirée');

            static $meta = [
                'BIEN'  => ['module' => 'Gestion immobilière',     'ordre' => 1,  'description' => 'Biens immobiliers'],
                'UNITE' => ['module' => 'Gestion immobilière',     'ordre' => 1,  'description' => 'Unités / Lots locatifs'],
                'PHOTO' => ['module' => 'Gestion immobilière',     'ordre' => 1,  'description' => 'Photos de biens'],
                'CARAC' => ['module' => 'Gestion immobilière',     'ordre' => 1,  'description' => 'Caractéristiques de biens'],
                'DOCF'  => ['module' => 'Gestion immobilière',     'ordre' => 1,  'description' => 'Documents fonciers'],
                'PART'  => ['module' => 'Parties prenantes',       'ordre' => 2,  'description' => 'Parties (propriétaires, locataires…)'],
                'KYC'   => ['module' => 'Parties prenantes',       'ordre' => 2,  'description' => 'Documents KYC'],
                'CONT'  => ['module' => 'Parties prenantes',       'ordre' => 2,  'description' => 'Contacts de parties'],
                'BANQ'  => ['module' => 'Parties prenantes',       'ordre' => 2,  'description' => 'Comptes bancaires'],
                'BAL'   => ['module' => 'Baux & Contrats',         'ordre' => 3,  'description' => 'Baux / Contrats de location'],
                'ECH'   => ['module' => 'Baux & Contrats',         'ordre' => 3,  'description' => 'Échéances de loyer'],
                'EDL'   => ['module' => 'Baux & Contrats',         'ordre' => 3,  'description' => 'États des lieux'],
                'REVL'  => ['module' => 'Baux & Contrats',         'ordre' => 3,  'description' => 'Révisions de loyer'],
                'GART'  => ['module' => 'Baux & Contrats',         'ordre' => 3,  'description' => 'Garants de bail'],
                'PAI'   => ['module' => 'Paiements & Finance',     'ordre' => 4,  'description' => 'Paiements de loyer'],
                'QUIT'  => ['module' => 'Paiements & Finance',     'ordre' => 4,  'description' => 'Quittances de loyer'],
                'TMM'   => ['module' => 'Paiements & Finance',     'ordre' => 4,  'description' => 'Transactions Mobile Money'],
                'RELP'  => ['module' => 'Paiements & Finance',     'ordre' => 4,  'description' => 'Relevés propriétaire'],
                'RPLG'  => ['module' => 'Paiements & Finance',     'ordre' => 4,  'description' => 'Lignes de relevé propriétaire'],
                'ECR'   => ['module' => 'Comptabilité',            'ordre' => 5,  'description' => 'Écritures comptables'],
                'BUDG'  => ['module' => 'Comptabilité',            'ordre' => 5,  'description' => 'Budgets'],
                'OI'    => ['module' => 'Maintenance',             'ordre' => 6,  'description' => "Ordres d'intervention"],
                'PREST' => ['module' => 'Maintenance',             'ordre' => 6,  'description' => 'Prestataires'],
                'PHOI'  => ['module' => 'Maintenance',             'ordre' => 6,  'description' => "Photos d'ordres d'intervention"],
                'PROS'  => ['module' => 'CRM',                     'ordre' => 7,  'description' => 'Prospects'],
                'ACRM'  => ['module' => 'CRM',                     'ordre' => 7,  'description' => 'Activités CRM'],
                'OFFR'  => ['module' => 'Ventes immobilières',     'ordre' => 8,  'description' => "Offres d'acquisition"],
                'COMP'  => ['module' => 'Ventes immobilières',     'ordre' => 8,  'description' => 'Compromis de vente'],
                'MAND'  => ['module' => 'Ventes immobilières',     'ordre' => 8,  'description' => 'Mandats de vente'],
                'DOC'   => ['module' => 'GED',                     'ordre' => 9,  'description' => 'Documents GED'],
                'NOTIF' => ['module' => 'Notifications',           'ordre' => 10, 'description' => 'Notifications'],
                'SOLV'  => ['module' => 'Intelligence artificielle','ordre' => 11, 'description' => 'Analyses de solvabilité'],
                'RAPP'  => ['module' => 'Reporting',               'ordre' => 12, 'description' => 'Rapports générés'],
                'COPR'  => ['module' => 'Copropriété',             'ordre' => 13, 'description' => 'Copropriétés'],
                'LOT'   => ['module' => 'Copropriété',             'ordre' => 13, 'description' => 'Lots de copropriété'],
                'COTIS' => ['module' => 'Copropriété',             'ordre' => 13, 'description' => 'Appels de cotisation'],
            ];

            $ns = new NoSerie();
            $result = [];
            if ($ns->FindSet()) {
                foreach ($ns->recordSet as $r) {
                    $code = $r->Code->value;
                    $m = $meta[$code] ?? ['module' => 'Système', 'ordre' => 99, 'description' => ''];
                    $result[] = [
                        'Code'         => $code,
                        'Module'       => $m['module'],
                        'Ordre_module' => $m['ordre'],
                        'Description'  => $m['description'],
                        'No_debut'     => $r->No_debut->value,
                        'No_fin'       => $r->No_fin->value,
                        'Last_Used_No' => $r->Last_Used_No->value,
                        'Prefix'       => $r->Prefix->value,
                        'Suffix'       => $r->Suffix->value,
                        'Start_date'   => $r->Start_date->value,
                        'End_date'     => $r->End_date->value,
                    ];
                }
            }
            db->commit();

            usort($result, fn($a, $b) => $a['Ordre_module'] <=> $b['Ordre_module'] ?: strcmp($a['Code'], $b['Code']));

            return json_encode(['status' => 200, 'result' => $result]);
        }

        public static function creer(array $data): string {
            header('Content-Type: application/json');
            $token = getAthorizationToken();
            if (!AuthenticationManagement::auth($token)) UnAuthorized('Session expirée');

            $code = Security::sanitizeInput($data['Code'] ?? '', 'string');
            if (IsNullOrEmptyString($code))
                return json_encode(['status' => 400, 'message' => 'Le champ Code est obligatoire']);

            $ns = new NoSerie();
            if ($ns->get($code)) {
                db->commit();
                return json_encode(['status' => 409, 'message' => "La souche $code existe déjà"]);
            }
            db->commit();

            $ns = new NoSerie();
            $ns->Validate('Code',      $code);
            $ns->Validate('No_debut',  Security::sanitizeInput($data['No_debut']   ?? '', 'string'));
            $ns->Validate('No_fin',    Security::sanitizeInput($data['No_fin']     ?? '', 'string'));
            $ns->Validate('Prefix',    Security::sanitizeInput($data['Prefix']     ?? '', 'string'));
            $ns->Validate('Suffix',    Security::sanitizeInput($data['Suffix']     ?? '', 'string'));
            $ns->Validate('Start_date',Security::sanitizeInput($data['Start_date'] ?? '', 'string'));
            $ns->Validate('End_date',  Security::sanitizeInput($data['End_date']   ?? '', 'string'));
            $ns->Insert();
            db->commit();
            return json_encode(['status' => 201, 'message' => 'Souche créée', 'code' => $code]);
        }

        public static function modifier(string $code, array $data): string {
            header('Content-Type: application/json');
            $token = getAthorizationToken();
            if (!AuthenticationManagement::auth($token)) UnAuthorized('Session expirée');

            $ns = new NoSerie();
            if (!$ns->get($code)) {
                db->commit();
                return json_encode(['status' => 404, 'message' => 'Souche introuvable']);
            }

            if (isset($data['No_debut']))    $ns->Validate('No_debut',    Security::sanitizeInput($data['No_debut'],    'string'));
            if (isset($data['No_fin']))       $ns->Validate('No_fin',      Security::sanitizeInput($data['No_fin'],      'string'));
            if (isset($data['Last_Used_No'])) $ns->Validate('Last_Used_No', Security::sanitizeInput($data['Last_Used_No'], 'string'));
            if (isset($data['Prefix']))       $ns->Validate('Prefix',      Security::sanitizeInput($data['Prefix'],      'string'));
            if (isset($data['Suffix']))       $ns->Validate('Suffix',      Security::sanitizeInput($data['Suffix'],      'string'));
            if (isset($data['Start_date']))   $ns->Validate('Start_date',  Security::sanitizeInput($data['Start_date'],  'string'));
            if (isset($data['End_date']))     $ns->Validate('End_date',    Security::sanitizeInput($data['End_date'],    'string'));
            $ns->Modify();
            db->commit();
            return json_encode(['status' => 200, 'message' => 'Souche mise à jour']);
        }

        public static function supprimer(string $code): string {
            header('Content-Type: application/json');
            $token = getAthorizationToken();
            if (!AuthenticationManagement::auth($token)) UnAuthorized('Session expirée');

            $ns = new NoSerie();
            if (!$ns->get($code)) {
                db->commit();
                return json_encode(['status' => 404, 'message' => 'Souche introuvable']);
            }
            $ns->Delete();
            db->commit();
            return json_encode(['status' => 200, 'message' => 'Souche supprimée']);
        }

        // ─────────────────────────────────────────────────────────────────────

        static function EvaluateNoSeriesParameter(NoSerie &$NoSerie){            
            //Vérification du N° de début
            $NoSerie->testField('No_debut');

                       
            //Vérification des N° en string
            $startNoStr = $NoSerie->No_debut->value;
            $lastNoStr = $NoSerie->Last_Used_No->value;

                       
            //Evaluation des N° en Nombre
            $startNo = Evaluate($NoSerie->No_debut->value);
            $lastNo = Evaluate($NoSerie->Last_Used_No->value);

            //Définition des nouveaux N°
            $NewNoStr="";
            $NewNo=0;
            
            if($lastNo == '' || $lastNo == 0)
                $lastNoStr = $startNoStr;

            $FigNumb = strlen($lastNoStr); 
            
            $NewNoStr = $NoSerie->Prefix->value;
            $NewNo .= sprintf('%0'.$FigNumb-(($lastNo+1)/(pow(10,($FigNumb-1)))).'d', $lastNo+1);            
            $NewNoStr .= $NewNo;            
            $NewNoStr .= $NoSerie->Suffix->value;

            $NoSerie->Validate('Last_Used_No', $NewNo);
            
            $NoSerie->Modify();
            return $NewNoStr;
        }
    }
?>