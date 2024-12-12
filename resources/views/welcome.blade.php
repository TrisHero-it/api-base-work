<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
<form action="{{ url('/payment/create') }}" method="GET">
    <label for="amount">Số tiền (VND):</label>
    <input type="number" id="amount" name="amount" required>
    <label for="bank_code">Ngân hàng:</label>
    <select id="bank_code" name="bank_code">
        <option value="">Không chọn</option>
        <option value="NCB">Ngân hàng NCB</option>
        <option value="AGRIBANK">Ngân hàng Agribank</option>
        <!-- Thêm các ngân hàng khác -->
    </select>
    <button type="submit">Thanh toán</button>
</form>

</body>
</html>
