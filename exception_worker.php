<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('tasks', false, true, false, false);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

// Only pull 1 message off the queue holy fuck
$channel->basic_qos(null, 1, null);
$channel->basic_consume('tasks', '', false, false, false, false, function ($msg) {
    $info = json_decode($msg->body, true);
    $data = $info['data'];
    try {
        echo " [x] Received ", $msg->body, "\n";
        throw new Exception("Error Processing Request", 1);
    } catch (Exception $e) {
        if (!isset($info['attempts'])) {
            $info['attempts'] = 0;
        }

        // Re-try the message
        if ($info['attempts'] < 5) {
            $info['attempts'] = $info['attempts'] + 1;
            $new_msg = new AMQPMessage(
                json_encode($info),
                array('delivery_mode' => 2) // make message persistent
            );
            $msg->delivery_info['channel']->basic_publish($new_msg, '', 'tasks');
        }
    }

    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
});

while (count($channel->callbacks)) {
    $channel->wait();
}
