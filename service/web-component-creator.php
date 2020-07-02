<?php
require_once(__DIR__ . '\..\components\web-component.php');

class WebComponentCreator
{

    static function createClass(WebComponent $component): string
    {
        $className = get_class($component);
        $fields = WebComponentCreator::getFields($component);
        ob_start();
?>
<script>
class <?= $className ?> extends HTMLElement {

    _createLifecycleName = 'create';
    _beforeRenderLifecycleName = 'beforeRender';
    _afterRenderLifecycleName = 'afterRender';

    /** Getters and Setters */
<?php
foreach ($fields as $value) {
    $name = $value->name;
    ?>
    get <?= $name ?>() {
        return this.getAttribute('<?= $name ?>');
    }

    set <?= $name ?>(value) {
        this.setAttribute('<?= $name ?>', value);
    }
    <?php
}
?>

    constructor() {
        super();
        this._onCreate();
        this.attachShadow({ mode: 'open' });
        this.render();
    }

    /** Rendering logic */
    render() {
        this._onBeforeRender();
        this.shadowRoot.innerHTML = `
            ${this._compileStyle()}
            ${this._compileTemplate()}
        `;
        this._onAfterRender();
    }

    _compileStyle() {
        return `<?= $component->getStyle() ?>`;
    }

    _compileTemplate() {
        <?php
            foreach ($fields as $value) {
                $name = $value->name;
                echo "const $name = this.$name;\n";
            }
        ?>
        return `<?= $component->getTemplate() ?>`;
    }

    /** Lifecycle hooks */
    _onCreate() {
        this._runLifecycle(this._createLifecycleName);
    }

    _onBeforeRender() {
        this._runLifecycle(this._beforeRenderLifecycleName);
    }

    _onAfterRender() {
        this._runLifecycle(this._afterRenderLifecycleName);
    }

    _runLifecycle(lifecycle) {
        if (this[lifecycle]) {
            this[lifecycle]();
        }
    }

    /** Web component hooks */
    connectedCallback() {
        this.render();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        console.log(name, oldValue, newValue);
        let shouldRender = true;
        if (this.renderOnChanged) {
            shouldRender = this.renderOnChanged(name, oldValue, newValue);
        }
        if (shouldRender) {
            this.render();
        }
    }

    /** Custom functionalities */
    <?= $component->getScript() ?>
}
</script>
<?php
        $javascriptClass = trim(ob_get_clean());
        return removeSurroundingTag($javascriptClass, 'script');
    }

    static function getFields(WebComponent $component) {
        $reflect = new ReflectionClass($component);
        return $reflect->getProperties();
    }
}
