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
            return '<style>'.trim(ob_get_clean()).'</style>';
        }
        
        function style(): void {}

        function getScript(): string {
            ob_start();
            $this->script();
            return '<script>'.trim(ob_get_clean()).'</script>';
        }

        function script(): void {}
    }
?>