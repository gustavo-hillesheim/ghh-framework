<?php
require_once(__DIR__ . '\..\components\web-component.php');
require_once(__DIR__ . '\..\service\style-register.php');
require_once(__DIR__ . '\..\utils\utils.php');

class WebComponentCreator {

    private static $typeConversionFunctions = [
        'boolean' => 'Boolean',
        'int' => 'parseInt',
        'integer' => 'parseInt',
        'float' => 'parseFloat',
        'double' => 'parseDouble',
        'number' => 'Number',
        'string' => 'String',
        'array' => 'JSON.parse',
        'Object' => 'JSON.parse',
        'null' => null,
        null => null
    ];

    static function createClass(WebComponent $component): string {
        $className = get_class($component);
        $fields = WebComponentCreator::getFields($component);
        foreach ($fields as $field) {
            $field->setAccessible(true);
        }

        return readOutput(function() use ($component, $className, $fields) { ?>
            <script>
            class <?= $className ?> extends AbstractWebComponent {

                constructor() {
                    super(<?=convertToJavascriptValue($component->_domMode)?>);
                }

                /** Properties / attributes accessors */
                <?php WebComponentCreator::defineProperties($component, $fields) ?>

                <?php WebComponentCreator::defaultValues($component, $fields) ?>

                /** Rendering */
                <?php WebComponentCreator::renderTemplateAndStyles($component, $fields) ?>

                <?php WebComponentCreator::observedAttributes($fields) ?>

                /** Custom functionalities */
                <?= $component->getScript() ?>
            }
            </script>
            <?php }, TagMode::REMOVE_SURROUNDING, 'script');
    }

    private static function renderTemplateAndStyles(WebComponent $component, array $fields): void {
        WebComponentCreator::stringCompiler($fields);
        WebComponentCreator::renderStyles($component);
        WebComponentCreator::renderTemplate($component);
    }

    private static function stringCompiler(array $fields): void {
        echo "_compile(template) {";
        echo WebComponentCreator::templateAttributes($fields);
        ?>
            var keys = Object.keys(templateAttributes),
            fn = new Function(...keys, 'return `' + template.replace(/`/g, '\\`') + '`');
            return fn(...keys.map(key => templateAttributes[key]));
        <?php
        echo "}";
    }

    private static function renderStyles(WebComponent $component): void {
        $styles = $component->getStyle();
        $styles = preg_replace('/\$/', '\\\\$', $styles);

        if ($component->_domMode == DomMode::SHADOW) {
            echo "_compileStyle() {";
            echo "  return this._compile(`$styles`);";
            echo "}";
        } else {
            echo "_compileStyle() { return ''; }";
            StyleRegister::addStyle($styles, $component->getTagName());
        }
    }

    private static function renderTemplate(WebComponent $component): void {
        $template = $component->getTemplate();
        $template = preg_replace('/\$/', '\\\\$', $template);
        echo "_compileTemplate() {";
        echo "  return this._compile(`$template`);";
        echo "}";
    }

    private static function defineProperties(WebComponent $component, array $fields): void {
        foreach ($fields as $field) {
            $fieldName = $field->name;
            $kebabCasedName = kebabCase($fieldName);
            $value = WebComponentCreator::getValue($field, $component);
            $type = WebComponentCreator::getType($field, $value);

            if (startsWith($fieldName, '_')) {
                WebComponentCreator::createProperty($fieldName, $value, $type);
            } else {
                WebComponentCreator::createAttributeGetter($fieldName, $kebabCasedName, $type);
                WebComponentCreator::createAttributeSetter($fieldName, $kebabCasedName, $type);
            }
        }
    }

    private static function getValue(ReflectionProperty $field, WebComponent $component) {
        if ($field->isInitialized($component)) {
            return $field->getValue($component);
        }
        return null;
    }

    private static function getType(ReflectionProperty $field, $value) {
        if (isset($value)) {
            return gettype($value);
        } else {
            $fieldType = $field->getType();
            if (isset($fieldType)) {
                return $fieldType->getName();
            }
            return 'null';
        }
    }

    private static function createProperty(string $fieldName, $value, string $type): void {
        if (!isset($value)) {
            $value = 'null';
        } else {
            $value = convertToJavascriptValue($value);
        }
        echo "$fieldName = $value;";
    }

    private static function createAttributeGetter(string $fieldName, string $kebabCasedName, string $type): void {
        
        $typeConversionFunction = WebComponentCreator::getTypeConverter($type);
        echo "get $fieldName() {";
        echo "  return ";
        if (WebComponentCreator::isObject($type)) {
            echo "JSON.parse(atob(this.getAttribute('$kebabCasedName')));";

        } else {
            if (isset($typeConversionFunction)) {
                echo "$typeConversionFunction(";
            }
            echo "this.getAttribute('$kebabCasedName')";
            if (isset($typeConversionFunction)) {
                echo ")";
            }
            echo ";";
        }
        echo "}";
    }

    private static function getTypeConverter(string $type) {
        if (array_key_exists($type, WebComponentCreator::$typeConversionFunctions)) {
            return WebComponentCreator::$typeConversionFunctions[$type];
        }
        return 'JSON.parse';
    }

    private static function createAttributeSetter(string $fieldName, string $kebabCasedName, string $type): void {
        echo "set $fieldName(value) {";
        if ($type === 'boolean') {

            echo "if (value) {";
            echo "  this.setAttribute('$kebabCasedName', value);";
            echo "} else {";
            echo "  this.removeAttribute('$kebabCasedName');";
            echo "}";

        } else {
            if (WebComponentCreator::isObject($type)) {
                echo "this.setAttribute('$kebabCasedName', btoa(JSON.stringify(value)));";
            } else {
                echo "this.setAttribute('$kebabCasedName', value);";
            }
        }
        echo "}";
    }

    private static function isObject(string $type): bool {
        return !array_key_exists($type, WebComponentCreator::$typeConversionFunctions) || $type == 'array' || $type == 'object';
    }

    private static function observedAttributes(array $fields): void {
        echo "static get observedAttributes() {";
        echo "  return [";
        foreach ($fields as $field) {
            $kebabCasedName = kebabCase($field->name);
            echo "'$kebabCasedName',";
        }
        echo "]; }";
    }

    private static function defaultValues(WebComponent $component, array $fields): void {
        echo "_setDefaultValues() {";
        foreach ($fields as $field) {
            $name = $field->name;
            
            if (!startsWith($name, '_') && isset($component->$name)) {
                $value = convertToJavascriptValue($component->$name);
                $encodedValue = json_encode($value);
                echo "  this.$name = $encodedValue;";
            }
        } 
        echo "}";
    }

    static function getFields(WebComponent $component) {
        $reflect = new ReflectionClass($component);
        return $reflect->getProperties();
    }

    private static function templateAttributes(array $fields): void {
        echo "const templateAttributes = { ";
        foreach ($fields as $field) {
            $name = $field->name;
            echo "'$name': this.$name, ";
        }
        echo "};";
    }
}
