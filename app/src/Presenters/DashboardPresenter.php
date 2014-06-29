<?php

namespace App\Presenters;

use App\Model\CertificatesService;
use App\Model\DomainsService;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;

/**
 * @author Tomáš Kolinger <tomas@kolinger.me>
 */
class DashboardPresenter extends ProtectedPresenter
{

	/** @var DomainsService @inject */
	public $domainsService;
	/** @var CertificatesService @inject */
	public $certificatesService;


	public function renderDefault()
	{
		$this->template->domains = $this->domainsService->findAll($this->user->id);
	}


	/**
	 * @return Form
	 */
	protected function createComponentForm()
	{
		$form = new Form;

		$form->addText('domain')
			->setRequired('Domain must be filled');

		$form->addText('target')
			->setRequired('Target must be filled');

		$items = $this->certificatesService->findAllInPairs($this->user->id);
		$form->addSelect('cert', NULL, $items)
			->setPrompt('- no SSL certificate selected -');

		$form->addCheckbox('wildcard', 'Include sub-domains wildcard (*.)');

		$id = $this->getParameter('id');
		if (!$id) {
			$form->addSubmit('send', 'Add');
		} else {
			$form->addSubmit('send', 'Save');
		}
		$form->addHidden('id', $id);

		$form->onSuccess[] = function (Form $form) {
			$values = $form->values;
			$id = $values->id;
			if (!$id) {
				$this->flashMessage('Domain successfully added', 'success');
				$this->domainsService->add($this->user->id, $values->domain, $values->target, $values->cert,
					$values->wildcard);
			} else {
				$this->flashMessage('Domain successfully saved', 'success');
				$this->domainsService->save($id, $values->domain, $values->target, $values->cert,
					$values->wildcard);
			}
			$this->redirect('default');
		};
		return $form;
	}


	public function handleEdit($id)
	{
		$domain = $this->domainsService->findOneById($id, $this->user->id);
		if (!$domain) {
			throw new BadRequestException();
		}

		$this['form']->setDefaults(array(
			'domain' => $domain->domain,
			'target' => $domain->target,
			'cert' => $domain->certificate_id,
			'wildcard' => $domain->wildcard,
		));
	}


	public function handleRemove($id)
	{
		$domain = $this->domainsService->findOneById($id, $this->user->id);
		if (!$domain) {
			throw new BadRequestException();
		}
		$this->domainsService->remove($id);

		$this->flashMessage('Domain successfully removed', 'success');
		$this->redirect('this');
	}
}
