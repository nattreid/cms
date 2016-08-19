<?php

namespace NAttreid\Crm\Mailing;

/**
 * Mailer
 *
 * Attreid <attreid@gmail.com>
 */
class Mailer extends \NAttreid\Mailing\BaseMailer {

    /**
     * Odeslani linku pro zmenu hesla
     * @param string $email
     * @param string $hash
     */
    public function sendRestorePassword($email, $hash) {
        $mail = $this->createMail('restorePassword');

        $mail->link = $this->link('Crm:Sign:restorePassword', [
            'hash' => $hash
        ]);

        $mail->setSubject($this->translate('mailing.restorePassword.subject'))
                ->addTo($email);

        $mail->send();
    }

    /**
     * Posle email novemu uzivateli s loginem a heslem
     * @param string $username
     * @param string $password
     */
    public function sendNewUser($email, $username, $password) {
        $mail = $this->createMail('newUser');

        $mail->link = $this->link('Crm:Sign:in');
        $mail->username = $username;
        $mail->password = $password;

        $mail->setSubject($this->translate('mailing.newUser.subject'))
                ->addTo($email);

        $mail->send();
    }

    /**
     * Zaslani noveho hesla
     * @param string $email
     * @param string $username
     * @param string $password
     */
    public function sendNewPassword($email, $username, $password) {
        $mail = $this->createMail('newPassword');

        $mail->link = $this->link('Crm:Sign:in');
        $mail->username = $username;
        $mail->password = $password;

        $mail->setSubject($this->translate('mailing.newPassword.subject'))
                ->addTo($email);

        $mail->send();
    }

}
