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
        'null' => null
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
        WebComponentCreator::renderStyles($component, $fields);
        WebComponentCreator::renderTemplate($component, $fields);
    }

    private static function renderStyles(WebComponent $component, array $fields): void {
        $styles = $component->getStyle();
        if ($component->_domMode == DomMode::SHADOW) {
            echo "\t_compileStyle() {\n";
            echo WebComponentCreator::templateAttributes($fields);
            echo "\t\treturn `$styles`;\n";
            echo "\t}\n";
        } else {
            echo "\t_compileStyle() { return ''; }";
            StyleRegister::addStyle($styles, $component->getTagName());
        }
    }

    private static function renderTemplate(WebComponent $component, array $fields): void {
        $template = $component->getTemplate();
        echo "\t_compileTemplate() {\n";
        echo WebComponentCreator::templateAttributes($fields);
        echo "\t\treturn `$template`;\n";
        echo "\t}\n";
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
                return $fieldType;
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
        echo "\t$fieldName = $value;";
    }

    private static function createAttributeGetter(string $fieldName, string $kebabCasedName, string $type): void {
        $typeConversionFunction = WebComponentCreator::$typeConversionFunctions[$type];
        echo "\tget $fieldName() {\n";
        echo "\t\treturn ";
        if (isset($typeConversionFunction)) {
            echo "$typeConversionFunction(";
        }
        echo "this.getAttribute('$kebabCasedName')";
        if (isset($typeConversionFunction)) {
            echo ")";
        }
        echo ";\n";
        echo "\t}\n";
    }

    private static function createAttributeSetter(string $fieldName, string $kebabCasedName, string $type): void {
        echo "\tset $fieldName(value) {\n";
        if ($type === 'boolean') {

            echo "\t\tif (value) {\n";
            echo "\t\t\tthis.setAttribute('$kebabCasedName', value);\n";
            echo "\t\t} else {\n";
            echo "\t\t\tthis.removeAttribute('$kebabCasedName');\n";
            echo "\t\t}\n";

        } else {
            echo "\t\tthis.setAttribute('$kebabCasedName', value);\n";
        }
        echo "\t}\n";
    }

    private static function observedAttributes(array $fields): void {
        echo "\tstatic get observedAttributes() {\n";
        echo "\t\treturn [";
        foreach ($fields as $field) {
            $kebabCasedName = kebabCase($field->name);
            echo "'$kebabCasedName',";
        }
        echo "];\n";
        echo "\t}\n";
    }

    private static function defaultValues(WebComponent $component, array $fields): void {
        echo "\t_setDefaultValues() {\n";
        foreach ($fields as $field) {
            $name = $field->name;

            if (!startsWith($name, '_') && isset($component->$name)) {
                $value = convertToJavascriptValue($component->$name);
                echo "\t\tthis.$name = $value;\n";
            }
        } 
        echo "\t}\n";
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
        return file_get_contents(__DIR__ . '\..\service\abstract-web-component.js');
    }
}
