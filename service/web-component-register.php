<?php
require_once(__DIR__ . '\..\components\web-component.php');
require_once(__DIR__ . '\..\service\web-component-creator.php');
require_once(__DIR__ . '\..\service\javascript-register.php');

class WebComponentRegister
{

    static function register(WebComponent $component): void
    {
        $tagName = $component->getTagName();
        $className = get_class($component);
        $javascriptClass = WebComponentCreator::createClass($component);
        $webComponentDefinition = "customElements.define('$tagName', $className)";
        JavascriptRegister::addCode($javascriptClass);
        JavascriptRegister::addCode($webComponentDefinition);
    }
}
