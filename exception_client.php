<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('tasks', false, true, false, false);

$message_data = implode(' ', array_slice($argv, 1));
$msg = new AMQPMessage(
    json_encode(array('data' => $message_data)),
    array('delivery_mode' => 2) // make message persistent
);

$channel->basic_publish($msg, '', 'tasks');

echo " [x] Sent ", $data, "\n";

$channel->close();
$connection->close();
