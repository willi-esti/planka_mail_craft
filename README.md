# Send Mail Script

This script sends an email notification when a task is assigned in Planka. It retrieves task details from Planka and sends an email to the assignee using PHPMailer.

## Prerequisites

- PHP 7.4 or higher
- Composer
- Planka API credentials
- SMTP server credentials

## Installation

1. Clone the repository or download the script.
2. Install dependencies using Composer:
    ```sh
    composer install
    ```
3. Create a `.env` file in the same directory as `send_mail.php` and add the following variables:
    ```
    PLANKA_USER=your_planka_username
    PLANKA_PWD=your_planka_password
    PLANKA_HOST=your_planka_host
    PLANKA_PORT=your_planka_port
    SMTP_HOST=your_smtp_host
    SMTP_USERNAME=your_smtp_username
    SMTP_PASSWORD=your_smtp_password
    SMTP_PORT=your_smtp_port
    ```

## Usage

### Via Web

1. Ensure your web server is running.
2. Access the script via a web browser with the following URL format:
    ```
    http://yourserver/send_mail.php?cardId=your_card_id&userId=your_user_id
    ```

### Via Command Line

1. Open a terminal.
2. Run the script with the following command:
    ```sh
    php send_mail.php your_card_id your_user_id
    ```

## License

This project is licensed under the MIT License.
