<?php

class StyleRegister {

    private static $style = "";
    private static $cssSelectorRegex = "/\s*(.*{[^}]*})/";

    static function addStyle(string $newStyle, string $prefix = ''): void {
        $newStyle = removeSurroundingTag($newStyle, 'style');
        if (isset($prefix) && trim($prefix) != '') {
            $newStyle = preg_replace(StyleRegister::$cssSelectorRegex, "$prefix $1\n", $newStyle);
        }
        StyleRegister::$style .= "\n$newStyle";
    }

    static function getStyle(): string {
        return StyleRegister::$style;
    }
}