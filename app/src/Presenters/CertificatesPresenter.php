<?php

namespace App\Presenters;

use App\Model\CertificatesService;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;

/**
 * @author Tomáš Kolinger <tomas@kolinger.me>
 */
class CertificatesPresenter extends ProtectedPresenter
{

	/** @var CertificatesService @inject */
	public $certificatesService;


	public function renderDefault()
	{
		$this->template->certificates = $this->certificatesService->findAll($this->user->id);
	}


	/**
	 * @return Form
	 */
	protected function createComponentForm()
	{
		$form = new Form;

		$form->addText('name')
			->setRequired('Name must be filled');

		$form->addUpload('key', 'Key certificate')
			->setRequired('Key certificate must be selected');

		$form->addUpload('crt', 'Public certificate or chained PEM (public combined with CA)')
			->setRequired('Public certificate must be selected');

		$form->addUpload('ca', 'CA certificate (not required if is PEM provided)');

		$form->addSubmit('send', 'Add');

		$form->onSuccess[] = function (Form $form) {
			$values = $form->values;
			$this->certificatesService->save($this->user->id, $values->name, $values->key, $values->crt, $values->ca);
		};
		return $form;
	}


	public function handleRemove($id)
	{
		$domain = $this->certificatesService->findOneById($id, $this->user->id);
		if (!$domain) {
			throw new BadRequestException();
		}
		$this->certificatesService->remove($id);

		$this->flashMessage('Certificate successfully removed', 'success');
		$this->redirect('this');
	}
}
