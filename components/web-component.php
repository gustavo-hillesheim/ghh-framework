<?php
require_once(__DIR__ . '\..\utils\utils.php');
require_once(__DIR__ . '\..\service\web-component-register.php');

abstract class WebComponent
{

    function getTemplate(): string {
        ob_start();
        $this->template();
        return trim(ob_get_clean());
    }

    abstract function template(): void;

    function getStyle(): string {
        ob_start();
        $this->style();
        $style = trim(ob_get_clean());
        return addSurroundingTag($style, 'style');
    }

    function style(): void {}

    function getScript(): string {
        ob_start();
        $this->script();
        $script = trim(ob_get_clean());
        return removeSurroundingTag($script, 'script');
    }

    function script(): void {}

    function getTagName(): string {
        $className = get_called_class();
        return kebab_case($className);
    }

    static function import(): void {
        $class = get_called_class();
        WebComponentRegister::register(new $class);
    }

    function importInstance(): void {
        WebComponentRegister::register($this);
    }
}
