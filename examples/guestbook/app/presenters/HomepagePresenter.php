<?php

use Nette\Application\UI\Form;

/**
 * Homepage presenter - základní presenter aplikace
 * @author Lukáš Ambrož
 */
class HomepagePresenter extends BasePresenter
{
    
    /**
     * Úložiště zpráv
     * @var MsgStorage
     */
    private $messages = NULL;
    
    /**
     * Zaregistruje filtr
     * @param Nette\Templating\Template $template Šablona
     */
    public function templatePrepareFilters($template)
    {
        $template->registerFilter(new XmlTmpl\Compiler());
    }
    
    /**
     * Vytvoří formulář pro přidání zprávy
     */
    protected function createComponentMsgForm()
    {
        $form = new Form($this, 'msgForm');
        
        $form->addText('from', 'Od:')
                ->setRequired('Zadejte prosím vaše jméno');
        $form->addText('subject', 'Předmět:')
                ->setRequired('Zadejte prosím předmět zprávy');
        $form->addText('email', 'E-mail:')
                ->addRule(Form::EMAIL, 'Zadejte prosím platný e-mail');
        $form->addTextArea('text', 'Text:')
                ->setRequired('Zadejte prosím text zprávy');
        $form->addSubmit('send', 'Odeslat');
        
        $form->onSuccess[] = callback($this, 'msgFormSubmitted');
    }
    
    /**
     * Provede inicializaci modelu
     */
    protected function startup()
    {
        parent::startup();
        
        if ($this->messages === NULL)
            $this->messages = new MsgStorage('messages.xml');
    }
    
    /**
     * Nastavení proměnných v šabloně, které jsou společné pro více views
     */
    public function beforeRender()
    {
        $this->template->title = 'Kniha návštěv';
    }
    
    /**
     * Vykreslení zpráv
     */
	public function renderDefault()
	{
		$this->template->messages = $this->messages->readMessages();
	}
    
    /**
     * Zpracuje úspěšně odeslaný formulář
     * @param \Nette\Application\UI\Form $form Formulář
     */
    public function msgFormSubmitted(Form $form)
    {
        $values = $form->getValues();
        
        if (!$this->messages->saveMessage($values['from'], $values['email'], $values['subject'], $values['text']))
            $this->flashMessage('Zprávu se nepodařilo uložit');
        else
            $this->flashMessage('Zpráva byla úspěšně uložena');
        
        $this->redirect('Homepage:');
    }
    
}
