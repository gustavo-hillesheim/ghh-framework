<?php
class JavascriptRegister {

    private static $code = "";

    static function addCode(string $newCode): void {
        JavascriptRegister::$code .= "\n$newCode";
    }

    static function getCode(): string {
        return JavascriptRegister::$code;
    }
}