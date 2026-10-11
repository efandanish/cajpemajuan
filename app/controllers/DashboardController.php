<?php

class DashboardController extends Controller
{
    public function initialize()
    {
        $this->loadComponents([]);

        $this->loadModel('DevelopmentCharge');
        $this->loadModel('Project');
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

        $totalCollection =
            $this->developmentCharge
                ->getTotalChargesByYear($year);

        $carriedForward = 0;

        $totalExpenditure = 0;

        $currentBalance =
            $carriedForward
            + $totalCollection
            - $totalExpenditure;

        $chargeSummary = $this->developmentCharge->getDashboardSummaryByYear($year);
        $projectSummary = $this->project->getDashboardSummaryByYear($year);


        $this->view->renderWithLayouts(

            Config::get('VIEWS_PATH') .
            'layout/cajpemajuan/',

            Config::get('VIEWS_PATH') .
            'dashboard/index.php',

            [
                'selectedYear' =>
                    $year,

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

                'chargeSummary' => $chargeSummary,
                'projectSummary' => $projectSummary,

            ]
        );
    }
}
