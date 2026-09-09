<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notifikasi in-app pada navbar.
 *
 * Panel navbar melakukan polling ke endpoint feed() sehingga daftar
 * notifikasi & jumlah yang belum dibaca selalu mengikuti kondisi terbaru
 * tanpa perlu memuat ulang halaman.
 */
class NotificationController extends Controller
{
    /** Jumlah maksimal item pada dropdown navbar. */
    private const FEED_LIMIT = 12;

    /**
     * Data notifikasi untuk dropdown navbar (JSON).
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = Notification::query()
            ->forUser($user->id)
            ->latest()
            ->limit(self::FEED_LIMIT)
            ->get();

        $unread = Notification::query()->forUser($user->id)->unread()->count();

        return response()->json([
            'unread_count' => $unread,
            'total'        => Notification::query()->forUser($user->id)->count(),
            'server_time'  => now()->toIso8601String(),
            'items'        => $items->map(fn (Notification $n) => [
                'id'      => $n->id,
                'tipe'    => $n->tipe,
                'judul'   => $n->judul,
                'pesan'   => $n->pesan,
                'url'     => $n->url,
                'icon'    => $n->iconName(),
                'accent'  => $n->accentColor(),
                'unread'  => $n->isUnread(),
                'waktu'   => $n->created_at?->diffForHumans(),
                'tanggal' => $n->created_at?->format('d M Y H:i'),
            ])->all(),
        ]);
    }

    /**
     * Tandai satu notifikasi sudah dibaca.
     */
    public function markRead(Request $request, int $id)
    {
        $notification = Notification::query()
            ->forUser($request->user()->id)
            ->findOrFail($id);

        if ($notification->isUnread()) {
            $notification->update(['read_at' => now()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok'           => true,
                'id'           => $notification->id,
                'unread_count' => Notification::query()->forUser($request->user()->id)->unread()->count(),
            ]);
        }

        if ($notification->url) {
            return redirect()->to($notification->url);
        }

        return redirect()->back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    /**
     * Tandai seluruh notifikasi sudah dibaca.
     */
    public function markAllRead(Request $request)
    {
        $affected = Notification::query()
            ->forUser($request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'           => true,
                'marked'       => $affected,
                'unread_count' => 0,
            ]);
        }

        return redirect()->back()->with('success', $affected . ' notifikasi ditandai sudah dibaca.');
    }

    /**
     * Halaman daftar seluruh notifikasi.
     */
    public function index(Request $request)
    {
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';

        $query = Notification::query()->forUser($request->user()->id)->latest();

        if ($filter === 'unread') {
            $query->unread();
        }

        $notifications = $query->paginate(20)->withQueryString();
        $unreadCount   = Notification::query()->forUser($request->user()->id)->unread()->count();

        return view('notifikasi.index', compact('notifications', 'unreadCount', 'filter'));
    }
}
