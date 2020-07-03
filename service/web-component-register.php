<?php
require_once(__DIR__ . '\..\components\web-component.php');
require_once(__DIR__ . '\..\service\web-component-creator.php');
require_once(__DIR__ . '\..\service\javascript-register.php');

class WebComponentRegister
{

    private static bool $createdAbstractWebComponent = false;

    static function register(WebComponent $component): void
    {
        if (!WebComponentRegister::$createdAbstractWebComponent) {

            $abstractWebComponentClass = WebComponentCreator::createAbstract();
            JavascriptRegister::addCode($abstractWebComponentClass);
            WebComponentRegister::$createdAbstractWebComponent = true;
        }
        
        $tagName = $component->getTagName();
        $javascriptClass = WebComponentCreator::createClass($component);
        $webComponentDefinition = "customElements.define('$tagName', $javascriptClass)";
        JavascriptRegister::addCode($webComponentDefinition);
    }
}
