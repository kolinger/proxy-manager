<?php

namespace App\Presenters;

use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;


/**
 * @author Tomáš Kolinger <tomas@kolinger.me>
 */
class LoginPresenter extends BasePresenter
{

	public function checkRequirements($element)
	{
		if ($this->user->loggedIn) {
			$this->redirect('Dashboard:');
		}
	}


	/**
	 * @return Form
	 */
	protected function createComponentForm()
	{
		$form = new Form;

		$form->addText('login', 'Login')
			->setRequired('Login must be filled');

		$form->addPassword('password', 'Password')
			->setRequired('Password must be filled');

		$form->addSubmit('send', 'Sign in');

		$form->onSuccess[] = function (Form $form) {
			$values = $form->getValues();
			try {
				$this->user->login($values->login, $values->password);
				$this->redirect('Dashboard:');
			} catch (AuthenticationException $e) {
				$form->addError($e->getMessage());
			}
		};

		return $form;
	}
}
