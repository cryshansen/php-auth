<?php

/* TODO: make all calls have an assignable response vs always returning 200 */

class UserController extends BaseController
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
                $userModel = new UserModel();
 
                $intLimit = 10;
                if (isset($arrQueryStringParams['limit']) && $arrQueryStringParams['limit']) {
                    $intLimit = $arrQueryStringParams['limit'];
                }
 
                $arrUsers = $userModel->getUsers($intLimit);
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
    
 
    function handleEmailInternal($data) {
        $subject = "You have a client who signed up to our Booking system with ".$data['email']. " and you need to check its functionality.";
        $templatePath = '/home/servername/public_html/api/internal_email.html';  // '/../internal_email.html'; //for back end notifications
    
        if (file_exists($templatePath) && is_readable($templatePath)) {
            $emailBody = $this->loadEmailTemplate($templatePath,$data);
            if ($emailBody) {
                if (Mail::sendConfirmation(getenv('MAIL_FROM') ?: 'noreply@example.com', $subject, $emailBody)) {
                    file_put_contents("log.txt", "✅ Email sent\n", FILE_APPEND);
                } else {
                    file_put_contents("log.txt", "❌ ERROR: Email failed\n", FILE_APPEND);
                }
            }
        } else {
            file_put_contents("log.txt", "❌ Template NOT found: $templatePath\n", FILE_APPEND);
            die("Error: Email template not found at $templatePath");
        }
    }

    
    
    // Create a function to replace placeholders
    function loadEmailTemplate($filePath, $data) {
        
        // Optional: log what you're loading
        file_put_contents("log.txt", "📤 Loading template from: $filePath\n", FILE_APPEND);

        $template = file_get_contents($filePath); // Load HTML file
        //$emailBody = "Template found successfully.";  // ← Just test it comment out when ready
        // Replace placeholders with real data
        foreach ($data as $key => $value) {
            //file_put_contents("log.txt", "loop:". $key." value ". $value."\n", FILE_APPEND);
            $template = str_replace("[$key]", $value, $template);
        }
        
        return $template;
    }
    
    public function meGetAction() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    
        if (empty($_SESSION['user_id'])) {
            $this->sendOutput(
                json_encode(["error" => "Unauthenticated"]),
                ['Content-Type: application/json', 'HTTP/1.1 401 Unauthorized']
            );
            return;
        }
    
        $userModel = new UserModel();
        $user = $userModel->getUserById($_SESSION['user_id']);
    
        if (!$user) {
            session_destroy();
            $this->sendOutput(
                json_encode(["error" => "Invalid session"]),
                ['Content-Type: application/json', 'HTTP/1.1 401 Unauthorized']
            );
            return;
        }
    
        // PUBLIC SAFE SHAPE
        $response = [
            "id" => $user['id'],
            "email" => $user['email'],
            "name" => $user['first_name'],
        ];
    
        $this->sendOutput(
            json_encode($response),
            ['Content-Type: application/json', 'HTTP/1.1 200 OK']
        );
    }

    
}