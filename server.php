<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\App;
use Ratchet\WebSocket\WsServer;

require __DIR__ . '/vendor/autoload.php';

class Chat implements MessageComponentInterface {
    protected $clients = [];
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients[$conn->resourceId] = [
            'conn'            => $conn,
            'user_id'         => null,
            'conversation_id' => null,
        ];

        echo "Nueva conexión WS: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $client = $this->clients[$from->resourceId] ?? null;
        if (!$client) return;

        $data = json_decode($msg, true);
        if (!$data || !isset($data['type'])) return;

        // ── Init: registrar conversación y verificar acceso ──
        if ($data['type'] === 'init') {
            $conversation_id = (int) ($data['conversation_id'] ?? 0);
            $user_id         = (int) ($data['user_id'] ?? 0);
            if ($conversation_id <= 0 || $user_id <= 0) return;
            $res = mysqli_query(
                $this->db,
                "SELECT id FROM conversations
                 WHERE id = $conversation_id
                   AND (buyer_id = $user_id OR seller_id = $user_id)
                 LIMIT 1"
            );

            if (!$res || mysqli_num_rows($res) === 0) {
                echo "Acceso denegado — conv $conversation_id para user $user_id\n";
                $from->close();
                return;
            }

            $this->clients[$from->resourceId]['conversation_id'] = $conversation_id;
            $this->clients[$from->resourceId]['user_id'] = $user_id;

            echo "User $user_id se unió a conv $conversation_id en connection {$from->resourceId}\n";
            return;
        }

        // ── Message: guardar en BD y reenviar solo al receptor ──
        if ($data['type'] === 'message') {
            $conversation_id = $client['conversation_id'];
            $user_id         = $client['user_id'];
            $message         = isset($data['message']) ? trim($data['message']) : '';

            if (!$conversation_id || $message === '') return;
            if (mb_strlen($message) > 2000) return;

            // Ya no persistimos en BD aquí, save_message.php se encarga de eso.
            // Solo hacemos broadcast.

            // Broadcast solo a los OTROS clientes en la misma conversación
            $payload = json_encode([
                'type'      => 'message',
                'message'   => $message,
                'sender_id' => $user_id,
            ]);

            foreach ($this->clients as $id => $c) {
                if ($id !== $from->resourceId && $c['conversation_id'] == $conversation_id) {
                    $c['conn']->send($payload);
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

// Conexión a la BD
$db = mysqli_connect('localhost', 'root', '', 'tiendalocal');
if (!$db) {
    die("No se pudo conectar a la BD: " . mysqli_connect_error() . "\n");
}

$server = new App('localhost', 8080);
$server->route('/chat', new WsServer(new Chat($db)), ['*']);
echo "Servidor WebSocket corriendo en ws://localhost:8080/chat\n";
$server->run();

