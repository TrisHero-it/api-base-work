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
<form action="/vnpay/create" method="GET">
    <label for="amount">Số tiền thanh toán (VNĐ):</label>
    <input type="number" name="amount" required>
    <button type="submit">Thanh toán với VNPay</button>
</form>
</body>
</html>
