<?php

/* TODO: make all calls have an assignable response vs always returning 200 */

class AuthTelemetryController extends BaseController
{
    
        /**
    *
    * "/authtelemetry/telemetry" POST Endpoint
    * Backend logging auditing Authentication system. login  
    * params(eventtype, metadata, timestamp)
    */
    public function telemetryPostAction(){
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
         // Retrieve the user agent string from the $_SERVER array
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $strErrorDesc = '';
        $responseData = '';
        // Read JSON input
        $inputJSON = file_get_contents("php://input");
        file_put_contents("authtelemetry_log.txt", "\nRaw AuthTelemetryController telemetryPostAction() POST data: " . $inputJSON . "\n", FILE_APPEND);
        $data = json_decode($inputJSON, true);
        $metadata = $data['metadata'] ?? [];
        $metadataString = implode(", ", $metadata);
        // Basic validation
        // $data = $request->validate([
        //     'event'    => 'required|string|max:100',
        //     'userId'   => 'nullable|string|max:255',
        //     'metadata' => 'nullable|array',
        // ]);

        // Enrich telemetry with request context  //database -> id	event	ip_address	user_agent	metadata	timestamp	user_id	

        $telemetry = [
            'event'     => $data['event'],
            'ip_address' => $ipAddress,
            'user_agent'=> substr($user_agent, 0, 255),
            'metadata'  => $metadataString,
            'timestamp' => $data['timestamp'] ?? null,
            'user_id'   => $data['userId'] ?? null,
        ];
        

        
        
        try {
        
       
            //clean the data before sending 
            $authenticationModel = new AuthTelemetryModel();
            $result = $authenticationModel->store($telemetry);
            // Never block auth flows — telemetry must be safe
            $response = [
                        "success" => true,
                        "message" => "System audit saved.",
                    ];
                        
            $responseData = json_encode($response);
        } catch (Error $e) {

           file_put_contents("authtelemetry_log.txt", "\nRaw Something errored at store  report telemetryPostAction() POST data: " . $e->getMessage(). "\n", FILE_APPEND);
            // Never block auth flows — telemetry must be safe
            $response = [
                        "success" => true,
                        "message" => "System audit saved.",
                    ];
                        
            $responseData = json_encode($response);
        }
        // send output
       if (!$strErrorDesc) {
           $this->sendOutput(
               $responseData,
               array('Content-Type: application/json', 'HTTP/1.1 200 OK')
           );
       } 
    }
    
    
    
    
}