<?php

/* 
   NOTE: Most endpoints return proper HTTP status codes (400, 401, 422, 500).
   Exception: signin returns 200 for both success/failure to allow frontend 
   to always parse the JSON response (avoid fetch throwing on 401).
 */

class AuthController extends BaseController
{
    /**
     * "/users/list" Endpoint - Get list of users
     */
    public function listAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();
 
        if (strtoupper($requestMethod) == 'GET') {
            try {
                $authenticationModel = new AuthenticationModel();
 
                $intLimit = 10;
                if (isset($arrQueryStringParams['limit']) && $arrQueryStringParams['limit']) {
                    $intLimit = $arrQueryStringParams['limit'];
                }
 
                $arrUsers = $authenticationModel->getUsers($intLimit);
                $responseData = json_encode($arrUsers);
            } catch (Error $e) {
                $strErrorDesc = $e->getMessage().'Something went wrong! Please contact support.';
                $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
            }
        } else {
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
        }
 
        // send output
        if (!$strErrorDesc) {
            $this->sendOutput(
                $responseData,
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }
    
    /**
     * 
     * "users/signin" Endpoint
     */
     
    public function signinPostAction(){
        
         $strErrorDesc = '';
        // Read JSON input
        $inputJSON = file_get_contents("php://input");
        file_put_contents("auth_log.txt", "\nRaw authController signinPostAction POST data: " . $inputJSON . "\n", FILE_APPEND);
        $data = json_decode($inputJSON, true);

       
        if (!is_array($data)) {
            $this->sendOutput(
                json_encode(["error" => "Invalid JSON"]),
                ['Content-Type: application/json', 'HTTP/1.1 400 Bad Request']
            );
            return;
        }
        
        $email = trim(strtolower($data['username']));
        $password = $data['password'];
        $captchaResponse = $data["token"] ?? '';
        
        /*if (!$this->verifyCaptchaV3($captchaResponse)) {
            
            
            
            $strErrorDesc = 'Invalid reCAPTCHA.';
            $strErrorHeader = 'HTTP/1.1 400 Bad Request';
            //return json_encode(["status" => "error", "message" => "Invalid reCAPTCHA."]);
            //exit;

        }*/
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            
            $strErrorDesc = 'Invalid email address.';
            $strErrorHeader = 'HTTP/1.1 400 Bad Request.';

            
        }
        
        $result =true;
        
        if($result){
            
            try{
                $authenticationModel =  new AuthenticationModel();
                $valid = $authenticationModel->signinUser($email, $password);
                
                // echo json_encode(["status" => "error", "message" => "Database error"]);
                /*ISVALID: Array
                (
                    [success] => false/true
                    [user_id] => 10
                    [message] => Login successful.
                )*/

                if(isset($valid['success']) && $valid['success']){
                    file_put_contents("auth_log.txt", "ISVALID: ".print_r($valid,true). "\n", FILE_APPEND);
                
                    $_SESSION['user_id'] = $valid['user_id'];
                    $_SESSION['authenticated_at'] = time();

                    //file_put_contents("auth_log.txt", "SESSION: ".print_r( $_SESSION,true). "\n", FILE_APPEND);
                    //should send back a token for session and expires at to meet the criteria
                    $response = [
                        "success" =>$valid['success'],
                        "message" => "You signed in!".$valid['user_id'],
                        ];
                    //file_put_contents("auth_log.txt", "response: ".print_r($response,true). "\n", FILE_APPEND);
                    $responseData = json_encode($response);
                    $strHeader = 'HTTP/1.1 200 OK';
                    file_put_contents("auth_log.txt", "responseData: ".print_r($responseData,true). "\n", FILE_APPEND);
                }else{
                // valid =  return ['success' => false, 'message' => 'Unable to login. Please check your credentials or verify your email.' ];
                    file_put_contents("e_log.txt", "ERROR: Login:".print_r($valid,true) ."\n", FILE_APPEND);

                    //$strErrorDesc = $valid['message']; // not json_encode here 'message' => 'Unable to login. Please check your credentials or verify your email.'
                    $responseData = json_encode([
                        "success" => false,
                        "message" => $valid['message'] ?? "Unknown error.",
                    ]);
                    $strHeader = 'HTTP/1.1 200 OK';
                    
                    
                }
            } catch (Error $e) {
                $strErrorDesc = $e->getMessage().'Something went wrong! Please contact support.';
                $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
            }
            
        } else {
           // echo json_encode(["status" => "error", "message" => "Database error"]);
            file_put_contents("e_log.txt", "ERROR: with sending email\n", FILE_APPEND);

            $strErrorDesc = 'Something went wrong! Please contact support.';
            $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
        }
         
        // send output
       if (!$strErrorDesc) {
            file_put_contents("auth_log.txt", "responseData: ".print_r($responseData,true). "\n", FILE_APPEND);
            $this->sendOutput(
               $responseData,
               array('Content-Type: application/json', $strHeader )
           );
       } else {
            file_put_contents("auth_log.txt", "responseData: ".print_r($strErrorDesc,true). "\n", FILE_APPEND);
            $this->sendOutput(json_encode(array('error' => $strErrorDesc)), 
               array('Content-Type: application/json', $strErrorHeader)
           );
       }
    }
    
