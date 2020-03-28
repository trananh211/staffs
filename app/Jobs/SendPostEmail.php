<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

//use \Swift_SmtpTransport as SmtpTransport;
use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_MailTransport;
use Swift_Message;

class SendPostEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $info;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($info)
    {
        $this->info = $info;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
//        $email_from = $this->info->email;
//        $password = $this->info->password;
//        $host = $this->info->host;
//        $port = $this->info->port;
//        $security = $this->info->security;
//        $title = $this->info->title;
//        $body = $this->info->body;
//        $file = $this->info->file;
//        $email_to = $this->info->email_to;
//
////        echo "Gui email tu: ".$email_from." den :".$email_to." - Host: ".$host." - port: ".$port." - Security: ".$security. ' - title : '.$title.' - file : '.$file.' - het'."\n";
//        sendEmail($email_from, $password, $host, $port, $security, $email_to, $title, $body, $file);
    }
}
