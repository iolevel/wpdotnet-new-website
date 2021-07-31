<?php

$customeremail = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    include_once "../vendor/autoload.php";

    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
    $event = null;

    try {

        \Stripe\Stripe::setApiKey(getenv('STRIPE_APIKEY'));

        $event = \Stripe\Event::constructFrom(
            //json_decode($payload, true)
            json_decode($payload, true), $sig_header, getenv('STRIPE_ENDPOINT_SECRET')
        );
    }
    catch (\UnexpectedValueException $e) {
        http_response_code(400);
        exit($e->getMessage());
    }
    catch(\Stripe\Exception\SignatureVerificationException $e) {
        http_response_code(400);
        exit($e->getMessage());
    }

    switch ($event->type) {
        case 'customer.subscription.created':
            /**
             * @var \Stripe\Subscription $subscription
             */
            $subscription = $event->data->object;
            $customeremail = $subscription->customer->email;
            break;
        default:
            http_response_code(500);
            exit("Unknown event type $event->type");
    }
}
else {
 //   exit;
    $customeremail = $_GET['test'];
}

try {

    if (!$customeremail) {
        throw new InvalidArgumentException("Unexpected! Missing customer's email.");
    }

    $email = new SendGrid\Mail\Mail();
    $email->setSubject("Welcome to WpDotNet!");
    $email->setFrom('info@iolevel.com', "iolevel");
    $email->setReplyTo($customeremail);
    $email->addTo('wpdotnet@iolevel.com', "iolevel");
    $email->addContent("text/html", file_get_contents(__DIR__ . '/welcome-email.html'));

    // account credentials
    $username = getenv("SENDGRID_UNAME");
    $password = getenv("SENDGRID_APIKEY");

    // SendGrid object
    $sendgrid = new SendGrid($password);

    // send message
    $response = $sendgrid->send($email);

    http_response_code(200);
}
catch (\Exception $e) {
    // Set a 500 (internal server error) response code.
    http_response_code(500);
    echo $e->getMessage();
}