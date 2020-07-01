<?php

    abstract class WebComponent {

        function getTemplate(): string {
            ob_start();
            $this->template();
            return trim(ob_get_clean());
        }

        abstract function template(): void;

        function getStyle(): string {
            ob_start();
            $this->style();
            return "<style>\n".trim(ob_get_clean())."\n</style>";
        }
        
        function style(): void {}

        function getScript(): string {
            ob_start();
            $this->script();
            return "<script>\n".trim(ob_get_clean())."\n</script>";
        }

        function script(): void {}
    }
?>