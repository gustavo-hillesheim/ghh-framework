<?php
require_once(__DIR__ . '\..\components\web-component.php');
require_once(__DIR__ . '\..\service\web-component-creator.php');
require_once(__DIR__ . '\..\service\javascript-register.php');

class WebComponentRegister {

    static function register(WebComponent $component): void {        
        $tagName = $component->getTagName();
        $javascriptClass = WebComponentCreator::createClass($component);
        $webComponentDefinition = "customElements.define('$tagName', $javascriptClass);";
        JavascriptRegister::addCode($webComponentDefinition);
    }
}
