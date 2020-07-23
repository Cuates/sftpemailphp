<?php
  /*
          File: checkerrorclass.php
       Created: 07/21/2020
       Updated: 07/21/2020
    Programmer: Cuates
    Updated By: Cuates
       Purpose: Check Error class for all types of PHP fatal errors
  */

  // Create the class
  class checkerrorclass
  {
    // PHP 5+ Style constructor
    public function __construct()
    {
      // This function needs to be here so the class can be executed when called
    }

    // PHP 4 Style constructor
    public function checkerrorclass()
    {
      // Call the constructor
      self::__construct();
    }

    // Email end user of fatal errors
    function shutdown_notify($email_to, $email_subject, $from_mail, $from_name, $replyto, $email_cc = "", $email_bcc = "")
    {
      // Get the last occurred error
      $error = error_get_last();

      // Check if the error is not empty and is in one of the predefined constants
      if(!empty($error) && in_array($error['type'], array(E_ERROR, E_USER_ERROR)))
      {
        // Set parameters to variables
        $to = $email_to;
        $subject = $email_subject;

        // Set the email headers
        $headers = "From: " . $from_name . " <" . $from_mail . ">\r\n";

        // Add CC header
        if (trim($email_cc) !== "")
        {
          // Append the CC header
          $headers .= "CC: " . $email_cc . "\r\n";
        }

        // Add BCC header
        if (trim($email_bcc) !== "")
        {
          // Append the BCC header
          $headers .= "BCC: " . $email_bcc . "\r\n";
        }

        // Set reply-to e-mail
        $headers .= "Reply-To: " . $replyto . "\r\n";

        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $message = "Error Message<br />"
        . $error['message']
        . "<br /><br />Error File<br />"
        . $error['file']
        . "<br /><br />Error Line<br />"
        . $error['line']
        . "<br /><br />Host Name<br />";

        // Display variable value if set else display nothing
        $hostName = gethostname();
        $message .= $hostName !== false ? $hostName : '';

        // Send email to software engineers for unsent email
        mail($to, $subject, $message, $headers);
      }
    }

    // Email end user of fatal errors
    function caught_error_notify($e, $email_to, $email_subject, $from_mail, $from_name, $replyto, $email_cc = "", $email_bcc = "")
    {
      // Set parameters to variables
      $to = $email_to;
      $subject = $email_subject;

      // Set the email headers
      $headers = "From: " . $from_name . " <" . $from_mail . ">\r\n";

      // Add CC header
      if (trim($email_cc) !== "")
      {
        // Append the CC header
        $headers .= "CC: " . $email_cc . "\r\n";
      }

      // Add BCC header
      if (trim($email_bcc) !== "")
      {
        // Append the BCC header
        $headers .= "BCC: " . $email_bcc . "\r\n";
      }

      // Set reply-to e-mail
      $headers .= "Reply-To: " . $replyto . "\r\n";

      $headers .= "MIME-Version: 1.0\r\n";
      $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

      // Set the email body message
      $message = "Error Message
      <br  />
      {$e->getMessage()}
      <br  />
      <br  />
      Error File
      <br  />
      {$e->getFile()}
      <br  />
      <br  />
      Error Line
      <br  />
      {$e->getLine()}
      <br /><br />Host Name<br />";

      // Display variable value if set else display nothing
      $hostName = gethostname();
      $message .= $hostName !== false ? $hostName : '';

      // Send email to developer about unsent email
      mail($to, $subject, $message, $headers);
    }
  }
?>