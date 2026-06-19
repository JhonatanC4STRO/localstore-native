[1] [2] conexion.php
https://github.com/JhonatanC4STRO/localstore-native/blob/main/src/config/conexion.php
[3] [4] [5] auth_login.php
https://github.com/JhonatanC4STRO/localstore-native/blob/main/src/controllers/auth_login.php
[6] auth_register.php
https://github.com/JhonatanC4STRO/localstore-native/blob/main/src/controllers/auth_register.php
[7] role_sync.php
https://github.com/JhonatanC4STRO/localstore-native/blob/main/src/config/role_sync.php
[8] [9] admins.php
https://github.com/JhonatanC4STRO/localstore-native/blob/main/src/api/admin/admins.php
[10] [11] [12] product_create_action.php
https://github.com/JhonatanC4STRO/localstore-native/blob/main/src/controllers/product_create_action.php
[13] product_edit_action.php
https://github.com/JhonatanC4STRO/localstore-native/blob/main/src/controllers/product_edit_action.php
[14] product_delete_action.php
https://github.com/JhonatanC4STRO/localstore-native/blob/main/src/controllers/product_delete_action.php
[15] eliminar_chat.php
https://github.com/JhonatanC4STRO/localstore-native/blob/main/src/api/chat/eliminar_chat.php
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
            $stmt = mysqli_prepare(
                $this->db,
                "SELECT id FROM conversations
                 WHERE id = ?
                   AND (buyer_id = ? OR seller_id = ?)
                 LIMIT 1"
            );

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "iii", $conversation_id, $user_id, $user_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                
                if (!$res || mysqli_num_rows($res) === 0) {
                    mysqli_stmt_close($stmt);
                    echo "Acceso denegado — conv $conversation_id para user $user_id\n";
                    $from->close();
                    return;
                }
                mysqli_stmt_close($stmt);
            } else {
                echo "Error de preparación de acceso a la conversación\n";
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

// Conexión a la BD dinámica desde variables de entorno de despliegue
require __DIR__ . '/src/config/conexion.php';
$db = $conn;

$ws_host = getenv('WS_HOST') ?: 'localhost';
$ws_port = (int)(getenv('WS_PORT') ?: 8080);

$server = new App($ws_host, $ws_port);
$server->route('/chat', new WsServer(new Chat($db)), ['*']);
echo "Servidor WebSocket corriendo en ws://{$ws_host}:{$ws_port}/chat\n";
$server->run();

