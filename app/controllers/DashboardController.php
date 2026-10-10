<?php

class DashboardController extends Controller
{
    public function initialize()
    {
        $this->loadComponents([]);

        $this->loadModel('DevelopmentCharge');
        $this->loadModel('Project');
        $this->loadModel('Notification');
        $this->loadModel('User');

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
        $users = [];

        if (
            isset($_SESSION['user_role']) &&
            $_SESSION['user_role'] === 'admin'
        ) {

            $users =
                $this->user
                    ->getAll();
        }

        /*
        |--------------------------------------------------------------------------
        | CURRENT USER
        |--------------------------------------------------------------------------
        */

        $currentUserId =
            isset($_SESSION['user_id'])
            ? (int) $_SESSION['user_id']
            : 1;


        /*
        |--------------------------------------------------------------------------
        | GENERATE AUTOMATIC REMINDERS FIRST
        |--------------------------------------------------------------------------
        */

        $this->notification
            ->generateChargePaymentReminders(
                $this->developmentCharge
            );


        $this->notification
            ->generateProjectPaymentReminders(
                $this->project
            );


        /*
        |--------------------------------------------------------------------------
        | THEN LOAD NOTIFICATIONS
        |--------------------------------------------------------------------------
        */

        $notifications =
            $this->notification
                ->getRecentForUser(
                    $currentUserId
                );


        $unreadNotificationCount =
            $this->notification
                ->getUnreadCount(
                    $currentUserId
                );


        $year = isset($_GET['year'])
            ? (int) $_GET['year']
            : 2026;

        $developmentCharges =
            $this->developmentCharge
                ->getAllByYear($year);

        $projects =
            $this->project
                ->getAllByYear($year);

        $totalCollection =
            $this->developmentCharge
                ->getTotalChargesByYear($year);

        $carriedForward = 0;

        $totalExpenditure = 0;

        $currentBalance =
            $carriedForward
            + $totalCollection
            - $totalExpenditure;


        $this->view->renderWithLayouts(

            Config::get('VIEWS_PATH') .
            'layout/cajpemajuan/',

            Config::get('VIEWS_PATH') .
            'dashboard/index.php',

            [
                'selectedYear' =>
                    $year,

                'developmentCharges' =>
                    $developmentCharges,

                'projects' =>
                    $projects,

                'totalCollection' =>
                    $totalCollection,

                'carriedForward' =>
                    $carriedForward,

                'totalExpenditure' =>
                    $totalExpenditure,

                'currentBalance' =>
                    $currentBalance,

                'notifications' =>
                    $notifications,

                'unreadNotificationCount' =>
                    $unreadNotificationCount,

                'currentUserId' =>
                    $currentUserId,

                'users' =>
                    $users
            ]
        );
    }
}