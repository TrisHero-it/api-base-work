<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class VNPayController extends Controller
{
    protected $vnpayService;

    public function __construct(Request $vnpayService)
    {
        $this->vnpayService = $vnpayService;
    }

    // Hàm tạo mã QR thanh toán
    public function createPayment(Request $request)
    {
        $order_id = time(); // Sử dụng thời gian làm order ID
        $amount = $request->input('amount'); // Số tiền thanh toán
        $order_desc = 'Thanh toán đơn hàng #' . $order_id; // Mô tả đơn hàng

        // Tạo URL thanh toán VNPay
        $paymentUrl = $this->vnpayService->createPaymentUrl($order_id, $amount, $order_desc);

        // Tạo mã QR từ URL thanh toán
        $qrCode = QrCode::size(200)->generate($paymentUrl);

        // Trả về mã QR cho người dùng
        return view('payment.qrcode', compact('qrCode', 'paymentUrl'));
    }

    // Hàm xử lý callback từ VNPay
    public function handleCallback(Request $request)
    {
        $vnp_TmnCode = config('vnpay.vnp_TmnCode');
        $vnp_HashSecret = config('vnpay.vnp_HashSecret');

        // Kiểm tra mã phản hồi từ VNPay
        $vnp_SecureHash = $request->input('vnp_SecureHash');
        $vnp_ResponseCode = $request->input('vnp_ResponseCode');
        $vnp_TxnRef = $request->input('vnp_TxnRef');

        // Tạo lại mã SecureHash từ các tham số và so sánh với mã SecureHash nhận được
        // Nếu SecureHash hợp lệ, thực hiện các hành động cần thiết như cập nhật trạng thái đơn hàng
        $inputData = $request->all();
        ksort($inputData);
        $hashData = urldecode(http_build_query($inputData));
        $calculatedSecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        if ($vnp_SecureHash === $calculatedSecureHash) {
            if ($vnp_ResponseCode == '00') {
                // Thanh toán thành công
                return "Thanh toán thành công!";
            } else {
                // Thanh toán thất bại
                return "Thanh toán thất bại!";
            }
        } else {
            // Lỗi xác thực SecureHash
            return "Lỗi xác thực!";
        }
    }
}
