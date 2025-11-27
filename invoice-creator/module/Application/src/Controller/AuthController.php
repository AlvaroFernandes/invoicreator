<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use RuntimeException;

/**
 * Simple controller that uses AuthService from the service manager.
 */

class AuthController extends AbstractActionController
{
    public function loginAction(): ViewModel
    {
        $sm = $this->getEvent()->getApplication()->getServiceManager();
        $auth = $sm->has(\Application\Service\AuthService::class)
            ? $sm->get(\Application\Service\AuthService::class)
            : $sm->get('Application\\AuthService');

        $vm = new ViewModel();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost();
            $email = (string)($data['email'] ?? '');
            $password = (string)($data['password'] ?? '');

            if ($auth->authenticate($email, $password)) {
                return $this->redirect()->toRoute('home');
            }

            $vm->setVariable('error', 'Invalid credentials');
        }

        return $vm;
    }

    public function registerAction(): ViewModel
    {
        $sm = $this->getEvent()->getApplication()->getServiceManager();
        $auth = $sm->has(\Application\Service\AuthService::class)
            ? $sm->get(\Application\Service\AuthService::class)
            : $sm->get('Application\\AuthService');

        $vm = new ViewModel();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost();
            $name = trim((string)($data['name'] ?? ''));
            $email = trim((string)($data['email'] ?? ''));
            $password = (string)($data['password'] ?? '');

            try {
                $auth->register($name, $email, $password);
                return $this->redirect()->toRoute('login');
            } catch (RuntimeException $e) {
                $vm->setVariable('error', $e->getMessage());
            }
        }

        return $vm;
    }

    public function logoutAction()
    {
        $sm = $this->getEvent()->getApplication()->getServiceManager();
        $auth = $sm->has(\Application\Service\AuthService::class)
            ? $sm->get(\Application\Service\AuthService::class)
            : $sm->get('Application\\AuthService');

        $auth->clearIdentity();

        return $this->redirect()->toRoute('login');
    }
}
