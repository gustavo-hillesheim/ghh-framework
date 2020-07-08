<?php
require_once(__DIR__ . '\..\web-component\web-component.php');
require_once(__DIR__ . '\..\web-component\web-component-creator.php');
require_once(__DIR__ . '\javascript-register.php');

class WebComponentRegister {

    static function register(WebComponent $component): void {        
        $tagName = $component->getTagName();
        $javascriptClass = WebComponentCreator::createClass($component);
        $webComponentDefinition = "customElements.define('$tagName', $javascriptClass);";
        JavascriptRegister::addCode($webComponentDefinition);
    }
}
