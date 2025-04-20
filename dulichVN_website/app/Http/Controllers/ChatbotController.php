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
    public function greeting()  
    {  
        $greetingText = "<p>Chào bạn! Tôi có thể giúp gì cho bạn hôm nay?</p>";  
        $suggestedQuestions = [  
            "Cho tôi xem các tour dưới 5 triệu",  
            "Có tour miền Bắc nào không?",  
            "Tour du lịch miền Trung đang có gì?",  
            "Bạn giới thiệu cho tôi vài tour nổi bật"  
        ];  

        // Tạo HTML danh sách câu hỏi để frontend hiển thị dạng nút  
        $suggestionsHtml = '<div id="suggestedQuestions" style="margin-top:10px;">';  
        foreach ($suggestedQuestions as $q) {  
            // Mỗi câu hỏi nên có class hoặc data-attribute để frontend bắt sự kiện click  
            $suggestionsHtml .= "<button class='suggested-question' style='margin:5px; padding:8px 12px; cursor:pointer; text-align: left;' data-question='{$q}'>{$q}</button>";  
        }  
        $suggestionsHtml .= '</div>';  

        $responseText = $greetingText . $suggestionsHtml;  

        return response()->json(['response' => $responseText]);  
    }

    public function chat(Request $request)
    {
        $message = strtolower($request->input('message'));
        $responseText = null;
        $filteredTours = collect();
        $filterDescription = 'danh sách tour hiện có';

        // Bộ lọc theo giá  
        if (preg_match('/dưới ([\d,.]+) ?triệu/', $message, $matches)) {
            $maxPrice = (float)str_replace([',', '.'], '', $matches[1]) * 1000000;
            $filteredTours = Tours::where('priceAdult', '<=', $maxPrice)->take(5)->get();
            $filterDescription = "các tour dưới {$matches[1]} triệu đồng";
        }
        // Bộ lọc theo vùng miền  
        elseif (strpos($message, 'miền bắc') !== false) {
            $filteredTours = Tours::where('domain', 'b')->take(5)->get();
            $filterDescription = "các tour khu vực miền Bắc";
        } elseif (strpos($message, 'miền trung') !== false) {
            $filteredTours = Tours::where('domain', 't')->take(5)->get();
            $filterDescription = "các tour khu vực miền Trung";
        } elseif (strpos($message, 'miền nam') !== false) {
            $filteredTours = Tours::where('domain', 'n')->take(5)->get();
            $filterDescription = "các tour khu vực miền Nam";
        }
        // Không có keyword đặc biệt → lấy ngẫu nhiên 5 tour  
        else {
            $filteredTours = Tours::inRandomOrder()->take(5)->get();
        }

        // Chuẩn bị nội dung tour cho Gemini  
        $tourInfo = '';
        foreach ($filteredTours as $tour) {
            $detail = (new Tours())->getTourDetail($tour->tourId);
            if ($detail) {
                $link = url('/tour-detail/' . $detail->tourId);
                $image = $detail->images->first() ?? 'default-image.jpg'; // Đảm bảo đường dẫn hình ảnh tồn tại  
                $tourInfo .= "<div style='border:1px solid #ccc; margin-bottom: 10px; padding: 10px;'>";
                $tourInfo .= "<img src='{$image}' alt='{$detail->title}' style='width: 100px; height: 100px; object-fit: cover; margin-right: 10px; float: left;'>";
                $tourInfo .= "<strong>Tên tour:</strong> {$detail->title}<br>";
                $tourInfo .= "<strong>Mô tả:</strong> {$detail->description}<br>";
                $tourInfo .= "<strong>Vị trí:</strong> {$detail->destination}<br>";
                $tourInfo .= "<strong>Giá:</strong> " . number_format($detail->priceAdult) . "đ<br>";
                $tourInfo .= "<a href='{$link}' target='_blank'>Xem chi tiết</a>";
                $tourInfo .= "</div><div style='clear: both;'></div>"; // Đảm bảo các div không bị chồng lên nhau  
            }
        }

        $fullPrompt = "Bạn là chatbot tư vấn du lịch thân thiện và thông minh. Dưới đây là $filterDescription:\n$tourInfo\nNgười dùng hỏi: \"$message\"\nDựa trên danh sách trên, hãy gợi ý một cách tự nhiên, hấp dẫn và có link đến tour phù hợp. (Phản hồi ở dạng html thuần, hạn chế xuống dòng nhiều lần.)";

        // Gọi Gemini API  
        try {
            $client = new \GuzzleHttp\Client();
            $apiKey = env('GEMINI_API_KEY');
            $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=' . $apiKey;

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
            $responseText = nl2br($reply) . "<br/><h4>Các gợi ý tour:</h4>$tourInfo"; // Đưa vào các tour gợi ý sau phản hồi  

        } catch (\Exception $e) {
            \Log::error('Gemini Error: ' . $e->getMessage());
            $responseText = 'Xin lỗi, hệ thống đang gặp lỗi. Vui lòng thử lại sau.';
        }

        return response()->json(['response' => $responseText]);
    }
}
