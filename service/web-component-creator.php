<?php
require_once(__DIR__ . '\..\components\web-component.php');

class WebComponentCreator {

    static function createClass(WebComponent $component): string
    {
        $className = get_class($component);
        $fields = WebComponentCreator::getFields($component);
        ob_start();
?>
<script>
class <?= $className ?> extends AbstractWebComponent {

    /** Getters and Setters */
<?php
foreach ($fields as $field) {
    $name = $field->name;
    $kebabCasedName = kebabCase($name);
    ?>
    get <?= $name ?>() {
        return this.getAttribute('<?= $kebabCasedName ?>');
    }

    set <?= $name ?>(value) {
    <?php 
        if (gettype($component->$name) === 'boolean') {
    ?>
        if (value) {
            this.setAttribute('<?= $kebabCasedName ?>', value);
        } else {
            this.removeAttribute('<?= $kebabCasedName ?>');
        }
    <?php
        } else {
    ?>
        this.setAttribute('<?= $kebabCasedName ?>', value);
    <?php
        }
    ?>
    }
    <?php
}
?>

    constructor() {
        super();
    }

    _setDefaultValues() {    
<?php
foreach ($fields as $field) {
    $name = $field->name;
    if (isset($component->$name)) {
        $value = phpToJavascriptType($component->$name);
        ?>
        this.<?= $name ?> = <?= $value ?>;
        <?php
    }
}
?>
    }

    _compileStyle() {
        <?php WebComponentCreator::templateAttributes($fields) ?>
        return `<?= $component->getStyle() ?>`;
    }

    _compileTemplate() {
        <?php WebComponentCreator::templateAttributes($fields) ?>
        return `<?= $component->getTemplate() ?>`;
    }

    static get observedAttributes() {
        return [<?php
            foreach ($fields as $field) {
                $kebabCasedName = kebabCase($field->name);
                echo "'$kebabCasedName',";
            }
        ?>];
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

    private static function templateAttributes(array $fields): void {
        foreach ($fields as $field) {
                $name = $field->name;
                echo "const $name = this.$name;\n";
            }
    }

    static function createAbstract() {
        ob_start();
?>
<script>
class AbstractWebComponent extends HTMLElement {

    /** Lifecycle hooks names */
    _createLifecycleName = 'create';
    _beforeRenderLifecycleName = 'beforeRender';
    _afterRenderLifecycleName = 'afterRender';

    constructor() {
        super();
        this._onCreate();
        this.attachShadow({ mode: 'open' });
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
        this._setDefaultValues();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        let shouldRender = true;
        if (this.renderOnChanged) {
            shouldRender = this.renderOnChanged(name, oldValue, newValue);
        }
        if (shouldRender) {
            this.render();
        }
    }
}
</script>
<?php
        return removeSurroundingTag(trim(ob_get_clean()), 'script');
    }
}
