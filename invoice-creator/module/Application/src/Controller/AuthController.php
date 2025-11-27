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
                // set flash success and redirect
                $flash = new \Laminas\Session\Container('flash');
                $messages = $flash->offsetExists('messages') ? $flash->messages : [];
                $messages[] = ['type' => 'success', 'text' => 'Signed in successfully'];
                $flash->messages = $messages;

                return $this->redirect()->toRoute('dashboard');
            }

            // push error to flash so layout shows a toast
            $flash = new \Laminas\Session\Container('flash');
            $messages = $flash->offsetExists('messages') ? $flash->messages : [];
            $messages[] = ['type' => 'danger', 'text' => 'Invalid credentials'];
            $flash->messages = $messages;
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
                $flash = new \Laminas\Session\Container('flash');
                $messages = $flash->offsetExists('messages') ? $flash->messages : [];
                $messages[] = ['type' => 'success', 'text' => 'Account created. Please sign in.'];
                $flash->messages = $messages;

                return $this->redirect()->toRoute('login');
            } catch (RuntimeException $e) {
                $flash = new \Laminas\Session\Container('flash');
                $messages = $flash->offsetExists('messages') ? $flash->messages : [];
                $messages[] = ['type' => 'danger', 'text' => $e->getMessage()];
                $flash->messages = $messages;
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

        $flash = new \Laminas\Session\Container('flash');
        $messages = $flash->offsetExists('messages') ? $flash->messages : [];
        $messages[] = ['type' => 'success', 'text' => 'Signed out'];
        $flash->messages = $messages;

        return $this->redirect()->toRoute('login');
    }
}
