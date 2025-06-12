<?php

use PHPMailer\PHPMailer\PHPMailer;

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

        } else

        if (file_exists($this->context->theme->path . 'pdf/' . $path_parts['filename'] . '.tpl')) {

            $tpl = $this->context->theme->path . 'pdf/' . $path_parts['filename'] . '.tpl';

        } else {

            $tpl = $tplName;

        }

        if (file_exists($tpl)) {
            return $this->_smarty->createTemplate($tpl, $this->_smarty);
        }

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

        if ($mail_allowed) {

            $this->htmlContent = $this->mailer->fetch();
            $tpl = $this->context->smarty->createTemplate(_EPH_MAIL_DIR_ . 'header.tpl');
            $url = 'https://' . $this->context->company->domain_ssl;
            $bckImg = !empty($this->context->phenyxConfig->get('EPH_BCK_LOGO_MAIL')) ? 'https://' . $url . '/content/img/' . $this->context->phenyxConfig->get('EPH_BCK_LOGO_MAIL') : false;
            $tpl->assign([
                'title'          => $this->subject,
                'show_head_logo' => $this->context->phenyxConfig->get('EPH_SHOW_HEADER_LOGO_MAIL') ? 1 : 0,
                'css_dir'        => 'https://' . $this->context->company->domain_ssl . $this->context->theme->css_theme,
                'shop_link'      => $this->context->_link->getBaseFrontLink(),
                'shop_name'      => $this->context->company->company_name,
                'bckImg'         => $bckImg,
                'logoMailLink'   => $url . '/content/img/' . $this->context->phenyxConfig->get('EPH_LOGO_MAIL'),
            ]);

            if (!is_null($this->meta_description)) {
                $tpl->assign([
                    'meta_description' => $this->meta_description,
                ]);
            }

            $header = $tpl->fetch();
            $tpl = $this->context->smarty->createTemplate(_EPH_MAIL_DIR_ . 'footer.tpl');
            $tpl->assign([
                'tag' => $this->context->phenyxConfig->get('EPH_FOOTER_EMAIL'),
            ]);
            $footer = $tpl->fetch();
            $this->htmlContent = $header . $this->htmlContent . $footer;
            $mail_method = $this->context->phenyxConfig->get('EPH_MAIL_METHOD');

            if ($mail_method == 1) {
                $encrypt = $this->context->phenyxConfig->get('EPH_MAIL_SMTP_ENCRYPTION');
                $mail = new PHPMailer();
                $mail->IsSMTP();
                $mail->SMTPAuth = true;
                $mail->Host = $this->context->phenyxConfig->get('EPH_MAIL_SERVER');
                $mail->Port = $this->context->phenyxConfig->get('EPH_MAIL_SMTP_PORT');
                $mail->Username = $this->context->phenyxConfig->get('EPH_MAIL_USER');
                $mail->Password = $this->context->phenyxConfig->get('EPH_MAIL_PASSWD');
                $mail->setFrom($this->sender['email'], $this->sender['name']);

                foreach ($this->to as $key => $value) {
                    $mail->addAddress($value['email'], $value['name']);
                }

                $mail->Subject = $this->subject;

                if ($encrypt != 'off') {

                    if ($encrypt == 'ENCRYPTION_STARTTLS') {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    } else {
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    }

                }

                $mail->Body = $this->htmlContent;
                $mail->isHTML(true);

                if (isset($this->attachment) && !is_null($this->attachment)) {
                    $mail->addAttachment($this->attachment);
                }

                if (!$mail->send()) {
                    return false;
                } else {
                    return true;
                }

            } else

            if ($mail_method == 2) {

                $this->generatePostfield();
                $api_key = $this->context->phenyxConfig->get('EPH_SENDINBLUE_API');

                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL            => "https://api.brevo.com/v3/smtp/email",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING       => "",
                    CURLOPT_MAXREDIRS      => 10,
                    CURLOPT_TIMEOUT        => 30,
                    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST  => "POST",
                    CURLOPT_POSTFIELDS     => json_encode($this->postfields),
                    CURLOPT_HTTPHEADER     => [
                        "accept: application/json",
                        "api-key: " . $api_key,
                        "content-type: application/json",
                    ],
                ]);

                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);

                if ($err) {
                    return false;
                } else {
                    return true;
                }

            }

        } else {
            return true;
        }

    }

}
