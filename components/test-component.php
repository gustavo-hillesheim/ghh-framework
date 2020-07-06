<?php
require_once(__DIR__ . '\..\components\web-component.php');

class TestandoComponent extends WebComponent {

    public $textLegal = 'teste legal';
    public $rotation = -45;
    public $hasValue = true;
    public $c = [
        "name" => "Gustavo",
        "hobby" => "programming"
    ];

    function template(): void {
    ?>
        <h1>${c && c.name} ${c && c.hobby}</h1>
        <h2>Outro texto</h2>
        ${hasValue ? '<h3>Eu tenho valor</h3>' : ''}
    <?php
    }

    function style(): void {
    ?>
        <style>
            h1 {
                transform: rotate(${rotation}deg);
            }
        </style>
    <?php
    }
}