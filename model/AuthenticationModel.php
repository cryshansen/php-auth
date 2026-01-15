<?php

require_once "database.php"; 

class AuthenticationModel extends Database
{
    public function getUsers($limit)
    {
        
    }
    public function getUserById($user_id){
         $data = [
            'user_id' => $user_id
        ];
        
        //$sql = "Select id,email,password_hash,last_login,failed_attempts,lock_until,ip_address, user_id from user_authentication where user_id=:user_id";
        $sql = "SELECT u.user_id, u.email, u.is_verified FROM users u WHERE u.user_id = :user_id";
        file_put_contents("auth_log.txt", "getUserById AuthenticationModel " .print_r($data,true) ."\n", FILE_APPEND);

        $dbResult = $this->select($sql, $data);
        
        return $dbResult;
    }
    
    public function signupUser($firstname,$lastname,$email,$password,$verificationNo){
        file_put_contents("auth_log.txt", "signupUser data  AuthenticationModel " . $firstname." ". $lastname." ". $email." ". $password." ". $verificationNo."\n", FILE_APPEND);
        // Get client IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $userData = [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'is_verified' => 0
        ];
        $result = $this->createUser($userData);
        $user_id = $this->lastInsertId();
        
        // Insert user authentication info
        $authData = [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),  // always hash passwords!
            'last_login' => null,
            'failed_attempts' => 0,
            'lock_until' => null,
            'ip_address' => $ipAddress,
            'user_id' => $user_id
        ];
        
        $result2 = $this->saveUserAuthentication($authData);
        file_put_contents("auth_log.txt", "signupUser after saveUserAuthentication data  AuthenticationModel " . $result2."\n", FILE_APPEND);

        //  Insert email verification token
        $expires = new DateTime();
        $expires->modify('+24 hours');
        
        //id	user_id	email	token	expires_at	used	created_at	

        $verificationData = [
            'user_id' => $user_id,
            'email' => $email,
            'token' => $verificationNo,
            'expires_at' => $expires->format('Y-m-d H:i:s'),
            'used' => 0,
        ];

        $this->saveSignupVerification($verificationData);


        return $user_id;
        
    }
    
    public function signinUser($email, $password){
          // Get client IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    
        //file_put_contents("auth_log.txt", "signinUser IP: $ipAddress | Email: $email\n", FILE_APPEND);

        file_put_contents("auth_log.txt", "signinUser data  AuthenticationModel " .$email." ". $password."\n", FILE_APPEND);
        $user = $this->getUserByEmail($email);
        
        if(!$user){
            file_put_contents("auth_log.txt", "signinUser select failed " .$email." ". $password."\n", FILE_APPEND);
            //the user was not found respond as such
            return ['success' => false, 'message' => 'Unable to login. Please check your credentials or verify your email.' ];
        }
        if (!$user[0]['is_verified']) {
            file_put_contents("auth_log.txt", "signinUser select failed " .$email." ". $password."\n", FILE_APPEND);
               return ['success' => false, 'message' => 'Please verify your email first.'];
        }
        else{
            //file_put_contents("auth_log.txt", "signinUser  AuthenticationModel user: " .print_r($user,true). "\n", FILE_APPEND);
            $authUser = $this->getAuthentication($user);
            file_put_contents("auth_log.txt", "signinUser  AuthenticationModel authUser: " .print_r($authUser,true). "\n", FILE_APPEND);
            
            if (!empty($authUser[0]['lock_until'])) { 
                $now = new DateTime();                 
                $lockUntil = new DateTime($authUser[0]['lock_until']);
                if($now < $lockUntil){
                    file_put_contents("auth_log.txt", "signinUser  AuthenticationModel LockUntil inside LockUntil< now: " .$lockUntil->format('Y-m-d H:i:s'). "\n", FILE_APPEND);
                    return ['success' => false, 'message' => 'Account is locked due to too many failed attempts. You can try again after:'.$lockUntil->format('Y-m-d H:i:s') ];
                }
            }
            if (password_verify($password, $authUser[0]['password_hash']) ) {
                file_put_contents("auth_log.txt", "signinUser  AuthenticationModel If password verify success : \n", FILE_APPEND);
                    // Auth successful - optionally generate a token/session here
                     // reset failed attempts
                    $result = $this->resetFailedAttempts($authUser[0]['user_id'],$ipAddress);
                    file_put_contents("auth_log.txt", "signinUser  AuthenticationModel If password verify success :" .print_r($result, true). "\n", FILE_APPEND);
                    return [
                        'success' => true,
                        'user_id' => $authUser[0]['user_id'],
                        'message' => 'Login successful.'
                    ];

            } else {
                    // Delay to mitigate brute-force attacks
                    usleep(300000); // 0.3 seconds
                    // increment failed_attempts
                    $this->saveFailedAttempt($authUser[0]['user_id'],$authUser[0]['failed_attempts'],$ipAddress);
                    // Lock account if failed_attempts >= 4 *after* this attempt
                    if ( ($authUser[0]['failed_attempts'] + 1) >= 4) {
                        $this->updateLockUntil($authUser[0]['user_id']);
                        return [
                            'success' => false,
                            'message' => 'Account locked due to too many failed attempts. Try again later.'
                        ];
                        
                    }else{
                        return ['success' => false, 'message' => 'Invalid Login.'];
                        
                    }


            }
        }
        
    }
    
    public function updateLockUntil($user_id){
        $expires = new DateTime();
        $expires->modify('+24 hours');
        $lock_until = $expires;
        $query = "UPDATE `user_authentication` SET  `lock_until` = :lock_until   WHERE `user_id` = :user_id" ;
        $data = [
            'lock_until' => $expires->format('Y-m-d H:i:s'),
            'user_id' => $user_id
        ];
        file_put_contents("auth_log.txt", "signinUser  AuthenticationModel resetFailedAttempts: sql" .$query." data: " .print_r($data,true). "\n", FILE_APPEND);
        $result = $this->update($query, $data);
        return $result;
    }
    public function resetFailedAttempts($user_id,$ipAddress){
        $last_login = new DateTime();
        $query = "UPDATE `user_authentication`  SET `failed_attempts` = 0,  `lock_until` = :lock_until, `last_login` = :last_login, `ip_address` =:ip_address WHERE `user_id` = :user_id";
        $data = [
            'lock_until' => '',
            'last_login' => $last_login->format('Y-m-d H:i:s'),
            'user_id' => $user_id,
            'ip_address' => $ipAddress
        ];
        file_put_contents("auth_log.txt", "signinUser  AuthenticationModel resetFailedAttempts: sql" .$query." data: " .print_r($data,true). "\n", FILE_APPEND);
        $result = $this->update($query, $data);
        return $result;
    }
    public function saveFailedAttempt($user_id,$failed_attempts,$ipAddress){
        
        file_put_contents("auth_log.txt", "signinUser  AuthenticationModel saveFailedAttempt: " .$user_id. "\n", FILE_APPEND);
        $failedcount = $failed_attempts +1;
        $data = [
            'user_id' => $user_id,
            'failed_attempts' => $failedcount,
            'ip_address' => $ipAddress
        ];
        $query = "UPDATE `user_authentication` 
          SET `failed_attempts` = :failed_attempts , `ip_address` =:ip_address
          WHERE `user_id` = :user_id" ;

        file_put_contents("auth_log.txt", "signinUser  AuthenticationModel saveFailedAttempt: sql" .$query." data: " .print_r($data,true). "\n", FILE_APPEND);
        $result = $this->update($query, $data);
        return $result;
    }
    
    public function getAuthentication($user){
        $data = [
            'user_id' => $user[0]['user_id'],
            'email' => $user[0]['email']
        ];
        
        $sql = "Select id,email,password_hash,last_login,failed_attempts,lock_until,ip_address, user_id from user_authentication where user_id=:user_id and email = :email";
        
        file_put_contents("auth_log.txt", "signinUser getAuthentication " .print_r($data,true) ."\n", FILE_APPEND);

        $dbResult = $this->select($sql, $data);
        
        return $dbResult;
    }
    
    /**
     *  ✅ Overview of Password Reset Flow
            User requests reset via email form. !!!! This component handles this step here. !!!
            
            Generate secure token, store it with expiry.
            
            Send email with reset link including token and user ID (or email hash).
    */
    //this function handles the user reset request and the token generation - email at usercontroller to reset the user password
    public function resetPasswordRequest($email){
        // `user_password_reset`  id	user_id	token	expires_at	used	created_at	
        $userdb = $this->getUserByEmail($email);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        file_put_contents("auth_log.txt", "Result of getUserByEmail for $email: " . print_r($userdb, true) . "\n", FILE_APPEND);

        if(!$userdb){
            file_put_contents("auth_log.txt", "resetPasswordRequest select failed email doesnt exist?" .$email."\n", FILE_APPEND);
            //the user was not found respond as such
            return ['success' => false, 'message' => 'Account does not exist. Try our signup instead and create an account.' ];
        }else{
            $user_id = $userdb[0]['user_id'];
            
            $token = (string) bin2hex(random_bytes(32)); // 64-character secure token
            $tokenHash = hash('sha256', $token);
            $expires = (new DateTime())->modify('+1 hour')->format('Y-m-d H:i:s');
            $created_at = (new DateTime())->format('Y-m-d H:i:s');
            $data = [
                'user_id' => $user_id,
                'token' => (string)$tokenHash,
                'expires_at' => $expires,
                'used'=> 0,
                'created_at' => $created_at,
                'ip_address' =>$ipAddress
            ];
            file_put_contents("auth_log.txt", "resetPasswordRequest before insert to table password_reset" .print_r($data,true)."\n", FILE_APPEND);
            $result =$this->savePasswordToken($data);
            //return user to the front end for emailTemplate
            /*$user = [
                    'user_id' => $userdb[0]['user_id'], 
                    'email' => $userdb[0]['email'], 
                    'firstname' => $userdb[0]['firstname'],
                    'lastname' => $userdb[0]['lastname'], 
                    'token' => $token
                ];
              */  
            if($result){
                return [
                    'success' => true, 
                    'user' => [
                        'user_id' => $user_id,
                        'email' => $userdb[0]['email'],
                        'firstname' => $userdb[0]['firstname'],
                        'lastname' => $userdb[0]['lastname'],
                        'token' => $token // return the non hashed token
                    ]
                ];
            }else{
                return ['success' => false, 'message' => 'Failed to save password token.' ];
            }
            //return $user; 
        
        }
       
    }
    
    
    
    /* User clicks the link → sent to reset page. goes to resetPassword-form for two password fields hits save, 
        Validate token and expiration, then allow password reset.
            Clear token after use */
    public function savePasswordRequest($tokenUrl,$password){
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        file_put_contents("auth_log.txt", "savePasswordRequest " .$tokenUrl." :: ".$password ."\n", FILE_APPEND);
        //get user_id from tokenUrl  user_password_reset  id	user_id	expires_at	used	created_at	
       
            $user = $this->getUserResetToken($tokenUrl);
            //file_put_contents("auth_log.txt", "savePasswordRequest " .print_r($user,true)."\n", FILE_APPEND);
            if(!$user){
                file_put_contents("auth_log.txt", "savePasswordRequest no matching users \n", FILE_APPEND);
                 return ['success' => false, 'message' => 'Account does not exist or doesnt match. Please contact support.' ];
            }
            else{
                
             file_put_contents("auth_log.txt", "savePasswordRequest else statement \n", FILE_APPEND);
                //Validate token and expiration, then allow password reset.
                $expires_at = new DateTime($user[0]['expires_at']);
                $now = new DateTime(); //->format('Y-m-d H:i:s');
                //$created_at = (new DateTime())->format('Y-m-d H:i:s');
                if($expires_at > $now){
                    file_put_contents("auth_log.txt", "savePasswordRequest expires_at is greater than now \n", FILE_APPEND);
                    $this->updatePassword($user[0]['user_id'], $password,$ipAddress);
                    $this->updateUsedToken($tokenUrl,$ipAddress);//updates the  user_password_reset not happening


                    return ['success' => true, 'message' => 'Account password has been reset.' ];
    
                    
                }else{
                    return ['success' => false, 'message' => 'Account reset has expired. Please return to the reset page to send a reset email again. If that fails, please contact support.' ];
                    
                }
                
            }
            
        
    }
    
    /**
    * Used by save_password_request for handling access via email links 
    * @return user_password_reset variables user_id,used,expires_at
    * @parameter: the token attached to the email link
    */
    public function getUserResetToken($token){
        
        file_put_contents("auth_log.txt", "getUserResetToken: ".$token . "\n", FILE_APPEND);
        file_put_contents("auth_log.txt", "Token Type: " . gettype($token) . "\n", FILE_APPEND);
        $hashed = hash('sha256', $token);
        $sql = "Select user_id, used, expires_at, ip_address from `user_password_reset` where token=:token";
        $data = [
            'token'=> (string)$hashed    
        ];
        file_put_contents("auth_log.txt", "getUserResetToken:".$sql ."data".print_r($data,true) . "\n", FILE_APPEND);
        $result = $this->select($sql,$data);
        return $result;
    }
    public function updateUsedToken($tokenUrl,$ipAddress){

        $hashed = hash('sha256', $tokenUrl);
        
        file_put_contents("auth_log.txt", "updateUsedToken: ".$tokenUrl . "\n", FILE_APPEND);
        file_put_contents("auth_log.txt", "Token Type: " . gettype($tokenUrl) . "\n", FILE_APPEND);
        $sql = "Update user_password_reset Set used = 1, ip_address = :ip_address  where token=:token";
        $data = [
            'ip_address' =>  $ipAddress,
            'token'=> (string)$hashed    
        ];
        file_put_contents("auth_log.txt", "updateUsedToken:".$sql ."data".print_r($data,true) . "\n", FILE_APPEND);
        $result = $this->update($sql,$data);
        return $result;
    }
    /* function that sets the users email as validated when click on the link in email example:  /verify?token=e9a18f02b34fb36f01827d8e22dc585a&email=info%40ecry.com in controfront end processing double opt in   */
    /**
    * Used by verifyemailPostAction Controller for handling double opt in signup mechanism
    * @return success/failure db result
    * @parameter: the token attached to the email link
    */
    
    public function emailVerification($data){
        //get user by email id for update of the user table set is_verified = 1
        
        //UPDATE `user_email_verification` SET `used` = '0' WHERE `user_email_verification`.`email` = 'info@ecry.com' and `token` = 'e9a18f02b34fb36f01827d8e22dc585a';
       
        $query = "UPDATE `user_email_verification` 
          SET `used` = 1, ip_address = :ip_address 
          WHERE `email` = :email AND `token` = :token";

        
        $dbResult = $this->update($query, $data);
        $updated_at = (new DateTime())->format('Y-m-d H:i:s');
        if(!$dbResult){
                file_put_contents("auth_log.txt", "emailVerification no matching users email / token  \n", FILE_APPEND);
                return ['success' => false, 'message' => 'Email Verification failed. Please contact support.' ];
            }
            else{    
            $data2=[
                'updated_at' => $updated_at,
                'email' => $data['email']
                ];
            $sql = 'UPDATE users set `is_verified` = 1 , updated_at = :updated_at where email = :email ';
            $dbResult2 = $this->update($sql, $data2);
            if(!$dbResult2){
                file_put_contents("auth_log.txt", "emailVerification failed to update the user table is_verified \n", FILE_APPEND);
                return ['success' => false, 'message' => 'Email Verification failed. Please contact support.' ];
            }else{
                return ['success' => true, 'message' => 'Email Verified.' ];
            }
        }
        
        
        
        
    }
    
    
    public function savePasswordToken($data){
        
        $sql = "INSERT INTO user_password_reset (user_id,token,expires_at,used,ip_address,created_at) VALUES (:user_id,:token,:expires_at,:used,:ip_address,:created_at)";
        $result = $this->insert($sql,$data);
        return $result;
    }
    
    public function getUserByEmail($email){
        

        $sql = "SELECT user_id, email, firstname,lastname, is_verified FROM users WHERE email = :email";
        $data = [
            'email' => $email,
        ];
        
        file_put_contents("auth_log.txt", "signupUser in getUserByEmail data  AuthenticationModel ".$sql." data: " . print_r($data,true)."\n", FILE_APPEND);

        $dbResults = $this->select($sql,$data);
        
        return $dbResults;
    }
    
    public function createUser($data){

        $sql = "INSERT INTO users (firstname,lastname,email,is_verified) VALUES (:firstname,:lastname,:email,:is_verified)";
        $result = $this->insert($sql,$data);
        return $result; 
        
        
    }
    
    
    public function saveUserAuthentication($data){
        //id email	password_hash	last_login	failed_attempts	lock_until	

        $sql = "INSERT INTO user_authentication (`email`,`password_hash`,`last_login`,`failed_attempts`,`lock_until`,`ip_address`,`user_id`) VALUES (:email, :password_hash, :last_login,:failed_attempts,:lock_until,:ip_address,:user_id)";
        file_put_contents("auth_log.txt", "saveUserAuthentication data  AuthenticationModel " . $sql." : ". print_r($data,true)."\n", FILE_APPEND);
        $dbResults = $this->insert($sql,$data);
        return $dbResults;
        
    }
    
    

    public function saveSignupVerification($data){
        //id	user_id	email	token	expires_at	used	created_at	
        $sql = "INSERT INTO user_email_verification (`user_id`,`email`,`token`,`expires_at`,`used`) VALUES (:user_id, :email, :token, :expires_at,:used)";
        file_put_contents("auth_log.txt", "saveUserAuthentication data  AuthenticationModel " . $sql." : ". print_r($data,true)."\n", FILE_APPEND);
        $dbResults = $this->insert($sql,$data);
        
        return $dbResults;
    }
    public function updatePassword($user_id,$password,$ipAddress){
        $sql = "Update user_authentication Set password_hash = :password, ip_address = :ip_address where user_id = :user_id";
        $data =[
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'user_id' => $user_id,
            'ip_address' =>$ipAddress
        ];
        
        $dbResults = $this->update($sql,$data);
        return $dbResults;
        
        
    }
    
}