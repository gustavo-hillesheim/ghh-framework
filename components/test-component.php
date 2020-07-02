<?php
require_once(__DIR__ . '\..\components\web-component.php');

class TestComponent extends WebComponent
{

    function template(): void {
    ?>
        <h1>teste</h1>
    <?php
    }

    function style(): void {
    ?>
        <style>
            h1 {
                color: blue;
                background-color: red;
            }
        </style>
    <?php
    }

    function script(): void {
    ?>
        <script>
            log() {
                console.log('oi');
            }
        </script>
    <?php
    }
}