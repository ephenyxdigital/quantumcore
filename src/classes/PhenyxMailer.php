<?php

namespace EphenyxDigital\QuantumCore;

use Language;


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class PhenyxMailer {

    protected $context;

    public $mailer;

    public $sender = [];

    public $to = [];

    public $cc = [];

    public $subject;

    public $htmlContent;

    public $attachment = null;

    public $meta_description = null;

    public $postfields = [];

    public $tpl_folder;

    public $_smarty;

    public function __construct($tplName = null) {

        $this->context = Context::getContext();

        $this->_smarty = $this->context->smarty;

        if (!isset($this->context->phenyxConfig)) {
            $this->context->phenyxConfig = Configuration::getInstance();

        }

        if (!isset($this->context->company)) {
            $this->context->company = Company::initialize();

        }

        if (!isset($this->context->language)) {
            $this->context->language = Tools::jsonDecode(Tools::jsonEncode(Language::buildObject($this->context->phenyxConfig->get('EPH_LANG_DEFAULT'))));
        }

        if (!isset($this->context->translations)) {

            $this->context->translations = new Translate($this->context->language->iso_code, $this->context->company);
        }

        if (!is_null($tplName)) {
            $this->mailer = $this->createTemplate($tplName);
        }

    }

    public function createTemplate($tplName) {

        $extraTplPaths = $this->context->_hook->exec('actionCreateMailTemplate', ['tplName' => $tplName], null, true);

        if (is_array($extraTplPaths)) {

            foreach ($extraTplPaths as $plugin => $template) {

                if (!is_null($template) && file_exists($template)) {
                    $tplName = $template;
                }

            }

        }

        $path_parts = pathinfo($tplName);
        $tpl = '';

        if (!is_null($this->tpl_folder) && file_exists($this->context->theme->path . $this->tpl_folder . '/pdf/' . $path_parts['filename'] . '.tpl')) {
            $tpl = $this->context->theme->path . $this->tpl_folder . '/pdf/' . $path_parts['filename'] . '.tpl';

        } elseif (file_exists($this->context->theme->path . 'pdf/' . $path_parts['filename'] . '.tpl')) {

            $tpl = $this->context->theme->path . 'pdf/' . $path_parts['filename'] . '.tpl';

        } else {

            $tpl = $tplName;

        }

        if (file_exists($tpl)) {
            return $this->_smarty->createTemplate($tpl, $this->_smarty);
        }

        // Fix #3: original returned null implicitly when the template file was not
        // found. Calling fetch() on null in send() caused a fatal error.
        // Now returns false so the caller can guard against it.
        PhenyxLogger::addLog('PhenyxMailer: template not found: ' . $tpl, 3);
        return false;
    }

    public function generatePostfield() {

        $this->postfields = [
            'sender'      => $this->sender,
            'to'          => $this->to,
            'cc'          => $this->cc,
            'subject'     => $this->subject,
            "htmlContent" => $this->htmlContent,
            'attachment'  => $this->attachment,
        ];
    }

    public function send() {

        $mail_allowed = $this->context->phenyxConfig->get('EPH_ALLOW_SEND_EMAIL') ? 1 : 0;

        if (!$mail_allowed) {
            return true;
        }

        // Fix #3 (guard): createTemplate() can return false if the template is missing.
        if (!$this->mailer) {
            PhenyxLogger::addLog('PhenyxMailer::send() called with no valid mailer template.', 3);
            return false;
        }

        $this->htmlContent = $this->mailer->fetch();
        $tpl = $this->context->smarty->createTemplate(_EPH_MAIL_DIR_ . 'header.tpl');

        // Fix #4: original built $bckImg as 'https://' . $url where $url already
        // contained 'https://', producing 'https://https://domain.com/...'.
        // $url is now a plain domain; the protocol is added only once where needed.
        $domain = $this->context->company->domain_ssl;
        $bckImg = !empty($this->context->phenyxConfig->get('EPH_BCK_LOGO_MAIL'))
            ? 'https://' . $domain . '/content/img/' . $this->context->phenyxConfig->get('EPH_BCK_LOGO_MAIL')
            : false;

        $tpl->assign([
            'title'          => $this->subject,
            'show_head_logo' => $this->context->phenyxConfig->get('EPH_SHOW_HEADER_LOGO_MAIL') ? 1 : 0,
            'css_dir'        => 'https://' . $domain . $this->context->theme->css_theme,
            'shop_link'      => $this->context->_link->getBaseFrontLink(),
            'shop_name'      => $this->context->company->company_name,
            'bckImg'         => $bckImg,
            'logoMailLink'   => 'https://' . $domain . '/content/img/' . $this->context->phenyxConfig->get('EPH_LOGO_MAIL'),
        ]);

        if (!is_null($this->meta_description)) {
            $tpl->assign([
                'meta_description' => $this->meta_description,
            ]);
        }

        $header = $tpl->fetch();
        $tpl    = $this->context->smarty->createTemplate(_EPH_MAIL_DIR_ . 'footer.tpl');
        $tpl->assign([
            'tag' => $this->context->phenyxConfig->get('EPH_FOOTER_EMAIL'),
        ]);
        $footer = $tpl->fetch();
        $this->htmlContent = $header . $this->htmlContent . $footer;

        $mail_method = $this->context->phenyxConfig->get('EPH_MAIL_METHOD');

        if ($mail_method == 1) {
            $encrypt = $this->context->phenyxConfig->get('EPH_MAIL_SMTP_ENCRYPTION');
            $mail    = new PHPMailer(true);

            // Fix #8 (encodage) : PHPMailer utilise par défaut iso-8859-1.
            // Le contenu généré par Smarty est en UTF-8, ce qui provoque le
            // mojibake côté client mail (ex. « reçu » → « reÃ§u »).
            // On force donc le charset à UTF-8 et un encodage de transfert
            // sûr pour les caractères 8 bits.
            $mail->CharSet  = PHPMailer::CHARSET_UTF8;       // 'UTF-8'
            $mail->Encoding = PHPMailer::ENCODING_BASE64;    // ou ENCODING_QUOTED_PRINTABLE

            // Fix #5: IsSMTP() is the deprecated CamelCase alias — use isSMTP().
            $mail->isSMTP();
            $mail->SMTPAuth = true;
            $mail->Host     = $this->context->phenyxConfig->get('EPH_MAIL_SERVER');
            $mail->Port     = $this->context->phenyxConfig->get('EPH_MAIL_SMTP_PORT');
            $mail->Username = $this->context->phenyxConfig->get('EPH_MAIL_USER');
            $mail->Password = $this->context->phenyxConfig->get('EPH_MAIL_PASSWD');
            // Fix #9 (data not accepted) : la majorité des serveurs SMTP
            // (OVH, Gmail, O365, IONOS, Brevo…) rejettent au DATA si le
            // From: du header diffère du compte SMTP authentifié, ou si
            // l'envelope-sender (MAIL FROM SMTP / Return-Path) n'est pas
            // sur le même domaine. On force donc :
            //   - le Sender (= MAIL FROM SMTP) à l'utilisateur authentifié
            //   - un Reply-To explicite vers la vraie adresse de l'expéditeur
            //   - le From: garde le nom voulu, mais sur l'adresse authentifiée
            //     pour rester aligné DMARC/SPF.
            $authUser = $this->context->phenyxConfig->get('EPH_MAIL_USER');
            $mail->Sender = $authUser; // Return-Path / envelope sender
            $mail->setFrom($authUser, $this->sender['name']);
            $mail->addReplyTo($this->sender['email'], $this->sender['name']);

            foreach ($this->to as $key => $value) {
                $mail->addAddress($value['email'], $value['name']);
            }

            $mail->Subject = $this->subject;

            if ($encrypt !== 'off') {

                if ($encrypt === 'ENCRYPTION_STARTTLS') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } else {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                }

            } else {
                // Fix #6: when encryption is 'off', PHPMailer still tries to upgrade
                // the connection via TLS by default. Explicitly disable auto-TLS.
                $mail->SMTPAutoTLS = false;
            }

            $mail->Body    = $this->htmlContent;
            $mail->AltBody = trim(strip_tags($this->htmlContent)); // évite "no plaintext part" sur certains anti-spam
            $mail->isHTML(true);

            if (isset($this->attachment) && !is_null($this->attachment)) {
                $mail->addAttachment($this->attachment);
            }

            // Fix #10 : capturer la réponse SMTP brute en cas d'erreur.
            // On active le debug uniquement si EPH_MAIL_SMTP_DEBUG est vrai
            // dans la conf, sinon on reste silencieux en prod.
            $debugBuffer = '';
            if ($this->context->phenyxConfig->get('EPH_MAIL_SMTP_DEBUG')) {
                $mail->SMTPDebug   = SMTP::DEBUG_LOWLEVEL; // 3 = client + server, sans le payload
                $mail->Debugoutput = function ($str, $level) use (&$debugBuffer) {
                    $debugBuffer .= '[' . $level . '] ' . $str . "\n";
                };
            }

            try {
                $mail->send();
            } catch (PHPMailerException $e) {
                $serverReply = '';
                if ($mail->getSMTPInstance()) {
                    $serverReply = $mail->getSMTPInstance()->getLastReply();
                }
                PhenyxLogger::addLog(
                    'PhenyxMailer SMTP failure: ' . $e->getMessage()
                    . ' | last server reply: ' . $serverReply
                    . ($debugBuffer ? ' | debug: ' . $debugBuffer : ''),
                    4, null, null, null, true, null
                );
                return false;
            }

            return true;

        } elseif ($mail_method == 2) {

            $this->generatePostfield();
            $api_key = $this->context->phenyxConfig->get('EPH_SENDINBLUE_API');

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL            => 'https://api.brevo.com/v3/smtp/email',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($this->postfields),
                // Fix #7: SSL verification was not explicitly set — added for security.
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER     => [
                    'accept: application/json',
                    'api-key: ' . $api_key,
                    'content-type: application/json',
                ],
            ]);

            $response = curl_exec($curl);
            $err      = curl_error($curl);

            // Fix #1 (PHP 8.5): curl_close() is deprecated since PHP 8.5 and has
            // been a no-op since PHP 8.0. CurlHandle objects are freed automatically.
            // curl_close($curl) — removed.

            if ($err) {
                PhenyxLogger::addLog('PhenyxMailer Brevo cURL error: ' . $err, 4);
                return false;
            }

            return true;

        }

        // Fix #2: original had no return when $mail_method was neither 1 nor 2,
        // producing a null return. Now returns false with a log entry.
        PhenyxLogger::addLog('PhenyxMailer::send() — unknown mail method: ' . $mail_method, 3);
        return false;
    }

}
