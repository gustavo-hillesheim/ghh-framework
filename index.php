<?php
    require_once('./components/web-component.php');

    class TestComponent extends WebComponent {

        function template(): void {
        ?>
            <h1>teste</h1>
        <?php
        }

        function style(): void {
        ?>
        h1 {
            color: blue;
        }
        <?php
        }

        function script(): void {
        ?>
        function log() {
            console.log('oi');
        }
        log();
        <?php
        }
    }

    $Component = new TestComponent();
    $template = $Component->getTemplate();
    $style = $Component->getStyle();
    $script = $Component->getScript();
    echo $template;
    echo $style;
    echo $script;
?>