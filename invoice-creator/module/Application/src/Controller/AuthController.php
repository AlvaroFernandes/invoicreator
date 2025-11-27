<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class AuthController extends AbstractActionController
{
    public function loginAction(): ViewModel
    {
        return new ViewModel([]);
    }

    public function registerAction(): ViewModel
    {
        return new ViewModel([]);
    }

    public function logoutAction()
    {
        // If you have an authentication service, clear identity here.
        // For now simply redirect to home.
        return $this->redirect()->toRoute('home');
    }
}
