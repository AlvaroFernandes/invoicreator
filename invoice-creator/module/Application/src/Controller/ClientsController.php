<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use RuntimeException;

class ClientsController extends AbstractActionController
{
    /**
     * Ensure the current user is authenticated. Returns the AuthService on success,
     * or a Response (redirect to login) when not authenticated.
     *
     * @return \Application\Service\AuthService|\Laminas\Http\Response
     */
    private function ensureAuthenticated()
    {
        $sm = $this->getEvent()->getApplication()->getServiceManager();
        $auth = $sm->has(\Application\Service\AuthService::class)
            ? $sm->get(\Application\Service\AuthService::class)
            : ($sm->has('Application\\AuthService') ? $sm->get('Application\\AuthService') : null);

        $identity = $auth ? $auth->getIdentity() : null;
        if (empty($identity)) {
            $flash = new \Laminas\Session\Container('flash');
            $messages = $flash->offsetExists('messages') ? $flash->messages : [];
            $messages[] = ['type' => 'warning', 'text' => 'Please sign in to access this page'];
            $flash->messages = $messages;

            return $this->redirect()->toRoute('login');
        }

        return $auth;
    }
    public function indexAction(): ViewModel
    {
        $authOrResponse = $this->ensureAuthenticated();
        if ($authOrResponse instanceof \Laminas\Http\Response) {
            return $authOrResponse;
        }

        $sm = $this->getEvent()->getApplication()->getServiceManager();
        $conn = $sm->get('doctrine.connection');

        $clients = $conn->fetchAllAssociative('SELECT * FROM clients ORDER BY company_name');

        return new ViewModel(['clients' => $clients]);
    }

    public function createAction(): ViewModel
    {
        $authOrResponse = $this->ensureAuthenticated();
        if ($authOrResponse instanceof \Laminas\Http\Response) {
            return $authOrResponse;
        }

        $sm = $this->getEvent()->getApplication()->getServiceManager();
        $conn = $sm->get('doctrine.connection');
        $request = $this->getRequest();

        if ($request->isPost()) {
            $data = $request->getPost();
            $row = [
                'company_name' => trim((string)($data['company_name'] ?? '')),
                'company_contact' => trim((string)($data['company_contact'] ?? '')),
                'abn' => trim((string)($data['abn'] ?? '')),
                'contact_email' => trim((string)($data['contact_email'] ?? '')),
                'contact_phone' => trim((string)($data['contact_phone'] ?? '')),
                'address' => trim((string)($data['address'] ?? '')),
            ];

            if ($row['company_name'] === '') {
                $flash = new \Laminas\Session\Container('flash');
                $messages = $flash->offsetExists('messages') ? $flash->messages : [];
                $messages[] = ['type' => 'danger', 'text' => 'Company Name is required'];
                $flash->messages = $messages;
                return new ViewModel(['data' => $row]);
            }

            $conn->insert('clients', $row);
            $flash = new \Laminas\Session\Container('flash');
            $messages = $flash->offsetExists('messages') ? $flash->messages : [];
            $messages[] = ['type' => 'success', 'text' => 'Client created'];
            $flash->messages = $messages;

            return $this->redirect()->toRoute('clients');
        }

        return new ViewModel();
    }

    public function editAction(): ViewModel
    {
        $authOrResponse = $this->ensureAuthenticated();
        if ($authOrResponse instanceof \Laminas\Http\Response) {
            return $authOrResponse;
        }

        $id = (int)$this->params()->fromRoute('id', 0);
        $sm = $this->getEvent()->getApplication()->getServiceManager();
        $conn = $sm->get('doctrine.connection');
        $request = $this->getRequest();

        $client = $conn->fetchAssociative('SELECT * FROM clients WHERE id = ?', [$id]);
        if (! $client) {
            return $this->redirect()->toRoute('clients');
        }

        if ($request->isPost()) {
            $data = $request->getPost();
            $row = [
                'company_name' => trim((string)($data['company_name'] ?? '')),
                'company_contact' => trim((string)($data['company_contact'] ?? '')),
                'abn' => trim((string)($data['abn'] ?? '')),
                'contact_email' => trim((string)($data['contact_email'] ?? '')),
                'contact_phone' => trim((string)($data['contact_phone'] ?? '')),
                'address' => trim((string)($data['address'] ?? '')),
            ];

            if ($row['company_name'] === '') {
                $flash = new \Laminas\Session\Container('flash');
                $messages = $flash->offsetExists('messages') ? $flash->messages : [];
                $messages[] = ['type' => 'danger', 'text' => 'Company Name is required'];
                $flash->messages = $messages;
                return new ViewModel(['data' => $row, 'id' => $id]);
            }

            $conn->update('clients', $row, ['id' => $id]);
            $flash = new \Laminas\Session\Container('flash');
            $messages = $flash->offsetExists('messages') ? $flash->messages : [];
            $messages[] = ['type' => 'success', 'text' => 'Client updated'];
            $flash->messages = $messages;

            return $this->redirect()->toRoute('clients');
        }

        return new ViewModel(['data' => $client, 'id' => $id]);
    }

    public function deleteAction()
    {
        $authOrResponse = $this->ensureAuthenticated();
        if ($authOrResponse instanceof \Laminas\Http\Response) {
            return $authOrResponse;
        }

        $id = (int)$this->params()->fromRoute('id', 0);
        $sm = $this->getEvent()->getApplication()->getServiceManager();
        $conn = $sm->get('doctrine.connection');

        $client = $conn->fetchAssociative('SELECT * FROM clients WHERE id = ?', [$id]);
        if (! $client) {
            return $this->redirect()->toRoute('clients');
        }

        // If POST, perform deletion
        $request = $this->getRequest();
        if ($request->isPost()) {
            $conn->delete('clients', ['id' => $id]);
            $flash = new \Laminas\Session\Container('flash');
            $messages = $flash->offsetExists('messages') ? $flash->messages : [];
            $messages[] = ['type' => 'success', 'text' => 'Client deleted'];
            $flash->messages = $messages;
            return $this->redirect()->toRoute('clients');
        }

        // Render a simple confirmation view
        return new ViewModel(['client' => $client]);
    }
}
