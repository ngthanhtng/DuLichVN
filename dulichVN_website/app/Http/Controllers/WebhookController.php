<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Tour;

class WebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Log::info('Webhook received:', $request->all());

        $message = $request->input('message');  // Tin nhắn từ người dùng
        $conversationId = $request->input('conversation_id'); // ID cuộc hội thoại (quan trọng để TaggoAI biết phản hồi cho ai)
        
        // Kiểm tra xem người dùng hỏi về tour du lịch không
        if (strpos(strtolower($message), 'tour') !== false) {
            // Lấy danh sách tour từ database
            $tours = Tour::where('availability', 1)->get(['title', 'destination', 'priceAdult', 'startDate', 'endDate']);

            if ($tours->isEmpty()) {
                $reply = 'Hiện tại không có tour nào khả dụng.';
            } else {
                $reply = "Danh sách tour khả dụng:\n";
                foreach ($tours as $tour) {
                    $reply .= "- {$tour->title} ({$tour->destination}): {$tour->priceAdult} VND từ {$tour->startDate} đến {$tour->endDate}\n";
                }
            }

            // Gửi phản hồi đến TaggoAI Inbox
            Http::withHeaders([
                'Authorization' => 'Bearer sk-f92ef8e6-5fb7-5409-9888-c2628410bf4c',
                'Content-Type'  => 'application/json',
            ])->post('https://app.taggoai.com/inbox/message', [
                'conversation_id' => $conversationId,
                'reply' => $reply
            ]);

            return response()->json(['status' => 'success']);
        }

        // Nếu không tìm thấy thông tin, gửi phản hồi mặc định
        Http::withHeaders([
            'Authorization' => 'Bearer sk-f92ef8e6-5fb7-5409-9888-c2628410bf4c',
            'Content-Type'  => 'application/json',
        ])->post('https://app.taggoai.com/inbox/message', [
            'conversation_id' => $conversationId,
            'reply' => "Mình chưa hiểu câu hỏi của bạn. Vui lòng thử lại!"
        ]);

        return response()->json(['status' => 'success']);

        //
        Log::info('Received request:', $request->all()); // Ghi log request

        return response()->json(['status' => 'success']);
    }
}
