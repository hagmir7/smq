<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List all notifications for the authenticated user (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->notifications();

        // Optional filter: ?status=unread | ?status=read
        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->whereNull('read_at');
            } elseif ($request->status === 'read') {
                $query->whereNotNull('read_at');
            }
        }

        // Optional filter: ?type=App\Notifications\CorrectiveActionCreated
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->paginate($request->integer('per_page', 15));

        return response()->json($notifications);
    }

    /**
     * List only unread notifications.
     */
    public function unread(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->paginate($request->integer('per_page', 15));

        return response()->json($notifications);
    }

    /**
     * Get unread notifications count (for a bell icon badge).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Show a single notification, and mark it as read on view.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json($notification);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read.',
            'notification' => $notification,
        ]);
    }

    /**
     * Mark a single notification as unread.
     */
    public function markAsUnread(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->update(['read_at' => null]);

        return response()->json([
            'message' => 'Notification marked as unread.',
            'notification' => $notification,
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();

        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => "{$count} notification(s) marked as read.",
        ]);
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted.',
        ]);
    }

    /**
     * Delete all read notifications (cleanup).
     */
    public function destroyRead(Request $request): JsonResponse
    {
        $count = $request->user()
            ->notifications()
            ->whereNotNull('read_at')
            ->delete();

        return response()->json([
            'message' => "{$count} read notification(s) deleted.",
        ]);
    }

    /**
     * Delete all notifications for the user.
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $count = $request->user()->notifications()->delete();

        return response()->json([
            'message' => "{$count} notification(s) deleted.",
        ]);
    }
}