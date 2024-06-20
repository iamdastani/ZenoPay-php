<?php

class ZenoPay {
    private $api_key;
    private $sandbox;
    private $endpoint;
    private $webhook_url;
    
    public function __construct($api_key, $sandbox, $webhook_url) {
        $this->api_key = $api_key;
        $this->sandbox = $sandbox;
        $this->endpoint = 'https://zeno.co.tz/api/v1/pay';
        $this->webhook_url = $webhook_url;
    }

    public function make_payment($amount, $transaction_id, $phone_number, $description) {
        $payload = [
            'api' => 170,
            'code' => 104,
            'data' => [
                'api_key' => $this->api_key,
                'order_id' => $transaction_id,
                'amount' => $amount,
                'username' => $description,
                'is_live' => !$this->sandbox,
                'phone_number' => $phone_number,
                'webhook_url' => $this->webhook_url
            ]
        ];

        $headers = [
            'Content-Type: application/json'
        ];

        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code >= 400) {
            throw new Exception('HTTP error: ' . $http_code . ' - ' . $response);
        }

        $data = json_decode($response, true);
        if ($data === null) {
            throw new Exception('Error decoding JSON response: ' . json_last_error_msg());
        }

        curl_close($ch);

        return $data;
    }
}

function create_order_id() {
    return 'Z' . strval(time());
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api_key = 'YOUR API KEY';
    $sandbox = false;
    $webhook_url = 'YOUR WEBHOOK URL';

    $zeno_pay = new ZenoPay($api_key, $sandbox, $webhook_url);

    $order_id = create_order_id();
    $amount = $_POST['amount'];
    $phone_number = $_POST['phone_number'];
    $description = 'Payment for order #' . $order_id;

    try {
        $response = $zeno_pay->make_payment($amount, $order_id, $phone_number, $description);
        $message = 'Payment successful: ' . json_encode($response);
    } catch (Exception $e) {
        $message = 'Failed to make payment: ' . $e->getMessage();
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZenoPay Payment</title>
</head>
<body>
    <h1>Make a Payment</h1>
    <form method="POST">
        <label for="phone_number">Phone Number:</label>
        <input type="text" id="phone_number" name="phone_number" required>
        <br>
        <label for="amount">Amount to Pay:</label>
        <input type="number" id="amount" name="amount" required>
        <br>
        <button type="submit">Pay Now</button>
    </form>

    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
</body>
</html>
