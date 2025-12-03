<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class JobsController extends AbstractActionController
{
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

    public function indexAction()
    {
        $authOrResponse = $this->ensureAuthenticated();
        if ($authOrResponse instanceof \Laminas\Http\Response) {
            return $authOrResponse;
        }

        $sm = $this->getEvent()->getApplication()->getServiceManager();
        $conn = $sm->get('doctrine.connection');

        $jobs = $conn->fetchAllAssociative(
            'SELECT j.*, c.company_name FROM jobs j LEFT JOIN clients c ON j.client_id = c.id ORDER BY j.created_at DESC'
        );

        return new ViewModel(['jobs' => $jobs]);
    }

    public function createAction()
    {
        $authOrResponse = $this->ensureAuthenticated();
        if ($authOrResponse instanceof \Laminas\Http\Response) {
            return $authOrResponse;
        }

        $sm = $this->getEvent()->getApplication()->getServiceManager();
        $conn = $sm->get('doctrine.connection');
        $request = $this->getRequest();

        // Fetch clients for selection
        $clients = $conn->fetchAllAssociative('SELECT id, company_name FROM clients ORDER BY company_name');

        if ($request instanceof \Laminas\Http\Request && $request->isPost()) {
            $post = $request->getPost()->toArray();
            $validation = $this->validateJobData($post);
            $row = $validation['row'];
            $errors = $validation['errors'];

            if (!empty($errors)) {
                return new ViewModel(['data' => $row, 'errors' => $errors, 'clients' => $clients]);
            }

            $conn->insert('jobs', $row);
            $flash = new \Laminas\Session\Container('flash');
            $messages = $flash->offsetExists('messages') ? $flash->messages : [];
            $messages[] = ['type' => 'success', 'text' => 'Job created'];
            $flash->messages = $messages;

            return $this->redirect()->toRoute('jobs');
        }

        return new ViewModel(['clients' => $clients]);
    }

    public function editAction()
    {
        $authOrResponse = $this->ensureAuthenticated();
        if ($authOrResponse instanceof \Laminas\Http\Response) {
            return $authOrResponse;
        }

        $id = (int)$this->params()->fromRoute('id', 0);
        $sm = $this->getEvent()->getApplication()->getServiceManager();
        $conn = $sm->get('doctrine.connection');
        $request = $this->getRequest();

        $job = $conn->fetchAssociative('SELECT * FROM jobs WHERE id = ?', [$id]);
        if (! $job) {
            return $this->redirect()->toRoute('jobs');
        }

        $clients = $conn->fetchAllAssociative('SELECT id, company_name FROM clients ORDER BY company_name');

        if ($request instanceof \Laminas\Http\Request && $request->isPost()) {
            $post = $request->getPost()->toArray();
            $validation = $this->validateJobData($post);
            $row = $validation['row'];
            $errors = $validation['errors'];

            if (!empty($errors)) {
                return new ViewModel(['data' => $row, 'errors' => $errors, 'clients' => $clients, 'id' => $id]);
            }

            $conn->update('jobs', $row, ['id' => $id]);
            $flash = new \Laminas\Session\Container('flash');
            $messages = $flash->offsetExists('messages') ? $flash->messages : [];
            $messages[] = ['type' => 'success', 'text' => 'Job updated'];
            $flash->messages = $messages;

            return $this->redirect()->toRoute('jobs');
        }

        return new ViewModel(['data' => $job, 'clients' => $clients, 'id' => $id]);
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

        $job = $conn->fetchAssociative('SELECT * FROM jobs WHERE id = ?', [$id]);
        if (! $job) {
            return $this->redirect()->toRoute('jobs');
        }

        $request = $this->getRequest();
        if ($request instanceof \Laminas\Http\Request && $request->isPost()) {
            $conn->delete('jobs', ['id' => $id]);
            $flash = new \Laminas\Session\Container('flash');
            $messages = $flash->offsetExists('messages') ? $flash->messages : [];
            $messages[] = ['type' => 'success', 'text' => 'Job deleted'];
            $flash->messages = $messages;
            return $this->redirect()->toRoute('jobs');
        }

        return new ViewModel(['job' => $job]);
    }

    private function validateJobData(array $input): array
    {
        $row = [
            'client_id' => (int)($input['client_id'] ?? 0),
            'title' => trim((string)($input['title'] ?? '')),
            'description' => trim((string)($input['description'] ?? '')),
            'location' => trim((string)($input['location'] ?? '')),
            'rate' => ($input['rate'] !== '' && isset($input['rate'])) ? (float)$input['rate'] : null,
            'rate_type' => in_array($input['rate_type'] ?? 'hourly', ['hourly','daily'], true) ? $input['rate_type'] : 'hourly',
            'target_type' => in_array($input['target_type'] ?? 'client', ['client','client_client'], true) ? $input['target_type'] : 'client',
            'target_name' => trim((string)($input['target_name'] ?? '')),
            'start_time' => isset($input['start_time']) && $input['start_time'] !== '' ? trim((string)$input['start_time']) : null,
            'end_time' => isset($input['end_time']) && $input['end_time'] !== '' ? trim((string)$input['end_time']) : null,
            'had_30min_break' => (!empty($input['had_30min_break']) && $input['had_30min_break'] !== '0') ? 1 : 0,
        ];

        $errors = [];
        if ($row['client_id'] <= 0) {
            $errors['client_id'] = 'Please select a client';
        }
        if ($row['title'] === '') {
            $errors['title'] = 'Job title is required';
        }
        if ($row['target_type'] === 'client_client' && $row['target_name'] === '') {
            $errors['target_name'] = 'Please provide the client\'s client name';
        }

        // rate validation: optional, but if provided must be numeric and non-negative
        if ($row['rate'] !== null && (!is_numeric($row['rate']) || $row['rate'] < 0)) {
            $errors['rate'] = 'Please provide a valid non-negative rate';
        }

        // location max length check
        if ($row['location'] !== '' && strlen($row['location']) > 255) {
            $errors['location'] = 'Location is too long';
        }

        // time format validation: accept HH:MM or HH:MM:SS
        $timeRegex = '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/';
        if ($row['start_time'] !== null && !preg_match($timeRegex, $row['start_time'])) {
            $errors['start_time'] = 'Start time must be in HH:MM or HH:MM:SS format';
        }
        if ($row['end_time'] !== null && !preg_match($timeRegex, $row['end_time'])) {
            $errors['end_time'] = 'End time must be in HH:MM or HH:MM:SS format';
        }

        // if both times provided, ensure end is after start
        if ($row['start_time'] !== null && $row['end_time'] !== null) {
            $startTs = strtotime($row['start_time']);
            $endTs = strtotime($row['end_time']);
            if ($startTs === false || $endTs === false || $endTs <= $startTs) {
                $errors['time_range'] = 'End time must be later than start time';
            }
        }

        return ['row' => $row, 'errors' => $errors];
    }
}
