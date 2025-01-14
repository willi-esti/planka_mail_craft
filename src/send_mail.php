<?php

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use Planka\Bridge\PlankaClient;
use Planka\Bridge\TransportClients\Client;
use Planka\Bridge\Config;
use Dotenv\Dotenv;

if (isset($_GET['cardId']) && isset($_GET['userId'])) {
    $cardId = $_GET['cardId'];
    $userId = $_GET['userId'];
} elseif (isset($argv[1]) && isset($argv[2])) {
    $cardId = $argv[1];
    $userId = $argv[2];
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No card ID or user ID provided. Exiting.']);
    exit(1);
}

function getMembersToSend($planka, $cardId, $members) {
    $comments = $planka->cardAction->getActions($cardId)->items;
    foreach ($comments as $comment) {
        if ($comment->type->name == 'COMMENT_CARD')
        {
            foreach ($members as $member) {
                if (strpos($comment->dataText, $member) !== false) {
                    // Remove the member from the list of members to send the email to
                    $members = array_diff($members, [$member]);
                }
            }
        }
    }
    return $members;
}

function getInfo($planka, $cardId) {
    $card = $planka->card->get($cardId);
    $comments = $planka->cardAction->getActions($cardId);

    $cardTitle = $card->name;
    $board = $planka->board->get($card->boardId);
    $user = $planka->user->get($card->creatorUserId);

    $cardDescription = $card->description;
    $cardDueDate = $card->dueDate;
    $cardUrl = $_ENV['PLANKA_HOST'] .  ':' . $_ENV['PLANKA_PORT'] . '/card/' . $card->id;
    $assigneeName = $user->name;
    $assigneeEmail = $user->email;

    $members = getMembers($planka, $cardId);
    $membersToSend = getMembersToSend($planka, $cardId, $members);

    // get the memebers removed from the list
    $membersRemoved = array_diff($members, $membersToSend);

    
    return [
        'cardTitle' => $cardTitle,
        'cardDescription' => $cardDescription,
        'cardDueDate' => $cardDueDate,
        'cardUrl' => $cardUrl,
        'assigneeName' => $assigneeName,
        'assigneeEmail' => $assigneeEmail,
        'members' => $membersToSend,
        'membersRemoved' => $membersRemoved
    ];
}

function getMembers($planka, $cardId) {
    $card = $planka->card->get($cardId);
    $cardMemberships = $card->included->cardMemberships;
    $members = [];
    foreach ($cardMemberships as $cardMembership) {
        $member = $planka->user->get($cardMembership->userId)->email;
        $members[] = $member;
    }

    return $members;
}

function sendMail($data) {
    $mail = new PHPMailer(true);

    try {
        return true;
        //Server settings
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME'];
        $mail->Password   = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'];

        //Recipients
        $mail->setFrom($_ENV['SMTP_USERNAME'], 'Ergo');
        $mail->addAddress($data['assigneeEmail'], $data['assigneeName']);
        $mail->addReplyTo($_ENV['SMTP_USERNAME'], 'Ergo');

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Assignation de Tâche';
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Body    = $mail->Body = "
        <!doctype html>
        <html lang='fr'>
        <meta charset='UTF-8'>
        <meta content='width=device-width,initial-scale=1' name='viewport'>
        <title>Assignation de Tâche</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 20px auto; background-color: #fff; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
            .header { background-color: #2c3e50; color: #ecf0f1; padding: 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { padding: 20px; }
            .content p { font-size: 16px; margin: 0 0 10px; }
            .card-details { width: 100%; margin-top: 10px; border-collapse: collapse; }
            .card-details td { padding: 10px; border: 1px solid #ddd; }
            .card-details .label { background-color: #ecf0f1; font-weight: 700; }
            .button { background-color: #3498db; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 5px; display: inline-block; margin-top: 20px; }
            .footer { background-color: #ecf0f1; text-align: center; padding: 10px; font-size: 12px; color: #7f8c8d; }
        </style>
        <div class='container'>
            <div class='header'>
                <h1>Assignation de Tâche</h1>
            </div>
            <div class='content'>
                <p>Bonjour,</p>
                <p>Une nouvelle tâche vous a été assignée sur <strong>Ergo</strong>. Veuillez trouver les détails ci-dessous :</p>
                <table class='card-details'>
                    <tr><td class='label'>Titre de la Tâche :</td><td>{$cardInfo['cardTitle']}</td></tr>
                    <tr><td class='label'>Description :</td><td>{$cardInfo['cardDescription']}</td></tr>
                    <tr><td class='label'>Date d'Échéance :</td><td>{$cardInfo['cardDueDate']}</td></tr>
                </table>
                <a class='button' href='{$cardInfo['cardUrl']}'>Voir la Tâche</a>
                <p style='font-size: 14px; margin-top: 20px; color: #7f8c8d;'>Merci,<br>L'équipe Ergo</p>
            </div>
            <div class='footer'>© " . date('Y') . " Ergo. Tous droits réservés.</div>
        </div>
        </html>
        ";;

        //$mail->send();
        return true;
    } catch (Exception $e) {
        echo "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}


// Load .env variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$mail = new PHPMailer(true);

$config = new Config(
    user: $_ENV['PLANKA_USER'],
    password: $_ENV['PLANKA_PWD'],
    baseUri: $_ENV['PLANKA_HOST'],
    port: $_ENV['PLANKA_PORT']
);

$planka = new PlankaClient($config);
$planka->authenticate();

$infos = getInfo($planka, $cardId);
//checkComment($planka, $cardId, $infos['assigneeEmail']);

if (!empty($infos['membersRemoved']) && empty($infos['members'])) {
    $planka->comment->add($cardId, "Tous les membres ont déjà reçu un email. Supprimé des message de notification contenant les emails des membres pour les renvoyé un email.");
    exit;
} else if (empty($infos['members'])) {
    $planka->comment->add($cardId, "Ils n'y a pas de membres à qui envoyer l'email.");
    exit;
}

foreach ($infos['members'] as $member) {
    if (sendMail($infos))
    {
        $planka->comment->add($cardId, 'Email envoyé à ' . $member);
    }
    else
    {
        $planka->comment->add($cardId, 'Erreur lors de l\'envoi de l\'email à ' . $member);
    }
}
exit;