    /**
     * 
     * "users/resetpassword" Endpoint
     */
     
    
    public function resetpasswordPostAction(){

        $strErrorDesc = '';
        $responseData='';
        // Read JSON input

        $inputJSON = file_get_contents("php://input");
        file_put_contents("auth_log.txt", "\nRaw authController resetpasswordPostAction POST data: " . $inputJSON . "\n", FILE_APPEND);
        $data = json_decode($inputJSON, true) ?? $_POST;
        $captchaResponse = $data["token"] ?? '';
        $email = $data['email'] ?? '';
        if (!$data) {
            $strErrorDesc = 'Invalid JSON received';
            $strErrorHeader = 'HTTP/1.1 400 Bad Request';
        }
 
        //returns $result['success'] && $result['score'] > 0.5;
       if (!$this->verifyCaptchaV3($captchaResponse)) {
            $strErrorDesc = 'Invalid reCAPTCHA.';
            $strErrorHeader = 'HTTP/1.1 422 Invalid Captcha';
            
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $strErrorDesc = 'Invalid email address.';
            $strErrorHeader = 'HTTP/1.1 400 Bad Request';
        }
        
        
        $result =true;
        
        if($result){
            $valid=true;
            file_put_contents("auth_log.txt", "\nRaw authController resetpasswordPostAction AFTER backend data: \n", FILE_APPEND);
            try{
                $authenticationModel = new AuthenticationModel();
               
                $result = $authenticationModel->resetPasswordRequest($email);
                file_put_contents("auth_log.txt", "\nRaw authController resetpasswordPostAction AFTER backend data: " . print_r($result,true) . "\n", FILE_APPEND);
                if (!$result['success']) {
                    //resetPasswordRequest select failed email doesnt exist?Array  return ['success' => false, 'message' => 'Account does not exist. Try our signup instead and create an account.' ];
                    $strErrorDesc =  $result['message'];
                    $strErrorHeader = 'HTTP/1.1 422 Invalid Action.';

                }else{
                    
                    $user= $authenticationModel->getUserByEmail($email); 
                    file_put_contents("auth_log.txt", "\nRaw authController resetpasswordPostAction AFTER getUserByEmail  data: " . print_r($userdb,true) . "\n", FILE_APPEND);

                    $signupEmailData = [
                        'First Name' => $user['firstname'],
                        'email' => $user['email'],
                        'subject' => "Reset your Password",
                        'corp' => getenv('APP_NAME') ?: 'Application',
                        'contact email' => getenv('MAIL_FROM') ?: 'noreply@example.com',
                        'validlink' => getenv('APP_URL') . '/resetpassword?token=' . $user['token'] . '&email=' . urlencode($email),
                        'Website' => getenv('APP_URL') ?: 'https://example.com',
                        'SocialMediaLink' => getenv('SOCIAL_MEDIA_URL') ?: 'https://example.com',
                    ];
                    
    
                    $this->handleResetEmail($signupEmailData);
    
                    $response = [
                        "success" => true,
                        "message" => "An email to reset your password has been sent! Check your inbox."
                     ];
                     
                     $strHeader = 'HTTP/1.1 200 OK';
                     $responseData = json_encode($response);
                     
                }
                 
            } catch (Error $e) {
                $strErrorDesc = $e->getMessage().'Something went wrong! Please contact support.';
                $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
            }
            
        }    
        // send output
        if (!$strErrorDesc) {
              
           $this->sendOutput(
               $responseData,
               array('Content-Type: application/json', $strHeader)
           );
        } else {
              
           $this->sendOutput(json_encode(array('error' => $strErrorDesc)), 
               array('Content-Type: application/json', $strErrorHeader)
           );
        }
        
        
        
    }
    
    
    
    
    
