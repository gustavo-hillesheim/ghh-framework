<?php
require_once(__DIR__ . '\..\components\web-component.php');
require_once(__DIR__ . '\..\service\web-component-creator.php');

class WebComponentRegister
{

    static function register(WebComponent $component): void
    {
        $tagName = $component->getTagName();
        $className = get_class($component);
        ?>
<script>
<?php echo WebComponentCreator::createClass($component) ?>

customElements.define('<?php echo $tagName ?>', <?php echo $className ?>);
</script>
        <?php
    }
}
