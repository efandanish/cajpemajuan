<?php

class NotifyController extends Controller
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

        $this->view->renderWithLayouts(
            Config::get('VIEWS_PATH') .
            'layout/cajpemajuan/',

            Config::get('VIEWS_PATH') .
            'notification/index.php',

            [
                'notifications' =>
                    $notifications
            ]
        );
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

        $this->notification->markAsRead(
            $id,
            $userId
        );

        header(
            'Location: ' . PUBLIC_ROOT . 'dashboard'
        );

        exit;
    }


    public function markAllRead()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                'Location: ' . PUBLIC_ROOT . 'notify'
            );

            exit;
        }

        $userId =
            $_SESSION['user_id'];

        $this->notification->markAllAsRead(
            $userId
        );

        header(
            'Location: ' . PUBLIC_ROOT . 'dashboard'
        );

        exit;
    }


    public function clearAll()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                'Location: ' . PUBLIC_ROOT . 'notify'
            );

            exit;
        }

        $this->notification->clearAll();

        header(
            'Location: ' . PUBLIC_ROOT . 'notify?success=notifications_cleared'
        );

        exit;
    }
}