    /**
     * 
     * "users/resetnewpass" Endpoint
     * 
     */ 

    public function resetnewpassPostAction(){
          // Start output buffer
        $strErrorDesc = '';
        $responseData='';
        // Read JSON input
        $inputJSON = file_get_contents("php://input");
        
        file_put_contents("auth_log.txt", "\nRaw authController resetnewpassPostAction POST data: " . $inputJSON . "\n", FILE_APPEND);
        $data = json_decode($inputJSON, true);
        
        if (!$data) {
            $this->sendOutput(
                json_encode(["error" => "Invalid JSON received"]),
                ['Content-Type: application/json', 'HTTP/1.1 400 Bad Request']
            );
            return;
        }
        
        $captchaResponse = $data["token"] ?? ''; // captcha token
        $tokenUrl = $data['tokenUrl'] ?? ''; //back end token sent in email.
        $password = $data['password'] ?? '';

        
        
        //returns $result['success'] && $result['score'] > 0.5;
       if (!$this->verifyCaptchaV3($captchaResponse)) {
            
            $responseData= json_encode(["status" => "error", "message" => "Invalid reCAPTCHA."]);
            $strErrorHeader = 'HTTP/1.1 422 Invalid Captcha';
            return;
        }
        
        $result =true;
        
        if($result){
            
            $valid=true;
            
            try{
                file_put_contents("auth_log.txt", "\nRaw authController resetnewpassPostAction try before this->savePasswordReset: " . $tokenUrl . "\n", FILE_APPEND);
                $authenticationModel = new AuthenticationModel();
                $valid = $authenticationModel->savePasswordRequest($tokenUrl,$password);
                if(!$valid){
                    //fire email response
                    $strErrorDesc = json_encode(["status" => "error", "message" => "Invalid check with support."]);
                    $strErrorHeader = 'HTTP/1.1 422 Invalid Action.';
                    return;
                }
                
                $response = [
                    "success" => true,
                    "message" => "Your password has been reset!",
                 ];
                 
                 $responseData = json_encode($response);
                 
            } catch (Error $e) {
                $strErrorDesc = $e->getMessage().'Something went wrong! Please contact support.';
                $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
            }
            
        }    
          // send output
       if (!$strErrorDesc) {
            
           $this->sendOutput(
               $responseData,
               array('Content-Type: application/json', 'HTTP/1.1 200 OK')
           );
       } else {
            
           $this->sendOutput(json_encode(array('error' => $strErrorDesc)), 
               array('Content-Type: application/json', $strErrorHeader)
           );
       }
    }
    
    /**
     * 
     * "/users/signup" Endpoint
     * 
     */ 
    
