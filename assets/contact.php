<?php

// Only process POST reqeusts.
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    include_once "../vendor/autoload.php";

    // Get the form fields and remove whitespace.
    $name = strip_tags(trim($_POST["name"]));
    $name = str_replace(array("\r", "\n"), array(" ", " "), $name);
    $fromemail = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = trim($_POST["message"]);

    // Check that data was sent to the mailer.
    if (empty($name) or empty($message) or !filter_var($fromemail, FILTER_VALIDATE_EMAIL)) {
        // Set a 400 (bad request) response code and exit.
        http_response_code(400);
        echo "Please complete the form and try again.";
        exit;
    }

    $recaptcha = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . getenv('RECAPTCHA_PRIVATE') . '&response=' . $_POST['recaptcha_response']));
    if ($recaptcha->score < 0.5) {
        echo "Error validating the request.";
        exit;
    }

    $email = new SendGrid\Mail\Mail();

    // set subject
    $email->setSubject("WpDotNet Contact from $name");

    // attach the body of the email
    $email->setFrom('info@iolevel.com', "iolevel");
    //$email->setFrom($fromemail, $name);
    $email->setReplyTo($fromemail, $name);
    $email->addTo('wpdotnet@iolevel.com', "iolevel");
    //$email->addCc('jakub@iolevel.com', "Jakub Misek");
    //$email->addCc('ben@iolevel.com', "Benjamin Fistein");
    $email->addContent("text/plain", "$message
    
---
From: $name <$fromemail>
    ");

    // account credentials
    $username = getenv("SENDGRID_UNAME");
    $password = getenv("SENDGRID_APIKEY");

    // SendGrid object
    $sendgrid = new SendGrid($password);

    try
    {
        // send message
        $response = $sendgrid->send($email);

        http_response_code(200);
        echo "Thank You! Your message has been sent.";
    }
    catch (\Exception $ex)
    {
        // Set a 500 (internal server error) response code.
        http_response_code(500);
        echo "Oops! Something went wrong and we couldn't send your message.";
    }
}
else {
    // Not a POST request, set a 403 (forbidden) response code.
    http_response_code(403);
    echo "There was a problem with your submission, please try again.";
}
