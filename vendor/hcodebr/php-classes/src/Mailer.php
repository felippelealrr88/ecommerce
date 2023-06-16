<?php

namespace Hcode;

use Hcode\Model\User;
use PHPMailer\PHPMailer\PHPMailer;
use Rain\Tpl;



class Mailer{

    const USERNAME = "76aaf85b681dc1";
    const PASSWORD = "bc753427d608e8";
    const NAME_FROM = "Hcode Store";
    private $mail;
    

    public function __construct($toAddress, $toName, $subject, $tplName, $data = array() ){
        
        //configurações do Rain TPL
		$config = array(
		    "tpl_dir"       => $_SERVER["DOCUMENT_ROOT"]."/views/email", //variável de ambiente do diretório root . pasta
		    "cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/views-cache/",
		    "debug"         => false
		);

		//passa as configs para o TPL
		Tpl::configure( $config ); 

		$tpl = new Tpl();

        //Passa as variáveis para o template
        foreach ($data as $key => $value) {
            $tpl->assign($key, $value);
        }

        //true joga dentro da variável
        $html = $tpl->draw($tplName, true);

        //Create a new PHPMailer instance
        $this->mail = new PHPMailer();

        //Tell PHPMailer to use SMTP
        $this->mail->isSMTP();

        //Enable SMTP debugging
        //SMTP::DEBUG_OFF = off (for production use)
        //SMTP::DEBUG_CLIENT = client messages
        //SMTP::DEBUG_SERVER = client and server messages
        $this->mail->SMTPDebug = 0;

        //Set the hostname of the mail server
        $this->mail->Host = 'sandbox.smtp.mailtrap.io';
        //Use `$this->mail->Host = gethostbyname('smtp.gmail.com');`
        //if your network does not support SMTP over IPv6,
        //though this may cause issues with TLS
        

        //Set the SMTP port number:
        // - 465 for SMTP with implicit TLS, a.k.a. RFC8314 SMTPS or
        // - 587 for SMTP+STARTTLS
        $this->mail->Port = 2525;

        //Set the encryption mechanism to use:
        // - SMTPS (implicit TLS on port 465) or
        // - STARTTLS (explicit TLS on port 587)
        $this->mail->SMTPSecure = "tls";

        //Whether to use SMTP authentication
        $this->mail->SMTPAuth = true;

        //Username to use for SMTP authentication - use full email address for gmail
        $this->mail->Username = Mailer::USERNAME;

        //Password to use for SMTP authentication
        $this->mail->Password = Mailer::PASSWORD;

        //Set who the message is to be sent from
        //Note that with gmail you can only use your account address (same as `Username`)
        //or predefined aliases that you have configured within your account.
        //Do not use user-submitted addresses in here
        $this->mail->setFrom(Mailer::USERNAME, Mailer::NAME_FROM);

        //Set an alternative reply-to address
        //This is a good place to put user-submitted addresses
        //$this->mail->addReplyTo('replyto@example.com', 'First Last');

        //Set who the message is to be sent to
        $this->mail->addAddress($toAddress, $toName);

        //Set the subject line
        $this->mail->Subject = $subject;

        //Read an HTML message body from an external file, convert referenced images to embedded,
        //convert HTML into a basic plain-text alternative body

        $this->mail->msgHTML($html);//Conteúdo html que será enviado

        //Replace the plain text body with one created manually
        $this->mail->AltBody = 'This is a plain-text message body';

        //Attach an image file
        //$this->mail->addAttachment('images/phpmailer_mini.png');

        //send the message, check for errors

    }

    public function send(){

        return $this->mail->send();
    }



}

?>