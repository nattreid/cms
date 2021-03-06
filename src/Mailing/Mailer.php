<?php

declare(strict_types=1);

namespace NAttreid\Cms\Mailing;

use NAttreid\Mailing\BaseMailer;

/**
 * Mailer
 *
 * Attreid <attreid@gmail.com>
 */
class Mailer extends BaseMailer
{

	/**
	 * Odeslani linku pro zmenu hesla
	 * @param string $email
	 * @param string $hash
	 * @throws \Nette\Application\UI\InvalidLinkException
	 */
	public function sendRestorePassword(string $email, string $hash): void
	{
		$mail = $this->createMail('restorePassword');

		$mail->link = $this->link('Cms:Sign:restorePassword', [
			'hash' => $hash
		]);

		$mail->setSubject('cms.mailing.restorePassword.subject')
			->addTo($email);

		$mail->send();
	}

	/**
	 * Posle email novemu uzivateli s loginem a heslem
	 * @param string $email
	 * @param string $username
	 * @param string $password
	 * @throws \Nette\Application\UI\InvalidLinkException
	 */
	public function sendNewUser(string $email, string $username, string $password): void
	{
		$mail = $this->createMail('newUser');

		$mail->link = $this->link('Cms:Sign:in');
		$mail->username = $username;
		$mail->password = $password;

		$mail->setSubject('cms.mailing.newUser.subject')
			->addTo($email);

		$mail->send();
	}

	/**
	 * Zaslani noveho hesla
	 * @param string $email
	 * @param string $username
	 * @param string $password
	 * @throws \Nette\Application\UI\InvalidLinkException
	 */
	public function sendNewPassword(string $email, string $username, string $password): void
	{
		$mail = $this->createMail('newPassword');

		$mail->link = $this->link('Cms:Sign:in');
		$mail->username = $username;
		$mail->password = $password;

		$mail->setSubject('cms.mailing.newPassword.subject')
			->addTo($email);

		$mail->send();
	}

}
