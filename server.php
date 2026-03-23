<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\App;

require __DIR__ . '/vendor/autoload.php';

class Chat implements MessageComponentInterface {
    protected $clients = [];

    public function onOpen(ConnectionInterface $conn) {
        $this->clients[$conn->resourceId] = [
            'conn' => $conn,
            'user_id' => null,
            'conversation_id' => null
        ];

        echo "Nueva conexión: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        // Inicializar usuario
        if ($data['type'] === 'init') {
            $this->clients[$from->resourceId]['user_id'] = $data['user_id'];
            $this->clients[$from->resourceId]['conversation_id'] = $data['conversation_id'];
            return;
        }

        // Enviar mensaje
        if ($data['type'] === 'message') {

            foreach ($this->clients as $client) {
                if ($client['conversation_id'] == $data['conversation_id']) {
                    $client['conn']->send(json_encode([
                        'message' => $data['message'],
                        'sender_id' => $data['sender_id']
                    ]));
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        unset($this->clients[$conn->resourceId]);
        echo "Conexión cerrada: {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

// 🚀 Servidor
$server = new App('localhost', 8080);
$server->route('/chat', new Chat, ['*']);
echo "Servidor WebSocket corriendo en ws://localhost:8080/chat\n";
$server->run();