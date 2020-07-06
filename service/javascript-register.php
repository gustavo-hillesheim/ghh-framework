<?php
class JavascriptRegister {

    private static $code = "";

    static function setDefaultCode() {
        JavascriptRegister::$code = file_get_contents(__DIR__ . '\..\service\default-javascript-code.js');
    }

    static function addCode(string $newCode): void {
        JavascriptRegister::$code .= "\n$newCode";
    }

    static function getCode(): string {
        return JavascriptRegister::$code;
    }
}
JavascriptRegister::setDefaultCode();