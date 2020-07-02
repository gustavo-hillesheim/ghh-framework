<?php
require_once(__DIR__ . '\..\components\web-component.php');

class WebComponentCreator
{

    static function createClass(WebComponent $component): string
    {
        $className = get_class($component);
        ob_start();
?>
<script>
class <?php echo $className ?> extends HTMLElement {

    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this.render();
    }

    render() {
        this.shadowRoot.innerHTML = `
            ${this._compileStyle()}
            ${this._compileTemplate()}
        `;
    }

    _compileStyle() {
        return `<?php echo $component->getStyle() ?>`;
    }

    _compileTemplate() {
        return `<?php echo $component->getTemplate() ?>`;
    }

    <?php echo $component->getScript() ?>
}
</script>
<?php
        $javascriptClass = trim(ob_get_clean());
        return removeSurroundingTag($javascriptClass, 'script');
    }
}
