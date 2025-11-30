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

        // Format ABN and phone for display and detect phone type
        $clients = array_map(function ($c) {
            $c['abn'] = $this->formatAbnForDisplay($c['abn'] ?? '');
            $c['contact_phone'] = $this->formatPhoneForDisplay($c['contact_phone'] ?? '');
            $c['contact_phone_type'] = $this->detectPhoneType($c['contact_phone'] ?? '');
            return $c;
        }, $clients);

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
            $post = $request->getPost()->toArray();
            $validation = $this->validateClientData($post);
            $row = $validation['row'];
            $errors = $validation['errors'];

            if (!empty($errors)) {
                // Show formatted ABN in the form and render errors
                $row['abn'] = $this->formatAbnForDisplay($row['abn'] ?? '');
                return new ViewModel(['data' => $row, 'errors' => $errors]);
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
            $post = $request->getPost()->toArray();
            $validation = $this->validateClientData($post);
            $row = $validation['row'];
            $errors = $validation['errors'];

            if (!empty($errors)) {
                $row['abn'] = $this->formatAbnForDisplay($row['abn'] ?? '');
                return new ViewModel(['data' => $row, 'id' => $id, 'errors' => $errors]);
            }

            $conn->update('clients', $row, ['id' => $id]);
            $flash = new \Laminas\Session\Container('flash');
            $messages = $flash->offsetExists('messages') ? $flash->messages : [];
            $messages[] = ['type' => 'success', 'text' => 'Client updated'];
            $flash->messages = $messages;

            return $this->redirect()->toRoute('clients');
        }

        // Format ABN for display in the edit form
        $client['abn'] = $this->formatAbnForDisplay($client['abn'] ?? '');

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

    /**
     * Normalize ABN for storage: remove non-digit characters.
     */
    private function normalizeAbn(string $abn): string
    {
        return preg_replace('/\D+/', '', trim($abn));
    }

    /**
     * Format an ABN for display using the common Australian grouping: 2-3-3-3
     * If the input does not contain 11 digits, returns the original trimmed value.
     */
    private function formatAbnForDisplay(string $abn): string
    {
        $digits = preg_replace('/\D+/', '', $abn);
        if (strlen($digits) !== 11) {
            return trim($abn);
        }

        return sprintf('%s %s %s %s', substr($digits, 0, 2), substr($digits, 2, 3), substr($digits, 5, 3), substr($digits, 8, 3));
    }

    /**
     * Validate client input. Returns an array with 'row' (normalized values)
     * and 'errors' (associative array field=>message).
     */
    private function validateClientData(array $input): array
    {
        $row = [
            'company_name' => trim((string)($input['company_name'] ?? '')),
            'company_contact' => trim((string)($input['company_contact'] ?? '')),
            'abn' => $this->normalizeAbn((string)($input['abn'] ?? '')),
            'contact_email' => trim((string)($input['contact_email'] ?? '')),
            'contact_phone' => trim((string)($input['contact_phone'] ?? '')),
            'address' => trim((string)($input['address'] ?? '')),
        ];

        $errors = [];

        // company_name required
        if ($row['company_name'] === '') {
            $errors['company_name'] = 'Company Name is required';
        }

        // email, if present, must be valid
        if ($row['contact_email'] !== '' && !filter_var($row['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = 'Please provide a valid email address';
        }

        // ABN, if present, must be 11 digits and pass ABN checksum
        if ($row['abn'] !== '') {
            if (strlen($row['abn']) !== 11) {
                $errors['abn'] = 'ABN must contain 11 digits';
            } elseif (! $this->isValidAbn($row['abn'])) {
                $errors['abn'] = 'ABN is invalid';
            }
        }

        // Contact phone: allow empty, or a valid Australian phone, or an email address
        if ($row['contact_phone'] !== '') {
            $phone = $row['contact_phone'];
            // If it's an email, it's valid here (user asked to allow email)
            if (filter_var($phone, FILTER_VALIDATE_EMAIL)) {
                // store as-is (email in phone field)
            } else {
                // try normalize as phone
                $normalized = $this->normalizePhone($phone);
                if ($normalized === '') {
                    $errors['contact_phone'] = 'Please provide a valid phone number or email';
                } elseif (! $this->isValidAustralianPhone($normalized)) {
                    $errors['contact_phone'] = 'Please provide a valid Australian phone number';
                } else {
                    // store normalized digits-only phone
                    $row['contact_phone'] = $normalized;
                }
            }
        }

        return ['row' => $row, 'errors' => $errors];
    }

    /**
     * Normalize phone: remove non-digits; convert leading +61 or 61 to leading 0.
     * Returns digits-only string or empty string if nothing numeric.
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return '';
        }

        // convert leading country code 61 to 0
        if (strpos($digits, '61') === 0) {
            $digits = '0' . substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Validate Australian phone number (simple rules): must be 10 digits starting with 0,
     * where prefixes indicate type (02,03,07,08 for landline; 04 for mobile; 13/1800/1300 allowed too).
     */
    private function isValidAustralianPhone(string $digits): bool
    {
        if (!preg_match('/^0\d+$/', $digits)) {
            return false;
        }

        $len = strlen($digits);
        // Standard 10-digit numbers (0XYYYYZZZZ)
        if ($len === 10) {
            $prefix = substr($digits, 0, 2);
            if (in_array($prefix, ['02','03','04','07','08'], true)) {
                return true;
            }
            return false;
        }

        // Service numbers like 13xx (4 digits), 1800/1300 (4 or 7?) treat as valid
        if (in_array($len, [4,6,7], true)) {
            // simple acceptance for short service numbers
            return true;
        }

        return false;
    }

    /**
     * Format phone for display: returns nicely grouped number or original if not recognized.
     */
    private function formatPhoneForDisplay(string $phone): string
    {
        // If it's an email, return as-is
        if (filter_var($phone, FILTER_VALIDATE_EMAIL)) {
            return $phone;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return trim($phone);
        }

        // convert leading country code 61 to 0 for display
        if (strpos($digits, '61') === 0) {
            $digits = '0' . substr($digits, 2);
        }

        $len = strlen($digits);
        if ($len === 10) {
            $prefix = substr($digits, 0, 2);
            if ($prefix === '04') {
                // mobile: 04 1234 5678 -> group 4-3-3: 0412 345 678
                return substr($digits, 0, 4) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7, 3);
            }
            // landline: 02 1234 5678
            return substr($digits, 0, 2) . ' ' . substr($digits, 2, 4) . ' ' . substr($digits, 6, 4);
        }

        // short service numbers like 13xx -> group 2-2
        if ($len === 4) {
            return substr($digits, 0, 2) . ' ' . substr($digits, 2, 2);
        }

        return $phone;
    }

    /**
     * Detect phone type: 'mobile', 'landline', 'service', 'email', or 'unknown'
     */
    private function detectPhoneType(string $phone): string
    {
        if (filter_var($phone, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return 'unknown';
        }
        if (strpos($digits, '61') === 0) {
            $digits = '0' . substr($digits, 2);
        }
        $len = strlen($digits);
        if ($len === 10) {
            $pref = substr($digits, 0, 2);
            if ($pref === '04') {
                return 'mobile';
            }
            if (in_array($pref, ['02','03','07','08'], true)) {
                return 'landline';
            }
        }
        if ($len === 4) {
            return 'service';
        }
        return 'unknown';
    }

    /**
     * ABN checksum validation.
     * Algorithm: subtract 1 from first digit, multiply digits by weights [10,1,3,5,7,9,11,13,15,17,19],
     * sum and check mod 89 === 0.
     */
    private function isValidAbn(string $abn): bool
    {
        $digits = preg_replace('/\D+/', '', $abn);
        if (strlen($digits) !== 11) {
            return false;
        }

        $weights = [10, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19];
        $digitsArr = array_map('intval', str_split($digits));
        $digitsArr[0] = $digitsArr[0] - 1;

        $sum = 0;
        foreach ($digitsArr as $i => $d) {
            $sum += $d * $weights[$i];
        }

        return ($sum % 89) === 0;
    }
}
