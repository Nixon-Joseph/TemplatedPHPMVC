<?php
use Pelago\Emogrifier\CssInliner;

abstract class EmailHelperBase {
    protected static function _BuildEmail(string $emailContent, array $siteData, ?array $emailData = null): string {
        foreach ($siteData as $key => $value) {
            $emailContent = str_replace("[$key]", $value, $emailContent);
        }
        if (isset($emailData) && count($emailData) > 0) {
            foreach ($emailData as $key => $value) {
                $emailContent = str_replace('{{' . $key . '}}', $value, $emailContent);
            }
        }
        return $emailContent;
    }

    protected static function _SendEmail(string $emailContent, string $subject, string $to, string $from, bool $isHtml, ?string $replyTo = null, ?string $cc = null, ?string $bcc = null, ?callable $onException = null): bool {
        $success = false;
        try {
            $body = $emailContent;
        
            $headers = array();
            $headers[] = "From: $from";
            if ($isHtml) {
                $body = CssInliner::fromHtml($emailContent)->inlineCss()->render();
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
            $success = mail($to, $subject, $body, implode("\r\n", $headers));
        } catch (\Throwable $e) {
            if (isset($onException)) {
                $onException($e);
            }
        }
        return $success;
    }
}
?>