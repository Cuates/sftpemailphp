<?php
  /*
          File: mailclass.php
       Created: 07/21/2020
       Updated: 07/21/2020
    Programmer: Cuates
    Updated By: Cuates
       Purpose: Enhanced mail functions
  */

  class mailclass
  {
    // PHP 5+ Style constructor
    public function __construct()
    {
      // This function needs to be here so the class can be executed when called
    }

    // PHP 4 Style constructor
    public function mailclass()
    {
      // Call the constructor
      self::__construct();
    }

    // Mail with attachment
    function mailWithAttachment($filename, $path, $mailto, $from_mail, $from_name, $replyto, $subject, $message, $to_cc= "", $to_bcc= "", $xPriority = "")
    {
      // Combine both path and filename for file variable
      $file = $path . $filename;

      // Get file contents from file and store into variable
      $content = file_get_contents($file);

      // Encode content from file and split into content into a big chunk
      $encoded_content = chunk_split(base64_encode($content));

      // Create an md5 unique id based on time
      $uid = md5(uniqid(time()));

      // Set End of Line EOL
      $eol = PHP_EOL;

      // Initialize variable
      $body = "";

      // Set from name and e-mail address
      $header = "From: " . $from_name . " <" . $from_mail . ">" . $eol;

      // Set reply-to e-mail
      $header .= "Reply-To: " . $replyto . $eol;

      // Check if Carbon Copy is empty string
      if (trim($to_cc) !== "")
      {
        // Set Carbon Copy e-mail
        $header .= "Cc: " . $to_cc . $eol;
      }

      // Check if Blind Carbon Copy is empty string
      if (trim($to_bcc) !== "")
      {
        // Set Blind Carbon Copy e-mail
        $header .= "Bcc: " . $to_bcc . $eol;
      }

      // Indicates the message is MIME-formatted
      $header .= "MIME-Version: 1.0" . $eol;

      // Check if priority is empty string
      if (trim($xPriority) !== "")
      {
        // Check if the X-Priority is equal to any of the following numbers
        if (trim($xPriority) === "1" || trim($xPriority) === "5")
        {
          // Set xpriority e-mail
          $header .= "X-Priority: " . trim($xPriority) . $eol;
        }
      }

      // Define content type as multipart/mixed and boundary with the md5 value.
      // States text plus attachments
      $header .= "Content-Type: multipart/mixed; boundary=\"" . $uid . "\"";

      // Boundary with md5 value
      $body .= "--" . $uid . $eol;

      // E-mail will contain html code and character set of utf-8
      $body .= "Content-type:text/html; charset=utf-8" . $eol;

      // No binary-to-text encoding on top of the original encoding was used.
      // Can use 7bit, 8bit, or binary (Default is 7bit)
      $body .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;

      // Store message of body into header
      $body .= $message . $eol;

      // Boundary with md5 value
      $body .= "--" . $uid . $eol;

      // State this file is binary
      $body .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"" . $eol;

      // Tell e-mail client a binary-to-text encoding scheme was used and that appropriate initial decoding is necessary before the message can be read with its original encoding (e.g. UTF-8)
      $body .= "Content-Transfer-Encoding: base64" . $eol;

      // Suggest a default name if content is saved to a file.
      // If used with Content-Type: application/octet-stream user agent should not display the response but directly enter a 'save response as...' dialog
      $body .= "Content-Disposition: attachment; filename=\"" . $filename . "\"" . $eol . $eol;

      // Store content into header
      $body .= $encoded_content . $eol;

      // Boundary with md5 value
      $body .= "--" . $uid . "--";

      // Send an e-mail with attachment
      mail($mailto, $subject, $body, $header);
    }
  }
?>