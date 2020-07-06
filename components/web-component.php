<?php
require_once(__DIR__ . '\..\utils\utils.php');
require_once(__DIR__ . '\..\service\web-component-register.php');

class DomMode {
    const SHADOW = 'shadow';
    const NORMAL = 'normal';
}

abstract class WebComponent
{

    public $_domMode = DomMode::SHADOW;

    function getTemplate(): string {
        return readOutput(fn() => $this->template());
    }

    abstract function template(): void;

    function getStyle(): string {
        return readOutput(fn() => $this->style(), TagMode::ADD_SURROUNDING, 'style');
    }

    function style(): void {}

    function getScript(): string {
        return readOutput(fn() => $this->script(), TagMode::REMOVE_SURROUNDING, 'script');
    }

    function script(): void {}

    function getTagName(): string {
        $className = get_called_class();
        return kebabCase($className);
    }

    static function import(): void {
        $class = get_called_class();
        WebComponentRegister::register(new $class);
    }

    function importInstance(): void {
        WebComponentRegister::register($this);
    }
}
