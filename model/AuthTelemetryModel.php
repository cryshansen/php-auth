<?php

require_once "database.php"; 

class AuthTelemetryModel extends Database
{
    // Enriched telemetry fields
    // $telemetry = [
    //     'event'     => $data['event'],
    //     'user_id'   => $data['userId'] ?? null,
    //     'ip'        => $request->ip(),
    //     'user_agent'=> substr($request->userAgent(), 0, 255),
    //     'metadata'  => $data['metadata'] ?? [],
    //     'timestamp' => now()->toISOString(),
    // ];
    public function store($telemetry)
    {
        


        // Never block auth flows — telemetry must be safe
        try {
            // Log to file (recommended first step)
            file_put_contents("authtelemetry_log.txt", "store  AuthTelemetryModel :  data: " .print_r($telemetry,true). "\n", FILE_APPEND);
            //SELECT `id`, `event`, `ip_address`, `user_agent`, `metadata`, `timestamp` `user_id`  FROM `auth_telemetry` WHERE 1
            $sql = "INSERT INTO auth_telemetry (`event`, `ip_address`, `user_agent`, `metadata`, `timestamp`, `user_id`) VALUES (:event, :ip_address, :user_agent,:metadata, :timestamp, :user_id )";

            file_put_contents("authtelemetry_log.txt", "store  AuthTelemetryModel :  sql: " .print_r($sql,true). "\n", FILE_APPEND);
            return $this->insert($sql,$telemetry);

            // TODO (later): forward to SIEM / analytics
            
        } catch (\Throwable $e) {
            // Fail silently
             file_put_contents("authtelemetry_log.txt", "store  AuthTelemetryModel :  error: " .$e->getMessage(). "\n", FILE_APPEND);
        }
        
        // Never block auth flows — telemetry must be safe
        return response()->json([
            'success' => true,
        ]);
    }
    
    
}