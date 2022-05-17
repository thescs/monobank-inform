<?php
	// скрипт добавляет айди клиента в БД для пуш рассылки
	if(isset($_POST['token']))
	{
		$db = new mysqli("localhost", "mono", "", "");
		$db->query("INSERT INTO `mono`.`clients` (`client_id`) VALUES ('". $_POST['token'] ."')");
		echo $_POST['token'];
	}