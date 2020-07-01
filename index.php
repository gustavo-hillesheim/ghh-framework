<?php
    require_once('./components/web-component.php');

    class TestComponent extends WebComponent {

        function template(): void {
        ?>
            <h1>teste</h1>
        <?php
        }
    }

    $Component = new TestComponent();
    $template = $Component->getTemplate();
    echo $template;
?>