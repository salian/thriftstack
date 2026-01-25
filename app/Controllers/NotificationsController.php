<?php

declare(strict_types=1);

final class NotificationsController
{
    private NotificationService $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): Response
    {
        $userId = (int)($request->session('user')['id'] ?? 0);
        $notifications = $userId > 0 ? $this->service->listForUser($userId) : [];

        return Response::html(View::render('notifications/index', [
            'title' => 'Notifications',
            'notifications' => $notifications,
        ]));
    }

    public function markRead(Request $request): Response
    {
        if (!Csrf::validate($request->input('_token'))) {
            return Response::forbidden(View::render('403', ['title' => 'Forbidden']));
        }

        $userId = (int)($request->session('user')['id'] ?? 0);
        $notificationId = (int)$request->input('notification_id', 0);
        if ($userId > 0 && $notificationId > 0) {
            $this->service->markRead($userId, $notificationId);
        }

        return Response::redirect('/notifications');
    }
}
