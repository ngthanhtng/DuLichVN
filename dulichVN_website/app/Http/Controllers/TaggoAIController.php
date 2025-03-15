<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Tour; // Import Model Tour

class TaggoAIController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Log::info('Received Webhook: ', $request->all());

        // Lấy nội dung tin nhắn từ người dùng
        $message = strtolower($request->input('message'));

        // Kiểm tra xem tin nhắn có liên quan đến tour không
        $responseMessage = $this->searchTour($message);

        // Gửi phản hồi về TaggoAI
        return response()->json([
            'reply' => $responseMessage
        ]);
    }

    private function searchTour($message)
    {
        // Tìm kiếm tour theo từ khóa
        $tour = Tour::where('title', 'LIKE', "%{$message}%")->first();

        if ($tour) {
            return "Tour {$tour->title} có giá người lớn: {$tour->priceAdult} VND, trẻ em: {$tour->priceChild} VND. Chi tiết: {$tour->description}";
        }

        return "Xin lỗi, không tìm thấy tour phù hợp với yêu cầu của bạn.";
    }

    public function sendToTaggoAI($message)
    {
        $response = Http::withHeaders([
            'x-api-key' => config('services.taggoai.api_key'),
            'Content-Type' => 'application/json',
        ])->post(config('services.taggoai.base_url') . '/v2/contact', [
            'message' => $message
        ]);

        return response()->json($response->json());
    }
}
