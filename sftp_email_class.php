<?php
  /*
          File: sftp_email_cl.php
       Created: 07/22/2020
       Updated: 08/03/2020
    Programmer: Cuates
    Updated By: Cuates
       Purpose: Class for all SFTP and Email interactions
  */

  // Include configuration file
  include ("sftp_email_config.php");

  // Set include path
  // Third party library needs to be downloaded from the internet and configured for the server system
  set_include_path(get_include_path() . PATH_SEPARATOR . 'phpseclib/');

  // Include dependent files
  require_once ('phpseclib/Net/SFTP.php');

  // Require once advance mail class file
  require_once ("mailclass.php");

  // Create the class for the db web service class
  class sftp_email_cl extends sftp_email_config
  {
    // PHP 5+ Style constructor
    public function __construct()
    {
      // This function needs to be here so the class can be executed when called
      // Create object of mail class
      $this->mcl = new mailclass();
    }

    // PHP 4 Style constructor
    public function sftp_email_cl()
    {
      // Call the constructor
      self::__construct();
    }

    // Open connection function to an internal or external server
    private function openConnection($type = "notype")
    {
      // Set variable
      $returnArray = array();

      // Try to execute the command(s)
      try
      {
        // Set variables with database settings
        $this->setConfigVars($type);

        // Set array to variable
        $conVars = $this->getConfigVars();

        // Set all credentials and information
        $this->Driver = reset($conVars); // Driver
        $this->Server = next($conVars); // Server name
        $this->Port = next($conVars); // Server port
        $this->Database = next($conVars); // Database name
        $this->User = next($conVars); // User name
        $this->Pass = next($conVars); // Password
        $this->URL = next($conVars); // URL
        $this->URLAPI = next($conVars); // URL API
        $this->RemotePath = next($conVars); // Remote directory
        $this->subscriptionKey = next($conVars); // Subscription Key
        $this->appKey = next($conVars); // App Key

        // Check database name. The data Name is set to make sure that we are connecting with a database
        if(preg_match('/MSSQL<Database_Name>[a-zA-Z]{1,}/i', $type))
        {
          // error_log('odbc:Driver=' . $this->Driver . '; Servername=' . $this->Server . '; Port=' . $this->Port . '; Database=' . $this->Database . '; UID=' . $this->User . '; PWD=' . $this->Pass . '; Type=' . $type);

          // Connect to a database
          $this->pdo = new PDO('odbc:Driver=' . $this->Driver . '; Servername=' . $this->Server . '; Port=' . $this->Port . '; Database=' . $this->Database . '; UID=' . $this->User . '; PWD=' . $this->Pass . ';'); // The developer will need to configure the driver of choice

          // Throw exception if given by the database server
          // This will help when the database returns a hard error
          $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        else if(preg_match('/PGSQL<Database_Name>[a-zA-Z]{1,}/i', $type))
        {
            // error_log('pgsql:host=' . $this->Server . '; port=' . $this->Port . '; dbname=' . $this->Database . '; user=' . $this->User . '; password=' . $this->Pass . ';');

            // Connect to a database
            $this->pdo = new PDO('pgsql:host=' . $this->Server . '; port=' . $this->Port. '; dbname=' . $this->Database . '; user=' . $this->User . '; password=' . $this->Pass . ';'); // The developer will need to configure the driver of choice

            // Throw exception if given by the database server
            // This will help when the database returns a hard error
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        else if(preg_match('/MySQL<Database_Name>[a-zA-Z]{1,}/i', $type))
        {
            // error_log('mysql:host=' . $this->Server . '; port=' . $this->Port . '; dbname=' . $this->Database . ', user=' . $this->User . ', password=' . $this->Pass);

            // Connect to a database
            $this->pdo = new PDO('mysql:host=' . $this->Server . '; port=' . $this->Port. '; dbname=' . $this->Database, $this->User, $this->Pass); // The developer will need to configure the driver of choice

            // Throw exception if given by the database server
            // This will help when the database returns a hard error
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        else if(preg_match('/^<SFTP_Name>$/i', $type))
        {
          // Create an object with connection
          $this->sftp = new \phpseclib\Net\SFTP($this->Server, $this->Port);

          // Check if user has proper credentials
          if ($this->sftp->login($this->User, $this->Pass))
          {
            // Error log connection to the end user's server
          }
          else
          {
            // Set message
            $returnArray = array('SError' => trim('SFTP Failed to Establish a Connection'));

            // Error log database connection error
            // error_log(print_r($returnArray, true));
          }
        }
        else
        {
          // Set message
          $returnArray = array('SError' => trim('Cannot connect to the database/SFTP'));

          // Error log database connection error
          // error_log(print_r($returnArray, true));
        }
      }
      catch (PDOException $e)
      {
        // Otherwise retains and outputs the potential error
        // Set message
        $returnArray = array('SError' => trim('Caught - PDO cannot connect to the database - ' . $e->getMessage()));

        // Error log database connection error
        // error_log(print_r($returnArray, true));

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }
      catch (Exception $e)
      {
        // Catch the error from the try section of code
        // Set message
        $returnArray = array('SError' => trim('Caught - cannot connect to the database/SFTP - ' . $e->getMessage()));

        // Error log database connection error
        // error_log(print_r($returnArray, true));

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }

      // Return message
      return $returnArray;
    }
    //**---------- Do not modify anything above this commented line ----------**//

    // Update sequence
    public function updateSequence($param01)
    {
      // Query
      $query = "<Stored_Procedure_Name> @optionMode = '<Option_Mode_Name>', @param01 = :param01";

      // Define values into key pair array
      $params = array(
        ':param01' => trim($param01)
      );

      // Return single attribute from the database
      return $this->retrieveSingleValue('Column01', $query, $params, '1', '1', '<Database_Name>');
    }

    // Register Data
    function registerData($param01)
    {
      // Query
      $query = "<Stored_Procedure_Name> @optionMode = '<Option_Mode_Name>', @param01 = :param01";

      // Define values into key pair array
      $params = array(
        ':param01' => trim($param01)
      );

      // Return single attribute from the database
      return $this->retrieveSingleValue('Column01', $query, $params, '1', '1', '<Database_Name>');
    }

    // Retrieve data
    function getData($param01)
    {
      // Set title array for sat air
      $titleArray = array('Column 01','Column 02', 'Column 03', 'Column 04');

      // Query
      $query = "<Stored_Procedure_Name> @optionMode = '<Option_Mode_Name>', @param01 = :param01";

      // Define values into key pair array
      $params = array(
        ':param01' => trim($param01)
      );

      // Return single attribute from the database
      return $this->retrieveMultipleAttribute($titleArray, $query, $params, '1', '1', '<Database_Name>');
    }

    // Extract ID numbers
    function extractIDData($param01)
    {
      // Query
      $query = "<Stored_Procedure_Name> @optionMode = '<Option_Mode_Name>', @param01 = :param01";

      // Define values into key pair array
      $params = array(
        ':param01' => trim($param01)
      );

      // Return single attribute from the database
      return $this->retrieveSingleAttribute('Column01', $query, $params, '1', '1', '<Database_Name>');
    }

    // Validate data in database
    function validateData($param01, $param02)
    {
      // Query
      $query = "<Stored_Procedure_Name> @optionMode = '<Option_Mode_Name>', @param01 = :param01, @param02 = :param02";

      // Define values into key pair array
      $params = array(
        ':param01' => trim($param01),
        ':param02' => trim($param02)
      );

      // Return single attribute from the database
      return $this->retrieveSingleValue('Column01', $query, $params, '1', '1', '<Database_Name>');
    }

    //------- Do not modify anything below this commented line -------//
    // Convert accent characters from other countries to en US characters
    public function enUSCharsConvert($inputString)
    {
      // Array containing characters that cannot be converted by iconv function
      $replace = array('ъ' => '-', 'Ь' => '-', 'Ъ' => '-', 'ь' => '-', 'а' => 'a', 'А' => 'a', 'א' => 'A', 'א' => 'a', 'Þ' => 'B', 'þ' => 'b', 'б' => 'b', 'Б' => 'b', 'ב' => 'b', '©' => 'c', 'ц' => 'c', 'Ц' => 'c', 'ץ' => 'C', 'צ' => 'c', 'Ч' => 'ch', 'ч' => 'ch', 'ד' => 'd', 'Đ' => 'd', 'đ' => 'd', 'д' => 'd', 'Д' => 'D', 'ð' => 'd', 'є' => 'e', 'Є' => 'e', 'ע' => 'e', 'е' => 'e', 'Е' => 'e', 'Ə' => 'e', 'ə' => 'e', 'ф' => 'f', 'Ф' => 'f', 'ƒ' => 'f', 'Г' => 'g', 'г' => 'g', 'ג' => 'g', 'Ґ' => 'g', 'ґ' => 'g', 'ח' => 'h', 'ħ' => 'h', 'Ħ' => 'h', 'Х' => 'h', 'х' => 'h', 'ה' => 'h', 'ı' => 'i', 'И' => 'i', 'и' => 'i', 'י' => 'i', 'Ї' => 'i', 'ї' => 'i', 'І' => 'i', 'і' => 'i', 'й' => 'j', 'Й' => 'j', 'я' => 'ja', 'Я' => 'ja', 'Э' => 'je', 'э' => 'je', 'ё' => 'jo', 'Ё' => 'jo', 'ю' => 'ju', 'Ю' => 'ju', 'ĸ' => 'k', 'כ' => 'k', 'К' => 'k', 'к' => 'k', 'ך' => 'k', 'Ŀ' => 'l', 'ŀ' => 'l', 'Л' => 'l', 'л' => 'l', 'מ' => 'm', 'ל' => 'l', 'М' => 'm', 'м' => 'm', 'ם' => 'm', 'н' => 'n', 'Н' => 'n', 'ן' => 'n', 'ŋ' => 'n', 'Ŋ' => 'n', 'נ' => 'n', 'Ø' => 'O', 'ø' => 'o', 'о' => 'o', 'О' => 'o', 'Ø' => 'O', 'ø' => 'o', 'ף' => 'p', 'פ' => 'p', 'п' => 'p', 'П' => 'p', 'ר' => 'r', 'ק' => 'q', '®' => 'r', 'Р' => 'r', 'р' => 'r', 'с' => 's', 'С' => 's', 'ס' => 's', 'Щ' => 'sch', 'щ' => 'sch', 'ш' => 'sh', 'Ш' => 'sh', '™' => 'tm', 'т' => 't', 'Т' => 't', 'ט' => 't', 'ŧ' => 't', 'Ŧ' => 't', 'ת' => 't', 'у' => 'u', 'У' => 'u', 'в' => 'v', 'В' => 'v', 'ו' => 'v', 'ש' => 'w', 'ы' => 'y', 'Ы' => 'y', 'З' => 'z', 'з' => 'z', 'ז' => 'z', 'Ж' => 'zh', 'ж' => 'zh');

      // Translate characters or replace substrings
      $inputString = strtr($inputString, $replace);

      // Convert string to requested character encoding
      $inputString = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $inputString);

      // Return string with modifications
      return $inputString;
    }

    // Validate web service headers
    public function validateWebServiceCall($valRequestMethod, $validRequestMethod, $valHttpAccept, $valContentType, $valHttpAcceptCharSet)
    {
      // Initialize array
      $returnArray = array();

      // Try to execute the following code
      try
      {
        // Check if Request Method is provided
        if ($valRequestMethod === $validRequestMethod)
        {
          // Check if HTTP Accept is provided
          if ($valHttpAccept === "application/json")
          {
            // Check if Content Type is provided
            if ($valContentType === "application/json")
            {
              // Check if HTTP Accept Charset is provided
              if ($valHttpAcceptCharSet === "UTF-8")
              {
                // Store parameters into array
                $returnArray = array("SRes" => "Success", "SMesg" => "Request Processed");
              }
              else
              {
                // Set error message
                $returnArray = array("SRes" => "Error", "SMesg" => "HTTP accept charset invalid");
              }
            }
            else
            {
              // Set error message
              $returnArray = array("SRes" => "Error", "SMesg" => "Content type invalid");
            }
          }
          else
          {
            // Set error message
            $returnArray = array("SRes" => "Error", "SMesg" => "HTTP accept invalid");
          }
        }
        else
        {
          // Set error message
          $returnArray = array("SRes" => "Error", "SMesg" => "Request method invalid");
        }
      }
      catch(Exception $e)
      {
        // Catch the error from the try section of code
        // Reset array to error message
        error_log('Error~Issue with validate Web Service Call - ' . $e->getMessage());

        // error log the caught exception
        error_log($e->getMessage());

        // Set variable
        $returnArray = array("SRes" => "Error", "SMesg" => "Issue with validate Web Service Call");
      }

      // Return result
      return $returnArray;
    }

    // Validate web service headers JSON
    public function validateJSONWebServiceCall($valRequestMethod, $validRequestMethod, $valHttpAccept, $valContentType, $valHttpAcceptCharSet, $valPayload)
    {
      // Initialize array
      $returnArray = array();
      $payloadArray = array();

      // Try to execute the following code
      try
      {
        // Convert payload value to JSON
        $valPayload = ($valPayload !== "") ? json_decode($valPayload, true) : trim("");

        // Validate payload for JSON
        $validatePayload = ($valPayload !== "") ? json_last_error() : trim("");

        // Check if Request Method is provided
        if ($valRequestMethod === $validRequestMethod)
        {
          // Check if HTTP Accept is provided
          if ($valHttpAccept === "application/json")
          {
            // Check if Content Type is provided
            if ($valContentType === "application/json")
            {
              // Check if HTTP Accept Charset is provided
              if ($valHttpAcceptCharSet === "UTF-8")
              {
                // Check if payload is in proper format
                if ($validatePayload === JSON_ERROR_NONE)
                {
                  // Set result array and change key case
                  $payloadArray = $this->array_change_key_case_recursive($valPayload, CASE_LOWER);

                  // Check if not server error
                  if(!isset($payloadArray['SError']) && !array_key_exists('SError', $payloadArray))
                  {
                    // Store parameters into array
                    $returnArray = array("SRes" => "Success", "SMesg" => "Request Processed", "Payload" => $payloadArray);
                  }
                  else
                  {
                    // Store parameters into array
                    // $returnArray = $payloadArray;
                    $returnArray = array("SRes" => "Error", "SMesg" => "Array Change Key Case Recursive Error", "Payload" => $payloadArray);
                  }
                }
                else if ($validatePayload === "")
                {
                  // Set error message
                  $returnArray = array("SRes" => "Error", "SMesg" => "Payload/Parameter was not provided", "Payload" => $payloadArray);
                }
                else
                {
                  // Set error message
                  $returnArray = array("SRes" => "Error", "SMesg" => "Payload syntax invalid", "Payload" => $payloadArray);
                }
              }
              else
              {
                // Set error message
                $returnArray = array("SRes" => "Error", "SMesg" => "HTTP accept charset invalid", "Payload" => $payloadArray);
              }
            }
            else
            {
              // Set error message
              $returnArray = array("SRes" => "Error", "SMesg" => "Content type invalid", "Payload" => $payloadArray);
            }
          }
          else
          {
            // Set error message
            $returnArray = array("SRes" => "Error", "SMesg" => "HTTP accept invalid", "Payload" => $payloadArray);
          }
        }
        else
        {
          // Set error message
          $returnArray = array("SRes" => "Error", "SMesg" => "Request method invalid", "Payload" => $payloadArray);
        }
      }
      catch(Exception $e)
      {
        // Catch the error from the try section of code
        // Reset array to error message
        error_log('Error~Issue with validate JSON Web Service Call - ' . $e->getMessage());

        // error log the caught exception
        error_log($e->getMessage());

        // Set variable
        $returnArray = array("SRes" => "Error", "SMesg" => "Issue with validate JSON Web Service Call", "Payload" => $payloadArray);
      }

      // Return result
      return $returnArray;
    }

    // Recursively change array key case
    private function array_change_key_case_recursive($arr, $case = CASE_LOWER)
    {
      // Return array
      $returnArray = array();

      // Function to catch exception errors
      set_error_handler(function ($errno, $errstr, $errfile, $errline)
      {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
      });

      // Try to execute the following code
      try
      {
        // Set array with modified array
        // Use keyword makes a copy of the parent variable to be used in the recursion call
        $returnArray = array_map(function ($item) use ($case)
          {
            // Check if the current item is an array
            if(is_array($item))
            {
              // Recursively loop through the next array
              $item = $this->array_change_key_case_recursive($item, $case);
            }

            // Return element
            return $item;
          }
          , array_change_key_case($arr, $case)
        );
      }
      catch (Exception $e)
      {
        // Catch the error from the try section of code
        // Set message
        $returnArray = array('SError' => trim('Caught array change key case recursive Execution Failure - ' . $e->getMessage()));

        // Error log failure
        //error_log($returnArray);

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }

      // Return array
      return $returnArray;
    }

    // Notify developer
    public function notifyDeveloper($downloadDir, $errorFilename, $colHeaderArray, $errormessagearray, $to, $to_cc, $to_bcc, $fromEmail, $fromName, $replyTo, $subject, $headers, $message, $xPriority)
    {
      // Write to file
      $writeToFileStatus = $this->writeToFile($downloadDir, $errorFilename, $errormessagearray, $colHeaderArray);

      // Explode database message
      $writeToFileStatusArray = explode('~', $writeToFileStatus);

      // Set response message
      $writeToFileStatusResp = reset($writeToFileStatusArray);
      $writeToFileStatusMesg = next($writeToFileStatusArray);

      // Check if an error message was returned from the class file
      if(trim($writeToFileStatusResp) === "Success")
      {
        // Attach any file to the email
        $this->mcl->mailWithAttachment($errorFilename, $downloadDir, $to, $fromEmail, $fromName, $replyTo, $subject, $message, $to_cc, $to_bcc, $xPriority);
      }
      else
      {
        // Display error string
        // error_log("Error Message Write To File (Error) - " . $writeToFileStatusResp . ': ' . $writeToFileStatusMesg);

        // Send email to software engineers for unsent email
        mail($to, $subject, $message, $headers);
      }
    }

    // Notify developer
    public function notifyEndUser($sendFilename, $DOWNLOADDIR, $to, $fromEmail, $fromName, $replyTo, $subject, $headers, $message, $to_cc, $to_bcc, $xPriority)
    {
      // Check if an error message was returned from the class file
      if(trim($sendFilename) !== "")
      {
        // Attach any file to the email
        $this->mcl->mailWithAttachment($sendFilename, $DOWNLOADDIR, $to, $fromEmail, $fromName, $replyTo, $subject, $message, $to_cc, $to_bcc, $xPriority);
      }
      else
      {
        // Send email to software engineers for unsent email
        mail($to, $subject, $message, $headers);
      }
    }

    // Write to File
    public function writeToFile($path, $filename, $content, $colHeaders)
    {
      // Initialize variables
      $returnValue = "";

      // Function to catch exception errors
      set_error_handler(function ($errno, $errstr, $errfile, $errline)
      {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
      });

      // Try to execute the following code
      try
      {
        // Create file handle
        $fp = fopen($path . $filename, 'w');

        // Check if the column header is an array
        if (is_array($colHeaders))
        {
          // Loop through array and write to file
          foreach ($colHeaders as $colHeadVal)
          {
            // Write to file
            fputcsv($fp, $colHeadVal);
          }
        }
        else
        {
          // Write to file
          fwrite($fp, $colHeaders);
        }

        // Check if content is an array
        if (is_array($content))
        {
          // Loop through array and write to file
          foreach ($content as $val)
          {
            // Write to file
            fputcsv($fp, $val);
          }

          // Gets information about a file using an open file pointer
          $stat = fstat($fp);

          // Truncates a file to a given length
          $truncateResponse = ftruncate($fp, $stat['size'] - 1);
        }
        else
        {
          // Write to file
          fwrite($fp, $content);
        }

        // Close file handle
        fclose($fp);

        // Check if was written
        if (file_exists($path . $filename) && trim($filename) !== "")
        {
          // Set variable value
          $returnValue = "Success~File was written to server";
        }
        else
        {
          // Set variable value
          $returnValue = "Error~File not written to server";

          // Error log message
          // error_log($returnValue);
        }
      }
      catch (Exception $e)
      {
        // Catch the error from the try section of code
        // Set message
        $returnValue = trim('SError~Caught write to file Execution Failure - ' . $e->getMessage());

        // Error log failure
        // error_log($returnValue);

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }

      // Return variable
      return $returnValue;
    }

    // Put file onto the server
    public function putSFTPFile($filename, $type, $localPath)
    {
      // Initialize variable
      $returnValue = "";

      // Function to catch exception errors
      set_error_handler(function ($errno, $errstr, $errfile, $errline)
      {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
      });

      // Try to execute the following code
      try
      {
        // Set array
        $connectionStatus = array();

        // Connect to SFTP server
        $connectionStatus = $this->openConnection($type);

        // Check if error with registering process
        if (!isset($connectionStatus['SError']) && !array_key_exists('SError', $connectionStatus))
        {
          // Set variable
          $filesize = "";

          // Put file on remote server [remote server]/[filename], [local server]/[filename]
          $this->sftp->put($this->RemotePath . $filename, $localPath . $filename, \phpseclib\Net\SFTP::SOURCE_LOCAL_FILE);

          // Check if the file has been placed in the correct folder
          /*
          echo $this->sftp->size($remotePath . $filename);
          print_r($this->sftp->stat($remotePath . $filename));
          print_r($this->sftp->lstat($remotePath . $filename));
          */

          // Set the file size to the variable for the return statement
          $filesize = $this->sftp->size($this->RemotePath . $filename);

          // Check if file size is not empty
          if (trim($filesize) !== "")
          {
            // Set message
            $returnValue = trim('Success~' . $filename . " with size of: " . $filesize . " has been transmitted.");

            // Error log message
            error_log($returnValue);
          }
          else
          {
            // Set message
            $returnValue = trim('Error~' . $filename . " was not transmitted.");

            // Error log message
            // error_log($returnValue);
          }

          // Check if the SFTP server is still connected
          if ($this->sftp)
          {
            // Close connection
            $this->sftp->disconnect();
            $this->sftp = null;
          }
        }
        else
        {
          // Else error has occurred
          $connectionServerMesg = reset($connectionStatus);

          // Set message
          $returnValue = trim('SError~Put - ' . $connectionServerMesg);

          // Display error string
          // error_log($returnValue);
        }
      }
      catch(Exception $e)
      {
        // Set message
        $returnValue = trim('SError~Caught Put SFTP File On Server Failure - ' . $e->getMessage());

        // Set message
        // error_log($returnValue);

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }

      // Return message
      return $returnValue;
    }

    // Get file from server
    public function getSFTPFile($filenameRemote, $type, $localPath, $filenameLocal)
    {
      // Initialize variable
      $returnValue = "";

      // Function to catch exception errors
      set_error_handler(function ($errno, $errstr, $errfile, $errline)
      {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
      });

      // Try to execute the following code
      try
      {
        // Set array
        $connectionStatus = array();

        // Connect to SFTP server
        $connectionStatus = $this->openConnection($type);

        // Check if error with registering process
        if (!isset($connectionStatus['SError']) && !array_key_exists('SError', $connectionStatus))
        {
          // Get file from remote server [remote server]/[filename], [local server]/[filename]
          $this->sftp->get($this->RemotePath . $filenameRemote, $localPath . $filenameLocal);

          // Check if was written
          if (file_exists($localPath . $filenameLocal) && trim($filenameLocal) !== "")
          {
            // Set variable value
            $returnValue = "Success~File was written to local server";
          }
          else
          {
            // Set variable value
            $returnValue = "Error~File not written to local server";

            // Error log message
            // error_log($returnValue);
          }

          // Check if the SFTP server is still connected
          if ($this->sftp)
          {
            // Close connection
            $this->sftp->disconnect();
            $this->sftp = null;
          }
        }
        else
        {
          // Else error has occurred
          $connectionServerMesg = reset($connectionStatus);

          // Set message
          $returnValue = trim('SError~Get - ' . $connectionServerMesg);

          // Display error string
          // error_log($returnValue);
        }
      }
      catch(Exception $e)
      {
        // Set message
        $returnValue = trim('SError~Caught Get SFTP File From Server Failure - ' . $e->getMessage());

        // Set message
        // error_log($returnValue);

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }

      // Return message
      return $returnValue;
    }

    // List file(s) from server
    public function listSFTPFiles($type, $extensions)
    {
      // Initialize variable
      $returnArray = array();

      // Function to catch exception errors
      set_error_handler(function ($errno, $errstr, $errfile, $errline)
      {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
      });

      // Try to execute the following code
      try
      {
        // Set array
        $connectionStatus = array();

        // Connect to SFTP server
        $connectionStatus = $this->openConnection($type);

        // Check if error with registering process
        if (!isset($connectionStatus['SError']) && !array_key_exists('SError', $connectionStatus))
        {
          // List file(s) from remote server [remote server]
          $list = $this->sftp->nlist($this->RemotePath);

          // Check if retrieval list is an array
          if (is_array($list))
          {
            // Process all file(s) within the return list
            foreach ($list as $item => $value)
            {
              // Explode the string to retrieve file extension
              $fileBreakDown = explode('.', $value);

              // Check if string contains any matching extensions in the array
              if (in_array(strtolower($fileBreakDown[count($fileBreakDown) - 1]), $extensions))
              {
                // Add string containing extension into the return array
                array_push($returnArray, $value);
              }
            }
          }
          else
          {
            // Set message
            $returnArray = array('SError' => trim('List Retrieval - ' . $list));
          }

          // Check if the SFTP server is still connected
          if ($this->sftp)
          {
            // Close connection
            $this->sftp->disconnect();
            $this->sftp = null;
          }
        }
        else
        {
          // Else error has occurred
          $connectionServerMesg = reset($connectionStatus);

          // Set message
          $returnArray = array('SError' => trim('Get - ' . $connectionServerMesg));

          // Display error string
          // error_log($returnArray);
        }
      }
      catch (Exception $e)
      {
        // Catch the error from the try section of code
        // Set message
        $returnArray = array('SError' => trim('Caught Get SFTP File From Server Failure - ' . $e->getMessage()));

        // Error log failure
        // error_log(print_r($returnArray, true));

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }

      // Return message
      return $returnArray;
    }

    // Move file around on server
    public function moveSFTPFile($filenameRemote, $type, $fromPath, $toPath, $localPath)
    {
      // Initialize variable
      $returnValue = "";

      // Function to catch exception errors
      set_error_handler(function ($errno, $errstr, $errfile, $errline)
      {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
      });

      // Try to execute the following code
      try
      {
        // Set array
        $connectionStatus = array();

        // Connect to SFTP server
        $connectionStatus = $this->openConnection($type);

        // Check if error with registering process
        if (!isset($connectionStatus['SError']) && !array_key_exists('SError', $connectionStatus))
        {
          // Set variable
          $filesize = "";
          $filesizeold = "";

          // Put file on remote server [remote server]/[filename], [local server]/[filename]
          $this->sftp->put($this->RemotePath . $toPath . $filenameRemote, $localPath . $filenameRemote, \phpseclib\Net\SFTP::SOURCE_LOCAL_FILE);

          // Check if the file has been placed in the correct folder
          /*
          echo $this->sftp->size($remotePath . $filename);
          print_r($this->sftp->stat($remotePath . $filename));
          print_r($this->sftp->lstat($remotePath . $filename));
          */

          // Set the file size to the variable for the return statement
          $filesize = $this->sftp->size($this->RemotePath . $toPath . $filenameRemote);

          // Check if file size is not empty
          if (trim($filesize) !== "")
          {
            // Put file on remote server [remote server]/[filename], [local server]/[filename]
            $this->sftp->delete($this->RemotePath . $fromPath . $filenameRemote);

            // Set the file size to the variable for the return statement
            $filesizeold = $this->sftp->size($this->RemotePath . $fromPath . $filenameRemote);

            // Check if file size is not empty
            if (trim($filesizeold) === "")
            {
              // Set message
              $returnValue = trim('Success~' . $filenameRemote . " with size of: " . $filesize . " has been archived and Original " . $filenameRemote . " was removed");

              // Error log message
              error_log($returnValue);
            }
            else
            {
              // Set message
              $returnValue = trim('Error~' . $filenameRemote . " with size of: " . $filesize . " has been archived and Original ". $filenameRemote . " was not removed");

              // Error log message
              // error_log($returnValue);
            }
          }
          else
          {
            // Set message
            $returnValue = trim('Error~' . $filenameRemote . " was not archived");

            // Error log message
            // error_log($returnValue);
          }

          // Check if the SFTP server is still connected
          if ($this->sftp)
          {
            // Close connection
            $this->sftp->disconnect();
            $this->sftp = null;
          }
        }
        else
        {
          // Else error has occurred
          $connectionServerMesg = reset($connectionStatus);

          // Set message
          $returnValue = trim('SError~Get - ' . $connectionServerMesg);

          // Display error string
          // error_log($returnValue);
        }
      }
      catch(Exception $e)
      {
        // Set message
        $returnValue = trim('SError~Caught Get SFTP File From Server Failure - ' . $e->getMessage());

        // Set message
        // error_log($returnValue);

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }

      // Return message
      return $returnValue;
    }

    // Retrieve no attributes with query only
    private function retrieveNoAttribute($query, $parameters, $SetNulls = "0", $SetWarnings = "0", $dbName = '<Database_Name>')
    {
      // Initialize variable
      $returnValue = "";

      // Function to catch exception errors
      set_error_handler(function ($errno, $errstr, $errfile, $errline)
      {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
      });

      // Try to execute the following code
      try
      {
        // Set array
        $connectionStatus = array();

        // Connect to database
        $connectionStatus = $this->openConnection($dbName);

        // Check if error with registering process
        if (!isset($connectionStatus['SError']) && !array_key_exists('SError', $connectionStatus))
        {
          // Check if set nulls is not empty
          if($SetNulls !== '0')
          {
            // Set ANSI_NULLS ON
            $commandOne = $this->pdo->prepare('SET ANSI_NULLS ON');
            $commandOne->execute();
          }

          // Check if set warnings is not empty
          if($SetWarnings !== '0')
          {
            // Set ANSI_WARNINGS ON
            $commandTwo = $this->pdo->prepare('SET ANSI_WARNINGS ON');
            $commandTwo->execute();
          }

          // Prepare query
          $sql = $this->pdo->prepare($query);

          // Execute the query with key pair values
          $sql->execute($parameters);

          // Un-comment the following block of code to see what the query string is sending to the database
          /*
          // Initialize parameters
          $final_string_one = array();
          $final_string_one = $parameters;
          $exQuery = "";
          $final_query = "";
          $final_query = $query;

          // Apply a user supplied function to every member of an array
          // Wrap all array elements in single quotes
          array_walk($final_string_one, function(&$str)
          {
            $str = "'" . $str . "'";
          });

          // Set the value with the parameter values to display in the string
          $exQuery = strtr($final_query, $final_string_one);

          // Displays the query string being used
          error_log('Query String: ' . $exQuery);
          */

          // The following call to closeCursor() may be required by some drivers
          // Closes the cursor, enabling the statement to be executed again
          $sql->closeCursor();

          // Check if the database server is still connected
          if ($this->pdo)
          {
            // Close connection
            $this->pdo = null;
            // $pdo = null;
          }
        }
        else
        {
          // Else error has occurred
          $connectionServerMesg = reset($connectionStatus);

          // Set message
          $returnValue = trim('SError~' . $connectionServerMesg);

          // Display message
          // error_log($returnValue);
        }
      }
      catch (PDOException $e)
      {
        // Catch the pdo error from the try section of code
        // Set message
        $returnValue = trim('SError~Caught PDO retrieve no attribute Execution Failure - ' . $e->getMessage());

        // Error log PDO failure
        // error_log($returnValue);

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }
      catch (Exception $e)
      {
        // Catch the error from the try section of code
        // Set message
        $returnValue = trim('SError~Caught retrieve no attribute Execution Failure - ' . $e->getMessage());

        // Display message
        // error_log($returnValue);

        // error log the caught exception
        error_log($e->getMessage());
      }

      // Return no value
      return $returnValue;
    }

    // Retrieve single value with attribute name and query
    private function retrieveSingleValue($attributeName, $query, $parameters, $SetNulls = "0", $SetWarnings = "0", $dbName = '<Database_Name>')
    {
      // Initialize variable
      $returnValue = "";

      // Function to catch exception errors
      set_error_handler(function ($errno, $errstr, $errfile, $errline)
      {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
      });

      // Try to execute the following code
      try
      {
        // Set array
        $connectionStatus = array();

        // Connect to database
        $connectionStatus = $this->openConnection($dbName);

        // Check if error with registering process
        if (!isset($connectionStatus['SError']) && !array_key_exists('SError', $connectionStatus))
        {
          // Check if set nulls is not empty
          if($SetNulls !== '0')
          {
            // Set ANSI_NULLS ON
            $commandOne = $this->pdo->prepare('SET ANSI_NULLS ON');
            $commandOne->execute();
          }

          // Check if set warnings is not empty
          if($SetWarnings !== '0')
          {
            // Set ANSI_WARNINGS ON
            $commandTwo = $this->pdo->prepare('SET ANSI_WARNINGS ON');
            $commandTwo->execute();
          }

          // Prepare query
          $sql = $this->pdo->prepare($query);

          // Execute the query with key pair values
          $sql->execute($parameters);

          // Un-comment the following block of code to see what the query string is sending to the database
          /*
          // Initialize parameters
          $final_string_one = array();
          $final_string_one = $parameters;
          $exQuery = "";
          $final_query = "";
          $final_query = $query;

          // Apply a user supplied function to every member of an array
          // Wrap all array elements in single quotes
          array_walk($final_string_one, function(&$str)
          {
            $str = "'" . $str . "'";
          });

          // Set the value with the parameter values to display in the string
          $exQuery = strtr($final_query, $final_string_one);

          // Displays the query string being used
          error_log('Query String: ' . $exQuery);
          */

          // Fetch a single value
          $row = $sql->fetch(PDO::FETCH_ASSOC);

          // Get value and store into variable
          $returnValue = trim($row[$attributeName]);

          // The following call to closeCursor() may be required by some drivers
          // Closes the cursor, enabling the statement to be executed again
          $sql->closeCursor();

          // Check if the database server is still connected
          if ($this->pdo)
          {
            // Close connection
            $this->pdo = null;
            // $pdo = null;
          }
        }
        else
        {
          // Else error has occurred
          $connectionServerMesg = reset($connectionStatus);

          // Set message
          $returnValue = trim('SError~' . $connectionServerMesg);

          // Display message
          // error_log($returnValue);
        }
      }
      catch (PDOException $e)
      {
        // Catch the pdo error from the try section of code
        // Set message
        $returnValue = trim('SError~Caught PDO retrieve single value Execution Failure - ' . $e->getMessage());

        // Error log PDO failure
        // error_log($returnValue);

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }
      catch (Exception $e)
      {
        // Catch the error from the try section of code
        // Set message
        $returnValue = trim('SError~Caught retrieve single value Execution Failure - ' . $e->getMessage());

        // Error log failure
        // error_log($returnValue);

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }

      // Return single value
      return $returnValue;
    }

    // Retrieve single attributes with attribute name and query
    private function retrieveSingleAttribute($attributeName, $query, $parameters, $SetNulls = "0", $SetWarnings = "0", $dbName = '<Database_Name>')
    {
      // Initialize array
      $returnArray = array();

      // Function to catch exception errors
      set_error_handler(function ($errno, $errstr, $errfile, $errline)
      {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
      });

      // Try to execute the following code
      try
      {
        // Initialize array for storing values into proper sections of the array
        $RCount = 0;
        $connectionStatus = array();

        // Connect to database
        $connectionStatus = $this->openConnection($dbName);

        // Check if error with registering process
        if (!isset($connectionStatus['SError']) && !array_key_exists('SError', $connectionStatus))
        {
          // Check if set nulls is not empty
          if($SetNulls !== '0')
          {
            // Set ANSI_NULLS ON
            $commandOne = $this->pdo->prepare('SET ANSI_NULLS ON');
            $commandOne->execute();
          }

          // Check if set warnings is not empty
          if($SetWarnings !== '0')
          {
            // Set ANSI_WARNINGS ON
            $commandTwo = $this->pdo->prepare('SET ANSI_WARNINGS ON');
            $commandTwo->execute();
          }

          // Prepare query
          $sql = $this->pdo->prepare($query);

          // Execute the query with key pair values
          $sql->execute($parameters);

          // Un-comment the following block of code to see what the query string is sending to the database
          /*
          // Initialize parameters
          $final_string_one = array();
          $final_string_one = $parameters;
          $exQuery = "";
          $final_query = "";
          $final_query = $query;

          // Apply a user supplied function to every member of an array
          // Wrap all array elements in single quotes
          array_walk($final_string_one, function(&$str)
          {
            $str = "'" . $str . "'";
          });

          // Set the value with the parameter values to display in the string
          $exQuery = strtr($final_query, $final_string_one);

          // Displays the query string being used
          error_log('Query String: ' . $exQuery);
          */

          // While rows exist
          while($row = $sql->fetch(PDO::FETCH_ASSOC))
          {
            // Store key pair values into an array
            $returnArray[$RCount] = trim($row[$attributeName]);

            // Increment position
            $RCount++;
          }

          // The following call to closeCursor() may be required by some drivers
          // Closes the cursor, enabling the statement to be executed again
          $sql->closeCursor();

          // Check if the database server is still connected
          if ($this->pdo)
          {
            // Close connection
            $this->pdo = null;
            // $pdo = null;
          }
        }
        else
        {
          // Else error has occurred
          $connectionServerMesg = reset($connectionStatus);

          // Set message
          $returnArray = array('SError' => trim($connectionServerMesg));

          // Display message
          // error_log(print_r($returnArray, true));
        }
      }
      catch (PDOException $e)
      {
        // Catch the pdo error from the try section of code
        // Set message
        $returnArray = array('SError' => trim('Caught PDO retrieve single attribute Execution Failure - ' . $e->getMessage()));

        // Error log PDO failure
        // error_log(print_r($returnArray, true));

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }
      catch (Exception $e)
      {
        // Catch the error from the try section of code
        // Set message
        $returnArray = array('SError' => trim('Caught retrieve single attribute Execution Failure - ' . $e->getMessage()));

        // Error log failure
        // error_log(print_r($returnArray, true));

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }

      // Return array
      return $returnArray;
    }

    // Retrieve multiple attributes with title array and query
    private function retrieveMultipleColumnRecordAttribute($titleArray, $query, $parameters, $SetNulls = "0", $SetWarnings = "0", $dbName = '<Database_Name>')
    {
      // Initialize array
      $returnArray = array();

      // Function to catch exception errors
      set_error_handler(function ($errno, $errstr, $errfile, $errline)
      {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
      });

      // Try to execute the following code
      try
      {
        // Initialize variable and array
        // Convert MB to a whole number and then minus 55% of that from the overall to get a limit
        $memoryLimit = ini_get('memory_limit');

        // Remove anything that is not a number and or a period
        $memoryLimit = preg_replace('/[^0-9\.]/', '', $memoryLimit);
        $overallMemoryUsage = ($memoryLimit * 1024 * 1024) - floor(($memoryLimit * 1024 * 1024 * .55));

        $currentMemoryUsage = 0;
        $connectionStatus = array();

        // Connect to database
        $connectionStatus = $this->openConnection($dbName);

        // Check if error with registering process
        if (!isset($connectionStatus['SError']) && !array_key_exists('SError', $connectionStatus))
        {
          // Check if set nulls is not empty
          if($SetNulls !== '0')
          {
            // Set ANSI_NULLS ON
            //$this->pdo->exec("SET ANSI_NULLS ON");
            $commandOne = $this->pdo->prepare('SET ANSI_NULLS ON');
            $commandOne->execute();
          }

          // Check if set warnings is not empty
          if($SetWarnings !== '0')
          {
            // Set ANSI_WARNINGS ON
            //$this->pdo->exec("SET ANSI_WARNINGS ON");
            $commandTwo = $this->pdo->prepare('SET ANSI_WARNINGS ON');
            $commandTwo->execute();
          }

          // Prepare query
          $sql = $this->pdo->prepare($query);

          // Execute the query with key pair values
          $sql->execute($parameters);

          // Un-comment the following block of code to see what the query string is sending to the database
          /*
          // Initialize parameters
          $final_string_one = array();
          $final_string_one = $parameters;
          $exQuery = "";
          $final_query = "";
          $final_query = $query;

          // Apply a user supplied function to every member of an array
          // Wrap all array elements in single quotes
          array_walk($final_string_one, function(&$str)
          {
            $str = "'" . $str . "'";
          });

          // Set the value with the parameter values to display in the string
          $exQuery = strtr($final_query, $final_string_one);

          // Displays the query string being used
          error_log('Query String: ' . $exQuery);
          */

          // While rows exist
          while($row = $sql->fetch(PDO::FETCH_ASSOC))
          {
            // Loop through all values in the args
            foreach($titleArray as $vals)
            {
              // Store key pair values into an array
              $returnArray[$vals] = trim($row[$vals]);
            }

            // Get current memory usage
            $currentMemoryUsage = memory_get_usage(TRUE);

            // Check if current memory usage is below overall memory usage
            if($currentMemoryUsage >= $overallMemoryUsage)
            {
              // State message in first position
              $returnArray = array('SError' => trim('Retrieve multiple column record attribute, limit reached or exceeded.'));

              // Error log the limit reached or exceeded
              // error_log(print_r($returnArray, true));

              // Exit while loop
              break;
            }
          }

          // The following call to closeCursor() may be required by some drivers
          // Closes the cursor, enabling the statement to be executed again
          $sql->closeCursor();

          // Check if the database server is still connected
          if ($this->pdo)
          {
            // Close connection
            $this->pdo = null;
            // $pdo = null;
          }
        }
        else
        {
          // Else error has occurred
          $connectionServerMesg = reset($connectionStatus);

          // Set message
          $returnArray = array('SError' => trim($connectionServerMesg));

          // Display message
          // error_log(print_r($returnArray, true));
        }
      }
      catch (PDOException $e)
      {
        // Catch the pdo error from the try section of code
        // Set message
        $returnArray = array('SError' => trim('Caught PDO retrieve multiple column record attribute Execution Failure - ' . $e->getMessage()));

        // Error log PDO failure
        // error_log(print_r($returnArray, true));

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }
      catch(Exception $e)
      {
        // Catch the error from the try section of code
        // Set message
        $returnArray = array('SError' => trim('Caught retrieve multiple column record attribute Execution Failure - ' . $e->getMessage()));

        // Error log failure
        // error_log(print_r($returnArray, true));

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }

      // Return array
      return $returnArray;
    }

    // Retrieve multiple attributes with title array and query
    private function retrieveMultipleAttribute($titleArray, $query, $parameters, $SetNulls = "0", $SetWarnings = "0", $dbName = '<Database_Name>')
    {
      // Initialize array
      $returnArray = array();

      // Function to catch exception errors
      set_error_handler(function ($errno, $errstr, $errfile, $errline)
      {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
      });

      // Try to execute the following code
      try
      {
        // Initialize variable and array
        // Convert MB to a whole number and then minus 55% of that from the overall to get a limit
        $memoryLimit = ini_get('memory_limit');

        // Remove anything that is not a number and or a period
        $memoryLimit = preg_replace('/[^0-9\.]/', '', $memoryLimit);
        $overallMemoryUsage = ($memoryLimit * 1024 * 1024) - floor(($memoryLimit * 1024 * 1024 * .55));

        $currentMemoryUsage = 0;
        $connectionStatus = array();

        // Connect to database
        $connectionStatus = $this->openConnection($dbName);

        // Check if database connection has been opened
        // Check if error with registering process
        if (!isset($connectionStatus['SError']) && !array_key_exists('SError', $connectionStatus))
        {
          // Check if set nulls is not empty
          if($SetNulls !== '0')
          {
            // Set ANSI_NULLS ON
            //$this->pdo->exec("SET ANSI_NULLS ON");
            $commandOne = $this->pdo->prepare('SET ANSI_NULLS ON');
            $commandOne->execute();
          }

          // Check if set warnings is not empty
          if($SetWarnings !== '0')
          {
            // Set ANSI_WARNINGS ON
            //$this->pdo->exec("SET ANSI_WARNINGS ON");
            $commandTwo = $this->pdo->prepare('SET ANSI_WARNINGS ON');
            $commandTwo->execute();
          }

          // Prepare query
          $sql = $this->pdo->prepare($query);

          // Execute the query with key pair values
          $sql->execute($parameters);

          // Un-comment the following block of code to see what the query string is sending to the database
          /*
          // Initialize parameters
          $final_string_one = array();
          $final_string_one = $parameters;
          $exQuery = "";
          $final_query = "";
          $final_query = $query;

          // Apply a user supplied function to every member of an array
          // Wrap all array elements in single quotes
          array_walk($final_string_one, function(&$str)
          {
            $str = "'" . $str . "'";
          });

          // Set the value with the parameter values to display in the string
          $exQuery = strtr($final_query, $final_string_one);

          // Displays the query string being used
          error_log('Query String: ' . $exQuery);
          */

          // While rows exist
          while($row = $sql->fetch(PDO::FETCH_ASSOC))
          {
            // Loop through all values in the args
            foreach($titleArray as $vals)
            {
              // Store key pair values into an array
              $rowArray[$vals] = trim($row[$vals]);
            }

            // Append to final array
            array_push($returnArray, $rowArray);

            // Get current memory usage
            $currentMemoryUsage = memory_get_usage(TRUE);

            // Check if current memory usage is below overall memory usage
            if($currentMemoryUsage >= $overallMemoryUsage)
            {
              // State message in first position
              $returnArray = array('SError' => trim('Retrieve multiple attribute, limit reached or exceeded.'));

              // Error log the limit reached or exceeded
              // error_log(print_r($returnArray, true));

              // Exit while loop
              break;
            }
          }

          // The following call to closeCursor() may be required by some drivers
          // Closes the cursor, enabling the statement to be executed again
          $sql->closeCursor();

          // Check if the database server is still connected
          if ($this->pdo)
          {
            // Close connection
            $this->pdo = null;
            // $pdo = null;
          }
        }
        else
        {
          // Else error has occurred
          $connectionServerMesg = reset($connectionStatus);

          // Set message
          $returnArray = array('SError' => trim($connectionServerMesg));

          // Display message
          // error_log(print_r($returnArray, true));
        }
      }
      catch (PDOException $e)
      {
        // Catch the pdo error from the try section of code
        // Set message
        $returnArray = array('SError' => trim('Caught PDO retrieve multiple attribute Execution Failure - ' . $e->getMessage()));

        // Error log PDO failure
        // error_log(print_r($returnArray, true));

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }
      catch(Exception $e)
      {
        // Catch the error from the try section of code
        // Set message
        $returnArray = array('SError' => trim('Caught retrieve multiple attribute Execution Failure - ' . $e->getMessage()));

        // Error log failure
        // error_log(print_r($returnArray, true));

        // error log the caught exception
        // error_log($e->getMessage());
        error_log($e);
      }

      // Return array
      return $returnArray;
    }
  }
?>