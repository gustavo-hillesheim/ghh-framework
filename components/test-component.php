<?php
require_once(__DIR__ . '\..\components\web-component.php');

class TestandoComponent extends WebComponent {

    private $textLegal;

    function template(): void {
    ?>
        <h1>${textLegal}</h1>
        <h2>Outro texto</h2>
    <?php
    }

    function style(): void {
    ?>
        <style>
            h1 {
                color: blue;
            }
        </style>
    <?php
    }
}