<?php
declare(strict_types=1);
namespace Exam;
final class Mailer {
    public static function send(string $to,string $subject,string $body): void {
        $driver=Config::get('MAIL_DRIVER','log');
        if ($driver==='log' && Config::get('APP_ENV','local')!=='production') {
            $path=ROOT.'/storage/mail/'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(6)).'.txt';
            if (file_put_contents($path,"To: $to\nSubject: $subject\n\n$body",LOCK_EX)===false) throw new \RuntimeException('Cannot write local mail.');
            chmod($path,0600); return;
        }
        $from=Config::get('MAIL_FROM');
        if ($driver!=='mail' || !filter_var($from,FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('Configure MAIL_DRIVER=mail and a valid MAIL_FROM.');
        if (!mail($to,$subject,$body,['From'=>$from,'Content-Type'=>'text/plain; charset=UTF-8'])) throw new \RuntimeException('Mail transport unavailable.');
    }
}
