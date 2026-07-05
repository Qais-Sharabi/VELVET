<?php

define('SMTP_HOST','smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_SECURE','ssl');
define('SMTP_AUTH',true);

define('SMTP_USER', 's12323616@stu.najah.edu');
define('SMTP_PASS','loijddeuogayggnm');
define('SMTP_FROM','s12323616@stu.najah.edu');
define('SMTP_FROM_NAME','VELVET Shop');

function sendEmail($to_email, $to_name, $subject, $html_body) {
    $boundary = '----=_VELVET_' . md5(uniqid('', true));

    $headers  = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $headers .= "To: $to_name <$to_email>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";

    $body  = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode(strip_tags($html_body))) . "\r\n";
    $body .= "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($html_body)) . "\r\n--$boundary--\r\n";

    $context = stream_context_create([
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]
    ]);

    $socket = @stream_socket_client("ssl://" . SMTP_HOST . ":" . SMTP_PORT, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) return "Connection Failed: $errstr";

    function get_smtp_res($socket) {
        $res = "";
        while($str = fgets($socket, 515)) {
            $res .= $str;
            if(substr($str, 3, 1) == " ") break;
        }
        return $res;
    }

    get_smtp_res($socket);
    fwrite($socket, "EHLO localhost\r\n");
    get_smtp_res($socket);
    fwrite($socket, "AUTH LOGIN\r\n");
    get_smtp_res($socket);
    fwrite($socket, base64_encode(SMTP_USER) . "\r\n");
    get_smtp_res($socket);
    fwrite($socket, base64_encode(SMTP_PASS) . "\r\n");
    $auth = get_smtp_res($socket);

    if (substr($auth, 0, 3) !== '235') { fclose($socket); return "Auth Failed: $auth"; }

    fwrite($socket, "MAIL FROM:<" . SMTP_FROM . ">\r\n");
    get_smtp_res($socket);
    fwrite($socket, "RCPT TO:<$to_email>\r\n");
    get_smtp_res($socket);
    fwrite($socket, "DATA\r\n");
    get_smtp_res($socket);
    fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
    $final = get_smtp_res($socket);
    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return (substr($final, 0, 3) === '250') ? true : $final;
}

function sendResetCode($to_email, $to_name, $code) {
    $year = date('Y');
    $html = "
    <div style='font-family: sans-serif; max-width: 500px; margin: auto; border: 1px solid #eee; padding: 20px;'>
        <h2 style='text-align: center; color: #111; letter-spacing: 3px;'>VELVET</h2>
        <p>Hi $to_name,</p>
        <p>Your password reset code is:</p>
        <div style='background: #f4f4f4; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 10px;'>$code</div>
        <p style='color: #888; font-size: 12px; margin-top: 20px;'>This code will expire in 15 minutes.</p>
    </div>";
    return sendEmail($to_email, $to_name, "Your Reset Code", $html);
}
