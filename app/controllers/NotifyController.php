<?php

class NotifyController extends ModuleController
{
    public function initialize()
    {
        $this->loadComponents([]);
        $this->loadModel('Notification');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {

            header(
                'Location: ' . PUBLIC_ROOT . 'auth'
            );

            exit;
        }
    }


    public function index()
    {
        $userId =
            $_SESSION['user_id'];

        $notifications =
            $this->notification
                ->getAllForUser(
                    $userId
                );

        $this->renderModule('notification/index.php', ['notifications' => $notifications]);

    }


    public function read($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                'Location: ' . PUBLIC_ROOT . 'dashboard'
            );

            exit;
        }

        $userId =
            $_SESSION['user_id'];

        $notification = $this->notification->getById($id);
        if ($notification) {
            $this->notification->markAsRead($id, $userId);
        }
        $target = $notification ? $this->notificationTarget($notification) : 'notification';
        header('Location: ' . PUBLIC_ROOT . $target);

        exit;
    }


    public function markAllRead()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                'Location: ' . PUBLIC_ROOT . 'notification'
            );

            exit;
        }

        $userId =
            $_SESSION['user_id'];

        $this->notification->markAllAsRead(
            $userId
        );

        header(
            'Location: ' . PUBLIC_ROOT . 'notification'
        );

        exit;
    }


    public function clearAll()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                'Location: ' . PUBLIC_ROOT . 'notification'
            );

            exit;
        }

        $this->notification->clearAll();

        header(
            'Location: ' . PUBLIC_ROOT . 'notification?success=notifications_cleared'
        );

        exit;
    }
    private function notificationTarget(array $notification)
    {
        $fallback = 'notification';
        $id = isset($notification['entity_id']) ? (int) $notification['entity_id'] : 0;
        if ($id <= 0 || in_array($notification['action'], ['deleted', 'user_deleted'], true)) {
            return $fallback;
        }
        $type = isset($notification['entity_type']) ? $notification['entity_type'] : '';
        if ($type === 'development_charge_payment' || $type === 'development_charge') {
            $this->loadModel('DevelopmentCharge');
            if ($type === 'development_charge_payment') {
                $payment = $this->developmentCharge->getPaymentById($id);
                if (!$payment) { return $fallback; }
                $id = (int) $payment['development_charge_id'];
            }
            return $this->developmentCharge->getById($id)
                ? 'charge/' . ($type === 'development_charge_payment' ? 'payment/' : 'edit/') . $id : $fallback;
        }
        if ($type === 'project' || $type === 'project_payment_schedule') {
            $this->loadModel('Project');
            if ($type === 'project_payment_schedule') {
                $schedule = $this->project->getScheduleById($id);
                if (!$schedule) { return $fallback; }
                $id = (int) $schedule['project_id'];
            }
            return $this->project->getById($id) ? 'project/finance/' . $id : $fallback;
        }
        if ($type === 'user' && $_SESSION['user_role'] === 'admin') {
            $this->loadModel('User');
            return $this->user->getById($id) ? 'user/edit/' . $id : $fallback;
        }
        return $fallback;
    }
}
