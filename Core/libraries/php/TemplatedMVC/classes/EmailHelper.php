<?php namespace devpirates\MVC;
use Pelago\Emogrifier\CssInliner;

class EmailHelper {
    public static function SendEmail(string $email, string $subject, string $to, string $from, bool $isHtml, ?string $replyTo = null, ?string $cc = null, ?string $bcc = null): void {
        $body = $email;
        
        $headers = array();
        $headers[] = "From: $from";
        if ($isHtml) {
            $body = CssInliner::fromHtml($email)->inlineCss()->render();
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=UTF-8';
            $headers[] = "Content-Transfer-Encoding: base64";
        }
        if (isset($replyTo) && strlen($replyTo) > 0) {
            $headers[] = "Reply-To: $replyTo";
        }
        if (isset($cc) && strlen($cc) > 0) {
            $headers[] = "Cc: $cc";
        }
        if (isset($bcc) && strlen($bcc) > 0) {
            $headers[] = "Bcc: $bcc";
        }

        mail($to, $subject, $inlinedEmail, implode("\r\n", $headers));
    }
}
?>