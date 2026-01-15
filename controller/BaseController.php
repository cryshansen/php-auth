<?php
class BaseController
{
    /**
     * __call magic method.
     */
    public function __call($name, $arguments)
    {
        $this->sendOutput('', array('HTTP/1.1 404 Not Found'));
    }
 
    /**
     * Get URI elements.
     * 
     * @return array
     */
    protected function getUriSegments()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = explode( '/', $uri );
 
        return $uri;
    }
 
    /**
     * Get querystring params.
     * 
     * @return array
     */
    protected function getQueryStringParams()
    {
        parse_str($_SERVER['QUERY_STRING'], $query);
        return $query;
    }
 
    /**
     * Send API output.
     *
     * @param mixed  $data
     * @param string $httpHeader
     */
    protected function sendOutput($data, $httpHeaders=array())
    {
        $buffer = ob_get_contents();
        if ($buffer !== '' && DEBUG_MODE) {
            file_put_contents(
                "output_leak.log",
                "LEAK:\n" . $buffer . "\n----\n",
                FILE_APPEND
            );
        }
        ob_clean();

        
        if (is_array($httpHeaders) && count($httpHeaders)) {
            foreach ($httpHeaders as $httpHeader) {
                header($httpHeader);
            }
        }
        
        echo is_string($data) ? $data : json_encode($data); //$data;
        exit;
    }
    
    /**
    * need to contact the host to get guzzle or curl working to allow apis
    */
    protected function verifyCaptcha($captchaResponse) {
        // Use environment variable for reCAPTCHA secret
        $secret_key = getenv('RECAPTCHA_SECRET_KEY');
        if (!$secret_key) {
            file_put_contents("e_log.txt", "Error: RECAPTCHA_SECRET_KEY not set in environment\n", FILE_APPEND);
            return false;
        }
        $url = "https://www.google.com/recaptcha/api/siteverify";

        // Prepare data
        $data = [
            "secret" => $secret_key,
            "response" => $captchaResponse,
            "remoteip" => $_SERVER["REMOTE_ADDR"]
        ];

        // Use cURL to send request to Google
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch);
        curl_close($ch);

        // Decode JSON response
        $result = json_decode($response, true);
        return $result["success"];
    }
    
    /* testing if this works vs curl */
     protected function verifyCaptchaV3($captchaResponse) {
        $secret = getenv('RECAPTCHA_SECRET_KEY');
        if (!$secret) {
            file_put_contents("e_log.txt", "Error: RECAPTCHA_SECRET_KEY not set in environment\n", FILE_APPEND);
            return false;
        }
        $url = "https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=$captchaResponse";
        file_put_contents("log.txt", "\nRaw in verifyCaptchaV3 POST \n", FILE_APPEND);
        $response = file_get_contents($url);
        $result = json_decode($response, true);
        
        file_put_contents("log.txt", "\nRaw verifyCaptchaV3 POST data: " . print_r($result, true) . "\n", FILE_APPEND);

                
        // Validate structure before accessing
        if (!is_array($result) || !isset($result['success']) || !isset($result['score'])) {
            file_put_contents("log.txt", "\nRaw verifyCaptchaV3 POST data: " . $response . "\n", FILE_APPEND);
            return false;
        }
        
        return $result['success'] === true && $result['score'] > 0.5;
     }
     protected function generateVerificationNo(){
         
        $verificationToken = bin2hex(random_bytes(16)); 
        
        return $verificationToken;
        
     }
     
     
}