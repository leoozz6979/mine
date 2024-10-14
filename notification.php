<?php
    $host = getenv('DB_HOST') ?: '34.138.176.84';
    $user = getenv('DB_USER') ?: 'leopica';
    $password = getenv('DB_PASSWORD') ?: 'Leo12345!';
    $db = getenv('DB_NAME') ?: 'leozada';
    $port = getenv('DB_PORT') ?: '19038';
    $accessToken = getenv('ACCESS_TOKEN') ?: 'APP_USR-891104909929153-100422-44c8d5ad01e0b6c29a9c331bfe0c99da-558785318';

    error_log("Início do processamento da requisição de notificação.");

    // Verifica se a requisição é POST
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        http_response_code(400);
        error_log("Erro: Método de requisição não suportado. Método recebido: " . $_SERVER["REQUEST_METHOD"]);
        echo "Método de requisição não suportado. Utilize POST.";
        return;
    }

// Verifica se os parâmetros 'id' e 'topic' ou 'data_id' e 'type' estão presentes
if (isset($_GET['data_id']) && isset($_GET['type'])) {
    $id = $_GET['data_id'];
    $topic = $_GET['type'];
} elseif (isset($_GET['id']) && isset($_GET['topic'])) {
    $id = $_GET['id'];
    $topic = $_GET['topic'];
} else {
    http_response_code(400);
    error_log("Erro: Parâmetros 'id' ou 'topic' ausentes. Parâmetros recebidos: " . print_r($_GET, true));
    echo "Parâmetros 'id' ou 'topic' ausentes.";
    return;
}

// Verifica se o tópico é de pagamento
if ($topic != "payment") {
    http_response_code(400);
    error_log("Erro: Tipo inválido. Tipo recebido: " . $topic);
    echo "Este endpoint lida apenas com notificações de pagamento.";
    return;
}

    $id = $_GET['id'];

    // Faz a requisição para a API do Mercado Pago para obter os detalhes do pagamento
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.mercadopago.com/v1/payments/' . $id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $accessToken
        ),
    ));

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($curlError) {
        http_response_code(500);
        error_log("Erro ao fazer requisição para o Mercado Pago: " . $curlError);
        echo "Erro ao se comunicar com o Mercado Pago.";
        return;
    }

    $payment = json_decode($response, true);

    if (!$payment || !isset($payment["status"])) {
        http_response_code(500);
        error_log("Erro: Resposta inválida do Mercado Pago. Resposta recebida: " . $response);
        echo "Resposta inválida do Mercado Pago.";
        return;
    }

    error_log("Status do pagamento: " . $payment["status"]);

    if ($payment["status"] === "approved") {
        // Conecta ao banco de dados MySQL
        $conn = new mysqli($host, $user, $password, $db, $port);

        if ($conn->connect_error) {
            http_response_code(500);
            error_log("Erro ao conectar ao banco de dados: " . $conn->connect_error);
            echo "Erro ao conectar ao banco de dados.";
            return;
        }

        // Pega o jogador relacionado ao pagamento
        $player = $payment["external_reference"];

        // Insere um registro na tabela de pendências
        $insertSql = "INSERT INTO autopix_pendings (id, player) VALUES (?, ?)";
        
        $stmt = $conn->prepare($insertSql);
        if (!$stmt) {
            http_response_code(500);
            error_log("Erro ao preparar statement: " . $conn->error);
            echo "Erro ao preparar inserção no banco de dados.";
            $conn->close();
            return;
        }

        $stmt->bind_param("ss", $id, $player);

        if ($stmt->execute()) {
            http_response_code(201);
            error_log("Pagamento aprovado e salvo com sucesso para o jogador: " . $player);
            echo "Pagamento aprovado e salvo com sucesso.";
        } else {
            http_response_code(500);
            error_log("Erro ao executar inserção no banco de dados: " . $stmt->error);
            echo "Erro ao salvar informações de pagamento.";
        }

        $stmt->close();
        $conn->close();
    } else {
        http_response_code(400);
        error_log("Pagamento não aprovado. Status recebido: " . $payment["status"]);
        echo "Pagamento não aprovado.";
    }
?>
