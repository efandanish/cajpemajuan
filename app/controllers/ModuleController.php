<?php

abstract class ModuleController extends Controller
{
    protected function requireLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . PUBLIC_ROOT . 'auth');
            exit;
        }
    }

    protected function requireAdmin()
    {
        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: ' . PUBLIC_ROOT . 'dashboard?error=unauthorized');
            exit;
        }
    }

    protected function renderModule($view, array $data = [])
    {
        $this->loadModel('Notification');
        $data['unreadNotificationCount'] = $this->notification->getUnreadCount($_SESSION['user_id']);
        if (!isset($data['notifications'])) {
            $data['notifications'] = $this->notification->getRecentForUser($_SESSION['user_id']);
        }
        $this->view->renderWithLayouts(
            Config::get('VIEWS_PATH') . 'layout/cajpemajuan/',
            Config::get('VIEWS_PATH') . $view,
            $data
        );
    }
}
