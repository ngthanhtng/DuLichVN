<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\clients\Promotion;
use App\Models\clients\Tours;
use App\Models\clients\ChatHistory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $message = strtolower($request->input('message'));
        $responseText = null; // Khởi tạo biến mặc định

        // Kịch bản: Tour giá rẻ - linh hoạt theo giá người dùng nhắc đến
        if (preg_match('/dưới ([\d,.]+) ?triệu/', $message, $matches)) {
            $maxPrice = (float)str_replace([',', '.'], '', $matches[1]) * 1000000;
            $tours = Tours::where('priceAdult', '<=', $maxPrice)->take(5)->get();

            if ($tours->isEmpty()) {
                $responseText = 'Hiện không có tour nào dưới ' . $matches[1] . ' triệu vào lúc này.';
            } else {
                $responseText = 'Dưới đây là các tour dưới ' . $matches[1] . ' triệu đồng:<br>';
                foreach ($tours as $tour) {
                    $responseText .= '<a href="/tour-detail/' . $tour->tourId . '">' . $tour->title . '</a> - ' . number_format($tour->priceAdult) . 'đ<br>';
                }
            }

            return response()->json(['response' => $responseText]);
        }

        // Kịch bản: Tour theo miền Bắc, Trung, Nam từ cột domain
        if (strpos($message, 'miền bắc') !== false) {
            $tours = Tours::where('domain', 'b')->take(5)->get();
            $region = 'miền Bắc';
        } elseif (strpos($message, 'miền trung') !== false) {
            $tours = Tours::where('domain', 't')->take(5)->get();
            $region = 'miền Trung';
        } elseif (strpos($message, 'miền nam') !== false) {
            $tours = Tours::where('domain', 'n')->take(5)->get();
            $region = 'miền Nam';
        } else {
            $tours = null;
            $region = null;
        }

        if (!is_null($tours)) {
            if ($tours->isEmpty()) {
                $responseText = "Hiện tại không có tour nào ở $region.";
            } else {
                $responseText = "Các tour du lịch ở $region:<br>";
                foreach ($tours as $tour) {
                    $responseText .= '<a href="/tour-detail/' . $tour->tourId . '">' . $tour->title . '</a><br>';
                }
            }

            return response()->json(['response' => $responseText]);
        }

        // Kịch bản: Tour nói chung
        if (strpos($message, 'địa điểm du lịch') !== false || strpos($message, 'tour') !== false) {
            $tours = Tours::take(5)->get();

            if ($tours->isEmpty()) {
                $responseText = 'Hiện tại không có tour nào khả dụng.';
            } else {
                $tourList = '';
                foreach ($tours as $tour) {
                    $tourList .= "- Tên tour: {$tour->title}\n  Mô tả: {$tour->description}\n  Vị trí: {$tour->destination}\n  Giá: {$tour->priceAdult}\n  Ngày khởi hành: {$tour->startDate}\n\n";
                }

                $fullPrompt = "Bạn là chatbot tư vấn du lịch. Dưới đây là danh sách tour từ hệ thống:
$tourList
Người dùng hỏi: $message
Hãy tư vấn một cách thân thiện và hữu ích dựa trên danh sách tour trên.";

                $client = new Client();
                $apiKey = env('GEMINI_API_KEY');
                $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=' . $apiKey;

                try {
                    $response = $client->post($endpoint, [
                        'headers' => ['Content-Type' => 'application/json'],
                        'json' => [
                            'contents' => [
                                ['parts' => [['text' => $fullPrompt]]]
                            ]
                        ],
                    ]);

                    $data = json_decode($response->getBody(), true);
                    $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Xin lỗi, tôi chưa có câu trả lời phù hợp.';
                    $responseText = nl2br($reply);

                } catch (RequestException $e) {
                    \Log::error('Gemini API Error: ' . ($e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage()));
                    $responseText = 'Xin lỗi, hệ thống đang gặp lỗi khi gọi AI.';
                } catch (\Exception $e) {
                    \Log::error('General Error: ' . $e->getMessage());
                    $responseText = 'Xin lỗi, hệ thống đang gặp lỗi.';
                }
            }

            return response()->json(['response' => $responseText]);
        }

        // Kịch bản: Khuyến mãi
        if (strpos($message, 'khuyến mãi') !== false || strpos($message, 'ưu đãi') !== false) {
            $promotions = Promotion::select('description', 'discount')->take(3)->get();

            if ($promotions->isEmpty()) {
                $responseText = 'Hiện tại chưa có chương trình khuyến mãi nào.';
            } else {
                $responseText = "Khuyến mãi hiện tại:<br>";
                foreach ($promotions as $promo) {
                    $responseText .= '- ' . $promo->description . ' (' . $promo->discount . '%)<br>';
                }
            }

            return response()->json(['response' => $responseText]);
        }

        // Mặc định
        $responseText = 'Xin lỗi, tôi chưa hiểu yêu cầu của bạn. Bạn có thể hỏi về tour du lịch, địa điểm gần bạn hoặc các khuyến mãi hiện có.';

        // Lưu lịch sử trò chuyện
        // ChatHistory::create([
        //     'user_id' => $request->user()->id ?? null, // Nếu có hệ thống đăng nhập
        //     'message' => $message,
        //     'response' => $responseText,
        // ]);

        // return response()->json(['response' => $responseText]);
    }
}
