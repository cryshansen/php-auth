<?php
class Database
{
    protected $connection = null;
 /*TODO: Production hardening
 
 
 if (count($params) !== substr_count($sql, ':')) {
    throw new Exception("Parameter count mismatch in SQL");
}
*/
    public function __construct()
    {
        
        try {
 
            $this->connection = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE_NAME, DB_USERNAME, DB_PASSWORD);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            file_put_contents("log.txt", "\nConnected successfully to the database!\n", FILE_APPEND);

           // echo "Connected successfully to the database!";

        } catch (PDOException $e) {
            // Catch any PDOException that occurs during connection
            // Display the error message
            //echo "Connection failed: " . $e->getMessage();
             file_put_contents("log.txt", "\nConnection failed!". $e->getMessage()."\n", FILE_APPEND);
        }       
    }
    
    public function lastInsertId() {
        $last_id=$this->connection->lastInsertId();
        //file_put_contents("log.txt", "\nRaw Database Model insert: ".$last_id." \n", FILE_APPEND);
        return $last_id;
    }


    public function select($query = "" , $params = [])
    {
        if (LOG_SENSITIVE_DATA) {
            file_put_contents("log.txt", "select: ".print_r($params) . "\n", FILE_APPEND);
        }
        try {
            $stmt = $this->executeStatement( $query , $params );
            $result =  $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (LOG_SENSITIVE_DATA) {
                file_put_contents("log.txt", "\nSelect Result::" .  print_r($result, true) . "\n", FILE_APPEND);
            }

            return $result;
        } catch(Exception $e) {
            throw New Exception( $e->getMessage() );
        }
        return false;
    }
    
    
    private function executeStatement($query = "" , $params = [])
    {
        try {
            $stmt = $this->connection->prepare( $query );
 
            if($stmt === false) {
                if (LOG_SENSITIVE_DATA) {
                    file_put_contents("log.txt", "\nRaw Unable to do prepared statement: ".$query." data:" .  print_r($params, true) . "\n", FILE_APPEND);
                }
                throw New Exception("Unable to do prepared statement: " . $query);
                
            }
 
            $stmt->execute($params);
            if (LOG_SENSITIVE_DATA) {
                file_put_contents("log.txt", "\n ExecuteStatement Prepared statement: ".$query." data:" .  print_r($params, true) . "\n", FILE_APPEND);
            }
 
            return $stmt;
        } catch(Exception $e) {
            throw New Exception( $e->getMessage() );
        }   
    }
    public function insert($sql = "" , $data = [])
    {
        try {
            if (LOG_SENSITIVE_DATA) {
                file_put_contents("log.txt", "\nRaw Database Model insert: ".$sql." data:" .  print_r($data, true) ."\n", FILE_APPEND);
            }
 
            $stmt= $this->executeInsert($sql , $data );
           //database message logging
            if ($stmt) { //true 
             $start = new DateTime();
            /*do not send messsage only stmt here back to the model */
                $result ="Database Model Insert success! " .$start->format('Y-m-d H:i:s');
                 file_put_contents("log.txt","Confirm Insert ". $result."\n", FILE_APPEND );
            }else{ 
                $result = "Something failed on Database Model Insert.";
                file_put_contents("log.txt","Database Model Insert ". $result."\n", FILE_APPEND );
               
            }

            return $stmt;

        } catch(Exception $e) {
            throw New Exception( $e->getMessage() );
        }
        return false;
    }
    
    public function update($sql = "" , $data = []){
       try {
            if (LOG_SENSITIVE_DATA) {
                file_put_contents("log.txt", "\nRaw Database Model update: ".$sql." data:" .  print_r($data, true) . "\n", FILE_APPEND);
            }
 
            $stmt= $this->executeUpdate($sql , $data );
           if ($stmt) { //true 
            /*do not send messsage only stmt here back to the model */
                $result ="Database Model Insert success!" ;
                 file_put_contents("log.txt","Confirm Insert ". print_r($result,TRUE), FILE_APPEND );
            }else{ 
                $result = "Something failed on Database Model Insert.";
                file_put_contents("log.txt","Database Model Insert ". print_r($result,TRUE), FILE_APPEND );
               
            }

            return $stmt;
       }catch(Exception $e) {
            throw New Exception( $e->getMessage() );
       }
    }
    private function executeInsert($query = "" , $params = [])
    {
        try {
            
            if (LOG_SENSITIVE_DATA) {
                file_put_contents("log.txt", "\nRaw Database Model executeInsert: ".$query." data:" .  print_r($params, true) . "\n", FILE_APPEND);
            }
            $stmt = $this->connection->prepare( $query );
 
            if($stmt === false) {
                file_put_contents("log.txt", "\nRaw Unable to do prepared statement: ".$query." data:" .  print_r($params, true) . "\n", FILE_APPEND);
                throw New Exception("Unable to do prepared statement: " . $query);
            }
 
            

             $result = $stmt->execute($params);
            if (LOG_SENSITIVE_DATA) {
                file_put_contents("log.txt", "\nDatabase Model executeInsert: after execute ".$query." data:" .  print_r($params, true) . "\n", FILE_APPEND);
            }
            
            
            if (!$result) {
                $error = $stmt->errorInfo();
                file_put_contents("log.txt", "Error info: " . print_r($error, true) . "\n", FILE_APPEND);
            }
            
            return $result;
        } catch(Exception $e) {
            throw New Exception( $e->getMessage() );
        }  
        
    }
    
    private function executeUpdate($query = "" , $data = [])
    {
        
        try {
            $stmt = $this->connection->prepare( $query );
 
            if($stmt === false) {
                file_put_contents("log.txt", "\nDatabase Model executeUpdate: unable to prepare ".$query." data:" .  print_r($data, true) . "\n", FILE_APPEND);
                throw New Exception("Unable to do prepared statement: " . $query);
            }
 
            /*if( $params ) {
                //if param is string: Names can be prefixed with colons ":" too (optional)
                $stmt->bindParam($data[0], $data[1],PDO::PARAM_STR);
                /* examples: $sth->bindParam('calories', $calories, PDO::PARAM_INT); */
                /* Names can be prefixed with colons ":" too (optional) */
                /*$sth->bindParam(':colour', $colour, PDO::PARAM_STR);*/
            /*}*/
 
            $stmt->execute($data);
 
            return $stmt;
        } catch(Exception $e) {
            throw New Exception( $e->getMessage() );
        }   
    }
    
    
    
    
}