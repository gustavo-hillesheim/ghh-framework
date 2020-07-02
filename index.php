<?php
require_once(__DIR__ . '\components\test-component.php');
require_once(__DIR__ . '\service\javascript-register.php');
TestandoComponent::import();
?>

<html>
    <head>
        <script>
            <?= JavascriptRegister::getCode() ?>
        </script>
    </head>
    <body>
        <testando-component textLegal="Testando"></testando-component>
    </body>
</html>
