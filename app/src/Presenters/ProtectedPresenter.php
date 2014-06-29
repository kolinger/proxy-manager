<?php

namespace App\Presenters;

use App\Model\MiscService;


/**
 * @author Tomáš Kolinger <tomas@kolinger.me>
 */
abstract class ProtectedPresenter extends BasePresenter
{

	/** @var MiscService @inject */
	public $miscService;


	public function checkRequirements($element)
	{
		if (!$this->user->loggedIn) {
			$this->redirect('Login:');
		}
	}


	public function beforeRender()
	{
		$this->template->lastUpdate = $this->miscService->getLastUpdateDate();
	}


	public function handleLogout()
	{
		$this->user->logout(TRUE);
		$this->redirect('Login:');
	}
}