    public function signupPostAction(){
          //start output buffer
        $strErrorDesc = '';
        // Read JSON input
        $inputJSON = file_get_contents("php://input");
        file_put_contents("auth_log.txt", "\nRaw authController signupPostAction POST data: " . $inputJSON . "\n", FILE_APPEND);
        $data = json_decode($inputJSON, true);
        $captchaResponse = $data["token"] ?? '';
        
        if (!$data) {
            $this->sendOutput(
                json_encode(["error" => "Invalid JSON received"]),
                ['Content-Type: application/json', 'HTTP/1.1 400 Bad Request']
            );
            return;        
        
        }
        
        // //returns $result['success'] && $result['score'] > 0.5;
        // if (!$this->verifyCaptchaV3($captchaResponse)) {
            
        //     $this->sendOutput(
        //         json_encode(["error" => "Invalid reCAPTCHA."]),
        //         ['Content-Type: application/json', 'HTTP/1.1 422 Invalid Captcha']
        //     );
        //     return;  

        // }
        

  

        $firstname = $data['firstname'] ?? null;
        $lastname = $data['lastName'] ?? null;
        $email = trim(strtolower($data['email'])) ?? null;
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
             $this->sendOutput(
                json_encode(["error" => "Invalid email address."]),
                ['Content-Type: application/json', 'HTTP/1.1 400 Bad Request.']
            );
            return; 
            
        }
        
        $password = $data['password'] ?? null;
        $verificationNo = $this->generateVerificationNo();
        $signupEmailData = [
            'First Name' => $firstname,
            'email' => $email,
            'subject' => "Signup Email Verification Request",
            'corp' => getenv('APP_NAME') ?: 'Application',
            'contact email' => getenv('MAIL_FROM') ?: 'noreply@example.com',
            'validlink' => getenv('APP_URL') . '/verify?token='.$verificationNo.'&email='. urlencode($email),
            'Website' => getenv('APP_URL') ?: 'https://example.com',
            'SocialMediaLink' => getenv('SOCIAL_MEDIA_URL') ?: 'https://example.com',
        ];
        $result =true;
        if($result){

            try{
                //file_put_contents("auth_log.txt", "\nRaw authController before email function call in signupPostAction: " .  print_r($signupEmailData, true) . "\n", FILE_APPEND);

                $authenticationModel = new AuthenticationModel();
                //file_put_contents("auth_log.txt", "\nRaw authController before signuUser function call in signupPostAction: " . $firstname." ".$lastname." ".$email." ".$password." ".$verificationNo . "\n", FILE_APPEND);
                $userid = $authenticationModel->signupUser($firstname,$lastname,$email,$password,$verificationNo);
                //This works exclude ENABLE AFTER TESTING                
                $this->handleEmailInternal($signupEmailData);
                $this->handleEmail($signupEmailData);
                $response = [
                        "message" => "You signed up!, Check your inbox. We’ve sent you an email to verify your account.".$userid ,
                ];
                    
                $responseData = json_encode($response);
                $strHeader = 'HTTP/1.1 200 OK';
            } catch (Error $e) {
                $strErrorDesc = $e->getMessage().'Something went wrong! Please contact support.';
                $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
            }
            
        } else {
           // echo json_encode(["status" => "error", "message" => "Database error"]);
            file_put_contents("auth_log.txt", "ERROR: with signup \n", FILE_APPEND);

            $strErrorDesc = $e->getMessage().'Something went wrong! Please contact support.';
            $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
        }
        
