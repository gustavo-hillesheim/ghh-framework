<?php
require_once(__DIR__ . '\test-component.php');
require_once(__DIR__ . '\..\register\javascript-register.php');
require_once(__DIR__ . '\..\register\style-register.php');
TestandoComponent::import();
?>

<html>
    <head>
        <script>
            <?= JavascriptRegister::getCode() ?>
            function logEvent(event) {
                console.log(event);
            }
        </script>
        <style>
            <?= StyleRegister::getStyle() ?>
        </style>
    </head>
    <body>
        <testando-component onteste="logEvent($event)"></testando-component>
    </body>
</html>
