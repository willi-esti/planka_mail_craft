<?php

require 'vendor/autoload.php'; // Add this line

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use Planka\Bridge\PlankaClient;
use Planka\Bridge\TransportClients\Client;
use Planka\Bridge\Config;
use Dotenv\Dotenv;

if (isset($_GET['cardId'])) {
    $cardId = $_GET['cardId'];
} elseif (isset($argv[1])) {
    $cardId = $argv[1];
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No card ID provided. Exiting.']);
    exit(1);
}

function getInfosFromPlanka($planka, $cardId) {
    $task = $planka->getTask($taskId);
    $board = $planka->getBoard($task['boardId']);
    $column = $planka->getColumn($task['columnId']);
    $user = $planka->getUser($task['assigneeId']);

    return [
        'taskTitle' => $task['title'],
        'taskDescription' => $task['description'],
        'taskDueDate' => $task['dueDate'],
        'taskUrl' => $task['url'],
        'boardName' => $board['name'],
        'columnName' => $column['name'],
        'assigneeName' => $user['name'],
        'assigneeEmail' => $user['email']
    ];
}

// Load .env variables
echo __DIR__;
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$mail = new PHPMailer(true);

$config = new Config(
    user: $_ENV['PLANKA_USER'],
    password: $_ENV['PLANKA_PWD'],
    baseUri: $_ENV['PLANKA_HOST'],
    port: 443
);

$planka = new PlankaClient($config);
$planka->authenticate();

try {

    //Server settings
    $mail->isSMTP();
    $mail->Host       = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SMTP_USERNAME'];
    $mail->Password   = $_ENV['SMTP_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $_ENV['SMTP_PORT'];

    // Get task information from Planka
    $taskInfo = getInfosFromPlanka($planka, $cardId);

    //Recipients
    $mail->setFrom('sender@example.com', 'Sender Name');
    $mail->addAddress($taskInfo['assigneeEmail'], $taskInfo['assigneeName']);
    $mail->addReplyTo('sender@example.com', 'Sender Name');

    //Content
    $mail->isHTML(true);
    $mail->Subject = 'Assignation de Tâche';
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->Body    = `<!doctypehtml><html lang=fr><meta charset=UTF-8><meta content="width=device-width,initial-scale=1"name=viewport><title>Assignation de Tâche</title><style>body{font-family:Arial,sans-serif;background-color:#f4f4f4;margin:0;padding:0}.container{max-width:600px;margin:20px auto;background-color:#fff;border:1px solid #ddd;border-radius:8px;overflow:hidden}.header{background-color:#2c3e50;color:#ecf0f1;padding:20px;text-align:center}.header h1{margin:0;font-size:24px}.content{padding:20px}.content p{font-size:16px;margin:0 0 10px}.task-details{width:100%;margin-top:10px;border-collapse:collapse}.task-details td{padding:10px;border:1px solid #ddd}.task-details .label{background-color:#ecf0f1;font-weight:700}.button{background-color:#3498db;color:#fff;text-decoration:none;padding:10px 20px;border-radius:5px;display:inline-block;margin-top:20px}.footer{background-color:#ecf0f1;text-align:center;padding:10px;font-size:12px;color:#7f8c8d}</style><div class=container><div class=header><h1>Assignation de Tâche</h1></div><div class=content><p>Bonjour,<p>Une nouvelle tâche vous a été assignée sur <strong>Ergo</strong>. Veuillez trouver les détails ci-dessous :<table class=task-details><tr><td class=label>Titre de la Tâche :<td>{$taskInfo['taskTitle']}<tr><td class=label>Description :<td>{$taskInfo['taskDescription']}<tr><td class=label>Date d'Échéance :<td>{$taskInfo['taskDueDate']}</table><a class=button href="{$taskInfo['taskUrl']}">Voir la Tâche</a><p style=font-size:14px;margin-top:20px;color:#7f8c8d>Merci,<br>L'équipe Ergo</div><div class=footer>© [Année Courante] Ergo. Tous droits réservés.</div></div>`;

    //$mail->send();
    echo 'Email sent successfully.';
} catch (Exception $e) {
    echo "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
}