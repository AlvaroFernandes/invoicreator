<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class DashboardController extends AbstractActionController
{
    public function indexAction(): ViewModel
    {
        $sm = $this->getEvent()->getApplication()->getServiceManager();
        $auth = $sm->has(\Application\Service\AuthService::class)
            ? $sm->get(\Application\Service\AuthService::class)
            : ($sm->has('Application\\AuthService') ? $sm->get('Application\\AuthService') : null);

        $identity = $auth ? $auth->getIdentity() : null;
        if (empty($identity)) {
            return $this->redirect()->toRoute('login');
        }

        return new ViewModel(['identity' => $identity]);
    }
}
