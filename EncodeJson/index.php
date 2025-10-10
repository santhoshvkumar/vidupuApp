<?php
require '../_common/EncryptDecrypt.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $text = $_POST["text"] ?? "";
    $action = $_POST["action"] ?? "";
    $type = $_POST["type"] ?? "base64";
    
    if ($action === "encode") {
            $result = encryptDataFunc($text);
            // echo json_encode(["result" => $result], JSON_UNESCAPED_UNICODE);
        // $result = ($type === "base64") ? base64_encode($text) : htmlentities($text, ENT_QUOTES, 'UTF-8');

    } elseif ($action === "decode") {
        $result = json_encode(decryptDataFunc($text),JSON_UNESCAPED_UNICODE);
        // $result = ($type === "base64") ? base64_decode($text) : html_entity_decode($text, ENT_QUOTES);

    } else {
        $result = "Invalid Action!";
    }
} else {
    $text = "";
    $result = "";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encode & Decode Tool</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin: 40px; }
        textarea { width: 80%; height: 100px; margin-bottom: 10px; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>

    <h2>Encode & Decode Tool</h2>

    <form method="post">
        <textarea name="text" placeholder="Enter text..." required><?= htmlspecialchars($text) ?></textarea><br>
        
        <!-- <label>
            <input type="radio" name="type" value="base64" <?= ($type === "base64") ? "checked" : "" ?>> Base64
        </label>
        <label>
            <input type="radio" name="type" value="html" <?= ($type === "html") ? "checked" : "" ?>> HTML Entities
        </label>
        <br><br> -->

        <button type="submit" name="action" value="encode">Encode</button>
        <button type="submit" name="action" value="decode">Decode</button>
    </form>

    <h3>Result:</h3>
    <textarea readonly><?= htmlspecialchars($result) ?></textarea>

</body>
</html>
