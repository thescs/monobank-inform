<?php
	ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// устанавливаем свой часовой пояс
date_default_timezone_set('Europe/Kiev');
// получаем входящие данные
$data = file_get_contents('php://input');
$decoded = json_decode($data, true);

// составляем и отправляем Push уведомления подписчикам из базы данных
function sendPush($ids)
{
	if (empty($ids)) return "No receive ids.";
	global $decoded;
	$url = 'https://fcm.googleapis.com/fcm/send';
	$YOUR_API_KEY = ''; // Server key
	$text = date("d-m-Y H:i:s", $decoded['data']['statementItem']['time'])."\n".$decoded['data']['statementItem']['description']."\nСумма: ".number_format(($decoded['data']['statementItem']['operationAmount']/100), 2, '.', '')."\nБаланс: ".number_format(($decoded['data']['statementItem']['balance']/100), 2, '.', '');
	$request_body = [
		'registration_ids' => $ids,
		'notification' => [
			'title' => 'Транзакция',
			'body' => $text,
			'icon' => 'https://i0.wp.com/www.banka.com.ua/wp-content/uploads/2021/01/mono-intl-ttx.jpg?fit=313%2C311&ssl=1',
			'click_action' => 'http://website.com/mono/?transaction_id', // TODO
		],
	];
	$fields = json_encode($request_body);
	
	$request_headers = [
		'Content-Type: application/json',
		'Authorization: key=' . $YOUR_API_KEY,
	];
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	$response = curl_exec($ch);
	curl_close($ch);
	
	return $response;
}
// отправляем подписчикам уведомление в Телеграм
function sendToTg($id)
{
	global $decoded;
	$text = "<i><b>Транзакция</b></i>\n".date("d-m-Y H:i:s", $decoded['data']['statementItem']['time'])."\n<i>".$decoded['data']['statementItem']['description']."</i>\nСумма: ".number_format(($decoded['data']['statementItem']['operationAmount']/100), 2, '.', '')."\nБаланс: ".number_format(($decoded['data']['statementItem']['balance']/100), 2, '.', '');
	$apiToken = ""; // bot token
	$data = [
      'chat_id' => $id,
	  'parse_mode' => 'HTML',
      'text' => $text
	  ];
	file_get_contents("https://api.telegram.org/bot$apiToken/sendMessage?" . http_build_query($data) );
	
	return 0;
}

// подключаемся к БД
$db = new mysqli("localhost", "mono", "", "");
// отладочные логи
file_put_contents("logs/mono_". date('d_m_Y h-i-s') .".log", print_r($decoded, true), FILE_APPEND);

// выбираем и отправляем пуши
$clients = $db->query("SELECT client_id FROM clients");
$ids = [];
while ($row = $clients->fetch_row()) {
    array_push($ids, $row[0]);
}
sendPush($ids);
//---------------------------

// выбираем и отправляем смс в телеграм
$users = $db->query('select id from user where is_activated=1');
while ($u = $users->fetch_row()){
	sendToTg($u[0]);
}
//-------------------------------------