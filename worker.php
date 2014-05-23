<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPConnection;

$connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('tasks', false, true, false, false);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

// Only pull 1 message off the queue holy fuck
$channel->basic_qos(null, 1, null);
$channel->basic_consume('tasks', '', false, false, false, false, function ($msg) {
    echo " [x] Received ", $msg->body, "\n";
    sleep(substr_count($msg->body, '.'));
    echo " [x] Done", "\n";
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
});

while (count($channel->callbacks)) {
    $channel->wait();
}
