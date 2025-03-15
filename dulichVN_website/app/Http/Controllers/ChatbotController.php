<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Promotion;
use App\Models\clients\Tours; // Add this line
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $message = $request->input('message');

        // Nếu user yêu cầu địa điểm du lịch, lấy từ database
        if (strpos(strtolower($message), 'địa điểm du lịch') !== false
            || strpos(strtolower($message), 'tour') !== false) {
            $spots = Tours::select('title as name', 'tourId as id')->take(5)->get(); // Replace TouristSpot with Tours
            $responseText = 'Dưới đây là các địa điểm du lịch nổi bật: ';
            foreach ($spots as $spot) {
                $responseText .= '<a href="/tour-detail/' . $spot->id . '">' . $spot->name . '</a>, ';
            }
            $responseText = rtrim($responseText, ', ');

            return response()->json([
                'response' => $responseText
            ]);
        }

        // Nếu user yêu cầu khuyến mãi, lấy từ database
        if (strpos(strtolower($message), 'khuyến mãi') !== false) {
            $promotions = Promotion::select('description', 'discount')->take(3)->get();
            return response()->json([
                'response' => 'Khuyến mãi hiện tại: ' . 
                    implode(', ', $promotions->pluck('description')->toArray())
            ]);
        }

        // Gọi API Gemini cho câu hỏi chung
        $client = new Client();
        $apiKey = env('GEMINI_API_KEY');
        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=' . $apiKey;

        try {
            $response = $client->post($endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        ['parts' => [['text' => "Bạn là chatbot tư vấn du lịch thông minh. Hãy trả lời câu hỏi: " . $message]]]
                    ]
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return response()->json([
                'response' => $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Xin lỗi, tôi chưa có thông tin về câu hỏi này.'
            ]);
        } catch (RequestException $e) {
            \Log::error('Gemini API Error: ' . ($e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage()));
            return response()->json(['response' => 'Xin lỗi, đã xảy ra lỗi khi xử lý yêu cầu.']);
        } catch (\Exception $e) {
            \Log::error('General Error: ' . $e->getMessage());
            return response()->json(['response' => 'Xin lỗi, đã xảy ra lỗi khi xử lý yêu cầu.']);
        }
    }
}