        // send output
       if (!$strErrorDesc) {
             
           $this->sendOutput(
               $responseData,
               array('Content-Type: application/json', 'HTTP/1.1 200 OK')
           );
       } else {
            
           $this->sendOutput(json_encode(array('error' => $strErrorDesc)), 
               array('Content-Type: application/json', $strErrorHeader)
           );
       }
       
    }
    
    /**
    *
    * "/users/verifyemail" Endpoint
    * After signup, click the email link, land on site verify email page and handles validation of signup process workflow. On page landing the url link processes the information to validate email is good    
    * params(email, jwttoken, captchatoken)
    */
    /*TODO: wrap with captcha so reduce hit on url page. */
    public function verifyemailPostAction(){
           //start output buffer
         $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
         $strErrorDesc = '';
        // Read JSON input
        $inputJSON = file_get_contents("php://input");
        file_put_contents("auth_log.txt", "\nRaw authController verifyemailPostAction POST data: " . $inputJSON . "\n", FILE_APPEND);
        $data = json_decode($inputJSON, true);
        
        $email = trim(strtolower($data['email'])) ?? '';
        $token = $data['token']; //TODO: integrate ; captcha `token` reserve field name should use jwttoken for confirmation backend tokens.
        $jwttoken = $data['jwttoken'];
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Invalid email format
            $this->sendOutput(
                json_encode(["error" => "Invalid email address."]),
                ['Content-Type: application/json', 'HTTP/1.1 400 Bad Request']
            );
            return;
            
        }
        
        $validate =[
            'email' =>$email,
            'token' => $jwttoken, //backend from email link sent to user not to be confused with captcha value
            'ip_address' => $ipAddress
            ];
            
            
           
        $result =true;
        if($result){
            //clean the data before sending 
            $authenticationModel = new AuthenticationModel();
            $result = $authenticationModel->emailVerification($validate);
            $response = [
                            "success" => $result['success'],
                            "message" => "You verified your account! You're ready to go!",
                    ];
                        
            $responseData = json_encode($response);
        } else {
           // echo json_encode(["status" => "error", "message" => "Database error"]);
            file_put_contents("auth_log.txt", "ERROR: with verification email \n", FILE_APPEND);

            $strErrorDesc = $e->getMessage().'Something went wrong! Please contact support.';
            $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
        }   
        
        // send output
       if (!$strErrorDesc) {
             
           $this->sendOutput(
               $responseData,
               array('Content-Type: application/json', 'HTTP/1.1 200 OK')
           );
       } else {
             
           $this->sendOutput(json_encode(array('error' => $strErrorDesc)), 
               array('Content-Type: application/json', $strErrorHeader)
           );
       }
    }
    
    
    /**
    * need  an email function build to send data and url for signup email verification or other emailing sytem needs during client engagements
    */
      // Create a function to replace placeholders
    function handleEmail($data) {
        $subject = "You signed up to our Booking system with ".$data['email']. " and you need to verify  your account before your can use our services.";
        $templatePath = '/home/crystal9/public_html/api/signup_email.html';//'/../signup_email.html'; //for back end notifications
        
        // Log the raw path
        //file_put_contents("auth_log.txt", "Checking raw filepath name: ".$templatePath."\n", FILE_APPEND);  
        //file_put_contents("auth_log.txt", "Checking raw path: ".print_r($data,true)."\n", FILE_APPEND); 
 
        // Actual condition
        if (file_exists($templatePath) && is_readable($templatePath)) {
           
            $emailBody = $this->loadEmailTemplate($templatePath,$data);
            if ($emailBody) {
                
                if (Mail::sendConfirmation(getenv('MAIL_FROM') ?: 'noreply@example.com', $subject, $emailBody)) {
                    file_put_contents("auth_log.txt", "✅ Email sent\n", FILE_APPEND);
                } else {
                    file_put_contents("auth_log.txt", "❌ ERROR: Email failed\n", FILE_APPEND);
                }
            }
        } else {
            file_put_contents("auth_log.txt", "❌ Template NOT found: $templatePath\n", FILE_APPEND);

            return ['success' => false, 'message' => 'Email template missing'];

        }
        
    }
    
    function handleEmailInternal($data) {
        $subject = "You have a client who signed up to our Booking system with ".$data['email']. " and you need to check its functionality.";
        $templatePath = '/home/crystal9/public_html/api/internal_email.html';  // '/../internal_email.html'; //for back end notifications
    
        if (file_exists($templatePath) && is_readable($templatePath)) {
            $emailBody = $this->loadEmailTemplate($templatePath,$data);
            if ($emailBody) {
                if (Mail::sendConfirmation(getenv('MAIL_FROM') ?: 'noreply@example.com', $subject, $emailBody)) {
                    file_put_contents("auth_log.txt", "✅ Email sent\n", FILE_APPEND);
                } else {
                    file_put_contents("auth_log.txt", "❌ ERROR: Email failed\n", FILE_APPEND);
                }
            }
        } else {
            file_put_contents("auth_log.txt", "❌ Template NOT found: $templatePath\n", FILE_APPEND);
           return ['success' => false, 'message' => 'Email template missing'];

        }
    }
    
    

    function handleResetEmail($data){
        $subject = "You requested a password reset to your account at " . (getenv('APP_URL') ?: 'example.com');
        $templatePath = getenv('TEMPLATE_PATH') ?: dirname(__FILE__) . '/../reset_password_email.html';
        if (file_exists($templatePath) && is_readable($templatePath)) {
            $emailBody = $this->loadEmailTemplate($templatePath,$data);
            if ($emailBody) {
                if (Mail::sendConfirmation(getenv('MAIL_FROM') ?: 'noreply@example.com', $subject, $emailBody)) {
                    file_put_contents("auth_log.txt", "✅ Email sent\n", FILE_APPEND);
                } else {
                    file_put_contents("auth_log.txt", "❌ ERROR: Email failed\n", FILE_APPEND);
                }
            }
        } else {
            file_put_contents("auth_log.txt", "❌ Template NOT found: $templatePath\n", FILE_APPEND);
           return ['success' => false, 'message' => 'Email template missing'];

        }
    }
    
    
    
    // Create a function to replace placeholders
    function loadEmailTemplate($filePath, $data) {
        
        // Optional: log what you're loading
        file_put_contents("auth_log.txt", "📤 Loading template from: $filePath\n", FILE_APPEND);

        $template = file_get_contents($filePath); // Load HTML file
        //$emailBody = "Template found successfully.";  // ← Just test it comment out when ready
        // Replace placeholders with real data
        foreach ($data as $key => $value) {
            //file_put_contents("auth_log.txt", "loop:". $key." value ". $value."\n", FILE_APPEND);
            $template = str_replace("[$key]", $value, $template);
        }
        
        return $template;
    }
    
    public function meGetAction() {
        
        file_put_contents("auth_log.txt", "COOKIE: ".print_r($_COOKIE, true)."\n", FILE_APPEND);

        if (empty($_SESSION['user_id'])) {
            
            $this->sendOutput(
                json_encode(["error" => "Unauthenticated"]),
                ['Content-Type: application/json','HTTP/1.1 401 Unauthorized']
            );
            return;
        }
        
        
        $userRows = (new AuthenticationModel())->getUserById($_SESSION['user_id']);
        
        if (!$userRows || !isset($userRows[0])) {
            $this->sendOutput(
                json_encode(["error" => "Invalid session"]),
                ['Content-Type: application/json', 'HTTP/1.1 401 Unauthorized']
            );
            return;
        }
        
        $user = $userRows[0];
        
        // Check if user email is verified
        if (!$user['is_verified']) {
            $this->sendOutput(
                json_encode(["error" => "Email not verified"]),
                ['Content-Type: application/json', 'HTTP/1.1 401 Unauthorized']
            );
            return;
        }
        
        // PUBLIC SAFE SHAPE
        $response = [
            "id" => $user['user_id'],
            "email" => $user['email'],
            "emailVerified" => (bool)$user['is_verified'],
        ];
         
        $this->sendOutput(
            
            json_encode($response),
            ['Content-Type: application/json', 'HTTP/1.1 200 OK']
        );
        
    }



    public function logoutPostAction() {
        
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    
        // Destroy all session data
        $_SESSION = [];
        session_destroy();
    
       
    
        $this->sendOutput(
            json_encode(["success" => true, "message" => "Logged out successfully."]),
            ['Content-Type: application/json', 'HTTP/1.1 200 OK']
        );
        
    }
    
  

    
    
}