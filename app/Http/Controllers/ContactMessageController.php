<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\ContactReply;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContactMessageController extends Controller
{
    /**
     * Menampilkan data pesan.
     * Filter berdasarkan email untuk User, atau tampilkan semua untuk Admin.
     */
    public function index(Request $request)
    {
        try {
            $email = $request->query('email');

            if (!$email) {
                $user = auth('sanctum')->user();
                if (!$user || !$user->is_admin) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Akses ditolak. Login sebagai admin atau sertakan parameter email.',
                    ], 403);
                }

                $messages = ContactMessage::with('replies')
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                $messages = ContactMessage::with('replies')
                    ->where('email', $email)
                    ->orderBy('created_at', 'asc')
                    ->get();
            }

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil data pesan.',
                'data' => $messages,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request)
    {
        $email = $request->query('email');

        try {
            if ($email) {
                $messages = ContactMessage::with('replies')
                    ->where('email', $email)
                    ->orderBy('created_at', 'asc')
                    ->get();
            } else {
                $messages = ContactMessage::with('replies')
                    ->orderBy('created_at', 'asc')
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => $messages,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        try {
            $contact = ContactMessage::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Pesan berhasil dikirim! Tim kami akan segera menghubungi Anda.',
                'data' => $contact,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengirim pesan. Silakan coba lagi.',
            ], 500);
        }
    }

    /**
     * Reply ke satu ticket (legacy).
     */
    public function reply(Request $request, $id)
    {
        $validated = $request->validate([
            'reply_message' => 'required|string',
        ]);

        try {
            $message = ContactMessage::findOrFail($id);

            $message->replies()->create([
                'reply_message' => $validated['reply_message'],
                'replied_by' => Auth::id() ?? 1,
            ]);

            $message->update(['is_read_by_admin' => true]);
            $message->load('replies');

            return response()->json([
                'success' => true,
                'message' => 'Balasan berhasil dikirim.',
                'data' => $message,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengirim balasan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Daftar percakapan 1-1: semua user non-admin + email kontak guest.
     */
    public function conversations()
    {
        try {
            $users = User::query()
                ->where(function ($q) {
                    $q->where('is_admin', false)->orWhereNull('is_admin');
                })
                ->orderBy('name')
                ->get(['id', 'name', 'nama_lengkap', 'email', 'avatar_profile', 'phone']);

            $contactEmails = ContactMessage::query()
                ->select('email', DB::raw('MAX(name) as name'), DB::raw('MAX(created_at) as last_at'))
                ->groupBy('email')
                ->get()
                ->keyBy(fn ($row) => strtolower($row->email));

            $userEmails = $users->map(fn ($u) => strtolower($u->email))->all();

            $conversations = $users->map(function (User $user) {
                return $this->buildConversationSummary(
                    email: $user->email,
                    name: $user->nama_lengkap ?: $user->name,
                    avatar: $user->avatar_profile,
                    userId: $user->id,
                    phone: $user->phone,
                );
            })->values();

            foreach ($contactEmails as $emailKey => $row) {
                if (in_array($emailKey, $userEmails, true)) {
                    continue;
                }
                $conversations->push($this->buildConversationSummary(
                    email: $row->email,
                    name: $row->name ?: $row->email,
                    avatar: null,
                    userId: null,
                    phone: null,
                ));
            }

            $conversations = $conversations
                ->sortByDesc(fn ($c) => $c['last_message_at'] ?? '')
                ->values();

            return response()->json([
                'success' => true,
                'data' => $conversations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat daftar percakapan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Thread chat 1-1 berdasarkan email.
     */
    public function thread(Request $request)
    {
        $email = $request->query('email') ?: $request->input('email');
        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Email wajib diisi.',
            ], 422);
        }

        try {
            $tickets = ContactMessage::with('replies')
                ->where('email', $email)
                ->orderBy('created_at', 'asc')
                ->get();

            ContactMessage::where('email', $email)
                ->where(function ($q) {
                    $q->where('is_read_by_admin', false)->orWhereNull('is_read_by_admin');
                })
                ->update(['is_read_by_admin' => true]);

            $bubbles = [];
            foreach ($tickets as $msg) {
                $bubbles[] = [
                    'id' => 'msg-' . $msg->id,
                    'type' => 'user',
                    'text' => $msg->message,
                    'subject' => $msg->subject,
                    'created_at' => optional($msg->created_at)->toIso8601String(),
                    'ticket_id' => $msg->id,
                ];
                foreach ($msg->replies ?? [] as $reply) {
                    $bubbles[] = [
                        'id' => 'reply-' . $reply->id,
                        'type' => 'admin',
                        'text' => $reply->reply_message,
                        'created_at' => optional($reply->created_at)->toIso8601String(),
                        'ticket_id' => $msg->id,
                        'reply_id' => $reply->id,
                    ];
                }
            }

            usort($bubbles, function ($a, $b) {
                return strcmp($a['created_at'] ?? '', $b['created_at'] ?? '');
            });

            $user = User::where('email', $email)->first();
            $latest = $tickets->last();

            return response()->json([
                'success' => true,
                'data' => [
                    'email' => $email,
                    'name' => $user?->nama_lengkap ?: ($user?->name ?: ($latest?->name ?: $email)),
                    'avatar' => $user?->avatar_profile,
                    'user_id' => $user?->id,
                    'phone' => $user?->phone,
                    'messages' => $bubbles,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat percakapan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin kirim chat langsung ke user.
     */
    public function sendToUser(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'message' => 'required|string',
            'name' => 'nullable|string|max:255',
        ]);

        try {
            $email = $validated['email'];
            $ticket = ContactMessage::where('email', $email)
                ->orderByDesc('created_at')
                ->first();

            if (!$ticket) {
                $user = User::where('email', $email)->first();
                $ticket = ContactMessage::create([
                    'name' => $validated['name']
                        ?: ($user?->nama_lengkap ?: $user?->name ?: 'Pelanggan'),
                    'email' => $email,
                    'subject' => 'Chat Admin Evomi',
                    'message' => '[Percakapan dimulai oleh admin]',
                    'is_read_by_admin' => true,
                ]);
            } else {
                $ticket->update(['is_read_by_admin' => true]);
            }

            $reply = $ticket->replies()->create([
                'reply_message' => $validated['message'],
                'replied_by' => Auth::id() ?? 1,
                'is_read_by_user' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pesan terkirim.',
                'data' => [
                    'id' => 'reply-' . $reply->id,
                    'type' => 'admin',
                    'text' => $reply->reply_message,
                    'created_at' => optional($reply->created_at)->toIso8601String(),
                    'ticket_id' => $ticket->id,
                    'reply_id' => $reply->id,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim pesan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus seluruh chat 1-1 untuk email.
     */
    public function destroyConversation(Request $request)
    {
        $email = $request->input('email') ?: $request->query('email');
        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Email wajib diisi.',
            ], 422);
        }

        try {
            $ids = ContactMessage::where('email', $email)->pluck('id');
            if ($ids->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tidak ada chat untuk dihapus.',
                    'deleted' => 0,
                ]);
            }

            ContactReply::whereIn('contact_message_id', $ids)->delete();
            $deleted = ContactMessage::whereIn('id', $ids)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Percakapan berhasil dihapus.',
                'deleted' => $deleted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus percakapan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUnreadCount(Request $request)
    {
        $email = $request->query('email');
        if (!$email) {
            return response()->json(['count' => 0]);
        }

        $count = ContactReply::where(function ($q) {
            $q->where('is_read_by_user', false)
                ->orWhereNull('is_read_by_user');
        })
            ->whereHas('contactMessage', function ($query) use ($email) {
                $query->where('email', $email);
            })->count();

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function markUserRead(Request $request)
    {
        $email = $request->input('email');
        if (!$email) {
            return response()->json(['success' => false, 'message' => 'Email tidak valid']);
        }

        try {
            $messageIds = ContactMessage::where('email', $email)->pluck('id');

            ContactReply::whereIn('contact_message_id', $messageIds)
                ->where(function ($q) {
                    $q->where('is_read_by_user', false)
                        ->orWhereNull('is_read_by_user');
                })
                ->update(['is_read_by_user' => true]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function buildConversationSummary(
        string $email,
        string $name,
        ?string $avatar,
        ?int $userId,
        ?string $phone,
    ): array {
        $tickets = ContactMessage::with('replies')
            ->where('email', $email)
            ->orderBy('created_at', 'asc')
            ->get();

        $lastPreview = null;
        $lastAt = null;
        $unread = 0;

        foreach ($tickets as $msg) {
            $unread += ($msg->is_read_by_admin ? 0 : 1);
            $lastPreview = $msg->message;
            $lastAt = optional($msg->created_at)->toIso8601String();

            foreach ($msg->replies ?? [] as $reply) {
                $lastPreview = $reply->reply_message;
                $lastAt = optional($reply->created_at)->toIso8601String();
            }
        }

        return [
            'email' => $email,
            'name' => $name,
            'avatar' => $avatar,
            'user_id' => $userId,
            'phone' => $phone,
            'last_message' => $lastPreview,
            'last_message_at' => $lastAt,
            'unread_count' => $unread,
            'message_count' => $tickets->count(),
            'has_chat' => $tickets->isNotEmpty(),
        ];
    }
}
