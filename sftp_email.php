#!/usr/bin/php
<?php
  /*
          File: sftp_email.php
       Created: 07/22/2020
       Updated: 07/22/2020
    Programmer: Cuates
    Updated By: Cuates
       Purpose: Create comma delimited CSV file for SFTP and email end user with generated file
  */

  // Include error check class
  include ("checkerrorclass.php");

  // Create an object of error check class
  $checkerrorcl = new checkerrorclass();

  // Set variables
  $developerNotify = 'cuates@email.com'; // Production email(s)
  // $developerNotify = 'cuates@email.com'; // Development email(s)
  $endUserEmailNotify = 'cuates@email.com'; // Production email(s)
  // $endUserEmailNotify = 'cuates@email.com'; // Development email(s)
  $externalEndUserEmailNotify = ''; // Production email(s)
  // $externalEndUserEmailNotify = 'cuates@email.com'; // Development email(s)
  $scriptName = 'SFTP Email'; // Production
  // $scriptName = 'TEST SFTP Email TEST'; // Development
  $fromEmailServer = 'Email Server';
  $fromEmailNotifier = 'email@email.com';

  // Retrieve any other issues not retrieved by the set_error_handler try/catch
  // Parameters are function name, $email_to, $email_subject, $from_mail, $from_name, $replyto, $email_cc and $email_bcc
  register_shutdown_function(array($checkerrorcl,'shutdown_notify'), $developerNotify, $scriptName . ' Error', $fromEmailNotifier, $fromEmailServer, $fromEmailNotifier);

  // Function to catch exception errors
  set_error_handler(function ($errno, $errstr, $errfile, $errline)
  {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
  });

  // Attempt to generate email
  try
  {
    // Declare download directory
    define ('DOWNLOADDIR', '/var/www/html/Doc_Directory/');
    define ('TEMPDOC', '/var/www/html/Temp_Directory/');

    // Include database class file
    include ("sftp_email_class.php");

    // Create an object of database class
    $sftp_email_cl = new sftp_email_class();

    // Initialize variables
    $jobName = "Job Name";
    $filename = "";
    $filenamePrefix = 'File_Name_';
    $dataValue = array();
    $headerDataInformation = array();
    $headerValue = array();
    $finalHeaderDataValues = array();
    $errorPrefixFilename = "sftp_email_issue_"; // Production
    // $errorPrefixFilename = "sftp_email_dev_issue_"; // Development
    $errormessagearray = array();
    $idNum = 0;

    // Call function to insert and or update any new data into the database table
    $regDataResult = $sftp_email_cl->registerData($jobName);

    // Explode database message
    $regDataReturn = explode('~', $regDataResult);

    // Set response message
    $regDataResp = reset($regDataReturn);
    $regDataMesg = next($regDataReturn);

    // Check if error with registering process
    if (trim($regDataResp) !== "Success")
    {
      // Append error message
      array_push($errormessagearray, array('Register', $jobName, '', '', '', '', '', '', '', 'Error', $regDataMesg));
    }

    // Function call to retrieve data values
    $dataValue = $sftp_email_cl->getData($jobName);

    // Check if server error
    if (!isset($dataValue['SError']) && !array_key_exists('SError', $dataValue))
    {
      // Proceed only if there is data to process
      if (count($dataValue) > 0)
      {
        // Copy array for later modifications
        $modifiedDataArray = $dataValue;

        // process each array
        foreach($modifiedDataArray as $keyArray => $subArray)
        {
          // Check if sub array is an array
          if (is_array($subArray))
          {
            // Set key to remove from array
            $keyString = 'column04';

            // Check if key exist within array
            if(array_key_exists($keyString, $subArray))
            {
              // Remove key from array
              unset($modifiedDataArray[$keyArray][$keyString]);
            }
          }
        }

        // Store modified array
        $finalHeaderDataValues = $modifiedDataArray;

        // Initialize column headers
        $colHeaders = array();

        // Build the filename
        $filename = $filenamePrefix . date("YmdyHis") . ".csv";

        // Define attribute names
        $headerValue = array('Column01', 'Column02', 'Column03');

        // Build the array with the information needed for data transfer
        array_unshift($finalHeaderDataValues, $headerValue);

        // Write to file for later processing
        $createFileNameResult = $sftp_email_cl->writeToFile(DOWNLOADDIR, $filename, $finalHeaderDataValues, $colHeaders);

        // Explode database message
        $createFileNameReturn = explode('~', $createFileNameResult);

        // Set response message
        $createFileNameResp = reset($createFileNameReturn);
        $createFileNameMesg = next($createFileNameReturn);

        // Check if error with creating file
        if (trim($createFileNameResp) === "Success")
        {
          // Push files to the remote server
          $fileTransferResult = $sftp_email_cl->putSFTPFile($filename, $jobName, DOWNLOADDIR);

          // Explode database message
          $fileTransferReturn = explode('~', $fileTransferResult);

          // Set response message
          $fileTransferReturnResp = reset($fileTransferReturn);
          $fileTransferReturnMesg = next($fileTransferReturn);

          // Check if there was a file transferred to the server
          if (trim($fileTransferReturnResp) === "Success")
          {
            // Loop through all values in the array for values in each record
            foreach($dataValue as $valueRec)
            {
              // Initialize parameters
              $column01 = reset($valueRec);
              $column02 = next($valueRec);
              $column03 = next($valueRec);
              $column04 = next($valueRec);

              // Retrieve id
              $idretnumber = $sftp_email_cl->extractIDData($column04);

              // Check if server error
              if (!isset($idretnumber['SError']) && !array_key_exists('SError', $idretnumber))
              {
                // Initialize parameters
                $idvalue = reset($idretnumber);

                // Validate data
                $validateDataResult = $sftp_email_cl->validateData($idvalue, $column04);

                // Explode database message
                $validateDataReturn = explode('~', $validateDataResult);

                // Set response message
                $validateDataResp = reset($validateDataReturn);
                $validateDataMesg = next($validateDataReturn);

                // Check if error with registering process
                if (trim($validateDataResp) !== "Success")
                {
                  // Append error message
                  array_push($errormessagearray, array('Validate Data', $jobName, $column01, $column02, $column03, $column04, $idvalue, $filename, '', 'Error', $validateDataMesg));
                }
              }
              else
              {
                // Set response and message
                $idretnumberMesg = reset($idretnumber);

                // Append error message
                array_push($errormessagearray, array('Retrieve ID', $jobName, $column01, $column02, $column03, $column04, '', $filename, '', 'Error', $idretnumberMesg));
              }
            }

            // Send an email to end user for notification of file transmission
            $toEndUser = "";
            $toEndUser = $externalEndUserEmailNotify;
            $to_ccEndUser = "";
            $to_bccEndUser = "";
            $to_bccEndUser = $endUserEmailNotify;

            $fromEmailEndUser = $fromEmailNotifier;
            $fromNameEndUser = $fromEmailServer;
            $replyToEndUser = $fromEmailNotifier;

            // Set the subject line
            $subjectEndUser = $scriptName . ' file has been Transmitted';

            // Set the email headers
            $headersEndUser = "From: " . $fromNameEndUser . " <" . $fromEmailEndUser . ">" . "\r\n";
            $headersEndUser .= "CC: " . $to_ccEndUser . "\r\n";
            $headersEndUser .= "BCC: " . $to_bccEndUser . "\r\n";
            $headersEndUser .= "MIME-Version: 1.0\r\n";
            $headersEndUser .= "Content-Type: text/html; charset=UTF-8\r\n";
            // $headersEndUser .= "X-Priority: 3\r\n";

            // Mail priority levels
            // "X-Priority" (values: 1, 3, or 5 from highest[1], normal[3], lowest[5])
            // Set priority and importance levels
            $xPriorityEndUser = "";

            // Set the email body message
            $messageEndUser = "<!DOCtype html>
            <html>
              <head>
                <title>"
                  . $scriptName .
                  " file has been Transmitted
                </title>
                <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
                <!-- Include next line to use the latest version of IE -->
                <meta http-equiv=\"X-UA-Compatible\" content=\"IE=Edge\" />
              </head>
              <body>
                <div style=\"text-align: center;\">
                  <h2>"
                    . $scriptName .
                    " file has been Transmitted
                  </h2>
                </div>";

            // Begin error message
            $messageEndUser .= "<div style=\"text-align: center;\">" . $scriptName . " file (" . $filename . ") has been transmitted.
                    <br />
                    <br />
                    Do not reply, your intended recipient will not receive the message.
                  </div>
                </body>
              </html>";

            // Send email to customer
            $sftp_email_cl->notifyEndUser($filename, DOWNLOADDIR, $toEndUser, $fromEmailEndUser, $fromNameEndUser, $replyToEndUser, $subjectEndUser, $headersEndUser, $messageEndUser, $to_ccEndUser, $to_bccEndUser, $xPriorityEndUser);
          }
          else
          {
            // Append error message
            array_push($errormessagearray, array('Put Data Feed File', $jobName, '', '', '', '', '', $filename, '', 'Error', $fileTransferReturnMesg));
          }
        }
        else
        {
          // Append error message
          array_push($errormessagearray, array('Write Data to File', $jobName, '', '', '', '', '', $filename, '', 'Error', $createFileNameMesg));
        }
      }
    }
    else
    {
      // Set message
      $dataValueMesg = reset($dataValue);

      // Append error message
      array_push($errormessagearray, array('Data Extract', $jobName, '', '', '', '', '', '', '', 'Error', $dataValueMesg));
    }

    // Update the sequence in the database
    $sequenceUpdate = $sftp_email_cl->updateSequence($idNum);

    // Explode database message
    $sequenceUpdateData = explode('~', $sequenceUpdate);

    // Set response message
    $sequenceUpdateResp = reset($sequenceUpdateData);
    $sequenceUpdateMesg = next($sequenceUpdateData);

    // Check if error with updating sequence
    if (trim($sequenceUpdateResp) !== "Success")
    {
      // Append error message
      array_push($errormessagearray, array('Update Sequence', $jobName, '', '', '', '', '', '', $idNum, 'Error', $sequenceUpdateMesg));
    }

    // Check if error message array is not empty
    if (count($errormessagearray) > 0)
    {
      // Set prefix file name and headers
      $errorFilename = $errorPrefixFilename . date("Y-m-d_H-i-s") . '.csv';
      $colHeaderArray = array(array('Process', 'Job Name', 'Column 01', 'Column 02', 'Column 03', 'Column 03', 'ID', 'File Name', 'Sequence Number', 'Response', 'Message'));

      // Initialize variable
      $to = "";
      $to = $developerNotify;
      $to_cc = "";
      $to_bcc = "";
      $fromEmail = $fromEmailNotifier;
      $fromName = $fromEmailServer;
      $replyTo = $fromEmailNotifier;
      $subject = $scriptName . " Error";

      // Set the email headers
      $headers = "From: " . $fromName . " <" . $fromEmail . ">" . "\r\n";
      // $headers .= "CC: " . $to_cc . "\r\n";
      // $headers .= "BCC: " . $to_bcc . "\r\n";
      $headers .= "MIME-Version: 1.0\r\n";
      $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
      // $headers .= "X-Priority: 3\r\n";

      // Mail priority levels
      // "X-Priority" (values: 1, 3, or 5 from highest[1], normal[3], lowest[5])
      // Set priority and importance levels
      $xPriority = "";

      // Set the email body message
      $message = "<!DOCtype html>
      <html>
        <head>
          <title>"
            . $scriptName .
            " Error
          </title>
          <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
          <!-- Include next line to use the latest version of IE -->
          <meta http-equiv=\"X-UA-Compatible\" content=\"IE=Edge\" />
        </head>
        <body>
          <div style=\"text-align: center;\">
            <h2>"
              . $scriptName .
              " Error
            </h2>
          </div>
          <div style=\"text-align: center;\">
            There was an issue with " . $scriptName . " Error process.
            <br />
            <br />
            Do not reply, your intended recipient will not receive the message.
          </div>
        </body>
      </html>";

      // Call notify developer function
      $sftp_email_cl->notifyDeveloper(TEMPDOC, $errorFilename, $colHeaderArray, $errormessagearray, $to, $to_cc, $to_bcc, $fromEmail, $fromName, $replyTo, $subject, $headers, $message, $xPriority);
    }
  }
  catch(Exception $e)
  {
    // Call to the function
    $checkerrorcl->caught_error_notify($e, $developerNotify, $scriptName . ' Error', $fromEmailNotifier, $fromEmailServer, $fromEmailNotifier);
  }
?>