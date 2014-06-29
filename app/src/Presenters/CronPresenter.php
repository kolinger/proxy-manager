<?php

namespace App\Presenters;

use App\Model\CertificatesService;
use App\Model\DomainsService;
use App\Model\MiscService;
use App\SSH;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;


/**
 * @author Tomáš Kolinger <tomas@kolinger.me>
 */
class CronPresenter extends Presenter
{

	/** @var MiscService @inject */
	public $miscService;
	/** @var DomainsService @inject */
	public $domainsService;
	/** @var CertificatesService @inject */
	public $certificatesService;


	public function checkRequirements($element)
	{
		if (!$this->context->parameters['consoleMode']) {
			throw new BadRequestException(NULL, 405);
		}
	}


	public function actionDefault()
	{
		$tasks = $this->certificatesService->collectTasks();
		$tasks = array_merge($tasks, $this->domainsService->collectTasks());

		if (count($tasks)) {
			foreach ($this->context->parameters['servers'] as $server) {
				$ssh = new SSH($server['host'], $server['port']);
				$ssh->setUsername($server['user']);
				$ssh->setPassword($server['password']);
				foreach ($tasks as $task) {
					$task($ssh);
				}
				$ssh->execute('service nginx reload');
				$ssh->close();
			}
		}

		$this->miscService->setLastUpdate();
		$this->terminate();
	}
}
