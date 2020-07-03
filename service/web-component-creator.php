<?php
require_once(__DIR__ . '\..\components\web-component.php');

class WebComponentCreator {

    static function createClass(WebComponent $component): string {
        $className = get_class($component);
        $fields = WebComponentCreator::getFields($component);

        return readOutput(function() use ($component, $className, $fields) { ?>
            <script>
            class <?= $className ?> extends AbstractWebComponent {
            
                constructor() {
                    super();
                }

                <?php WebComponentCreator::defaultValues($component, $fields) ?>

                /** Properties / attributes accessors */
                <?php WebComponentCreator::defineProperties($component, $fields) ?>

                /** Rendering */
                _compileStyle() {
                    <?php WebComponentCreator::templateAttributes($fields) ?>
                    return `<?= $component->getStyle() ?>`;
                }
                _compileTemplate() {
                    <?php WebComponentCreator::templateAttributes($fields) ?>
                    return `<?= $component->getTemplate() ?>`;
                }

                <?php WebComponentCreator::observedAttributes($fields) ?>

                /** Custom functionalities */
                <?= $component->getScript() ?>
            }
            </script>
            <?php }, TagMode::REMOVE_SURROUNDING, 'script');
    }

    private static function defineProperties(WebComponent $component, array $fields): void {
        foreach ($fields as $field) {
            $name = $field->name;
            $kebabCasedName = kebabCase($name);
            echo "\tget $name() {\n";
            echo "\t\treturn this.getAttribute('$kebabCasedName');\n";
            echo "\t}\n";
        
            echo "\tset $name(value) {\n";
            if (gettype($component->$name) === 'boolean') {

                echo "\t\tif (value) {\n";
                echo "\t\t\tthis.setAttribute('$kebabCasedName', value);\n";
                echo "\t\t} else {\n";
                echo "\t\t\tthis.removeAttribute('$kebabCasedName>');\n";
                echo "\t\t}\n";

            } else {
                echo "\t\tthis.setAttribute('$kebabCasedName', value);\n";
            }
            echo "\t}\n";
        }
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

            if (isset($component->$name)) {
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
