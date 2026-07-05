<?php

namespace App\Http\Controllers\Api\Communication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ContactMessage;
use App\Http\Requests\Api\Communication\StoreContactMessageRequest;
use App\Http\Requests\Api\Communication\ReplyContactMessageRequest;

class ContactMessageController extends Controller
{
    /**
     * إضافة رسالة جديدة من المستخدمين أو الزوار
     */
    public function store(StoreContactMessageRequest $request)
    {
        $data = $request->validated();

        // ربط الرسالة بالمستخدم إذا كان مسجلاً للدخول
        if (Auth::check()) {
            $data['user_id'] = Auth::id();
        }

        ContactMessage::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم استلام رسالتك بنجاح وسنتواصل معك قريباً!'
        ], 201);
    }

    public function index(Request $request)
    {
        $query = ContactMessage::query();

        if ($request->filled('status')) {

            switch ($request->status) {

                case 'new':
                    $query->where('status', '!=', 'replied')
                        ->where('created_at', '>=', now()->subDay());
                    break;

                case 'pending':
                    $query->where('status', '!=', 'replied');
                    break;

                case 'replied':
                    $query->where('status', 'replied');
                    break;
            }
        }

        $messages = $query->latest()->paginate(3);

        // UI type فقط للعرض
        $messages->getCollection()->transform(function ($message) {

            if ($message->status === 'replied') {
                $message->ui_type = 'replied';
            } elseif ($message->created_at && $message->created_at->greaterThan(now()->subDay())) {
                $message->ui_type = 'new';
            } else {
                $message->ui_type = 'pending';
            }

            return $message;
        });

        return response()->json($messages);
    }

    /**
     * الرد على رسالة (للأدمن فقط)
     */
    public function reply(ReplyContactMessageRequest $request, $id)
    {
        $message = ContactMessage::findOrFail($id);

        $message->update([
            'reply_text' => $request->validated()['reply_text'],
            'status'     => 'replied',
            'replied_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الرد وتغيير الحالة بنجاح'
        ]);
    }
}
