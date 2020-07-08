<?php
require_once(__DIR__ . '\..\components\web-component.php');
require_once(__DIR__ . '\..\service\style-register.php');
require_once(__DIR__ . '\..\utils\utils.php');
require_once(__DIR__ . '\..\utils\method-builder.php');

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
        $methodBuilder = (new JavascriptMethodBuilder("_compile"))
            ->param("template");
        WebComponentCreator::templateAttributes($methodBuilder, $fields);

        echo $methodBuilder
            ->const("keys", "Object.keys(templateAttributes)")
            ->const("fn", "new Function(...keys, 'return `' + template.replace(/`/g, '\\`') + '`')")
            ->return("fn(...keys.map(key => templateAttributes[key]))")
            ->build();
    }

    private static function renderStyles(WebComponent $component): void {
        $styles = $component->getStyle();
        $methodBuilder = new JavascriptMethodBuilder("_compileStyle");

        if ($component->_domMode == DomMode::SHADOW) {
            $styles = preg_replace('/\$/', '\\\\$', $styles);
            $methodBuilder->return("this._compile(`$styles`)");

        } else {
            $methodBuilder->return("''");
            StyleRegister::addStyle($styles, $component->getTagName());
        }
        echo $methodBuilder->build();
    }

    private static function renderTemplate(WebComponent $component): void {
        $template = $component->getTemplate();
        $template = preg_replace('/\$/', '\\\\$', $template);
        echo (new JavascriptMethodBuilder("_compileTemplate"))
            ->return("this._compile(`$template`)")
            ->build();
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
                if (WebComponentCreator::isObject($type)) {
                    WebComponentCreator::createProperty($fieldName . '_', $value, $type);
                }
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
            $value = json_encode($value);
        }
        echo "$fieldName = $value;\n";
    }

    private static function createAttributeGetter(string $fieldName, string $kebabCasedName, string $type): void {
        $typeConversionFunction = WebComponentCreator::getTypeConverter($type);
        $methodBuilder = new JavascriptMethodBuilder("get $fieldName");
        if (WebComponentCreator::isObject($type)) {
            $methodBuilder->return("this.${fieldName}_");

        } else {
            $methodBuilder->code("return ");
            if (isset($typeConversionFunction)) {
                $methodBuilder->code("$typeConversionFunction(");
            }
            $methodBuilder->code("this.getAttribute('$kebabCasedName')");
            if (isset($typeConversionFunction)) {
                $methodBuilder->code(")");
            }
            $methodBuilder->endAndNewLine();
        }
        echo $methodBuilder->build();
    }

    private static function getTypeConverter(string $type) {
        if (array_key_exists($type, WebComponentCreator::$typeConversionFunctions)) {
            return WebComponentCreator::$typeConversionFunctions[$type];
        }
        return 'JSON.parse';
    }

    private static function createAttributeSetter(string $fieldName, string $kebabCasedName, string $type): void {
        echo (new JavascriptMethodBuilder("set $fieldName"))
            ->param("value")
            ->phpIf($type === 'boolean',
                fn($builder) => $builder
                    ->if("value")
                        ->line("this.setAttribute('$kebabCasedName', value)")
                    ->else()
                        ->line("this.removeAttribute('$kebabCasedName')")
                    ->end(),
                fn($builder) => $builder
                    ->phpIf(WebComponentCreator::isObject($type),
                        fn($builder) => $builder
                            ->line("this.setAttribute('$kebabCasedName', encodeObject(value))")
                            ->line("this.${fieldName}_ = value"),
                        fn($builder) => $builder->line("this.setAttribute('$kebabCasedName', value)")
                    )
            )
            ->build();
    }

    private static function isObject(string $type): bool {
        return !array_key_exists($type, WebComponentCreator::$typeConversionFunctions) || $type == 'array' || $type == 'object';
    }

    private static function observedAttributes(array $fields): void {
        $methodBuilder = (new JavascriptMethodBuilder("static get observedAttributes"))
            ->return("[", true, false);
        foreach ($fields as $field) {
            $kebabCasedName = kebabCase($field->name);
            $methodBuilder->code("'$kebabCasedName', ", true, false, true);
        }
        echo $methodBuilder
            ->line("]")
            ->build();
    }

    private static function defaultValues(WebComponent $component, array $fields): void {
        $methodBuilder = new JavascriptMethodBuilder("_setDefaultValues");
        foreach ($fields as $field) {
            $name = $field->name;
            
            if (!startsWith($name, '_') && isset($component->$name)) {
                $encodedValue = json_encode($component->$name);
                $methodBuilder->line("this.$name = $encodedValue");
            }
        } 
        echo $methodBuilder->build();
    }

    static function getFields(WebComponent $component) {
        $reflect = new ReflectionClass($component);
        return $reflect->getProperties();
    }

    private static function templateAttributes(JavascriptMethodBuilder $methodBuilder, array $fields): void {
        $methodBuilder->const("templateAttributes", "{", true, false, true);
        foreach ($fields as $field) {
            $name = $field->name;
            $methodBuilder->code("'$name': this.$name, ", true, false, true);
        }
        $methodBuilder->line("}");
        $methodBuilder
            ->foreach("getInstancePropertiesNames(this, HTMLElement.prototype)", "propertyName")
                ->newLine()
                ->if("isMethod(this, propertyName, HTMLElement.prototype)")
                    ->line("templateAttributes[propertyName] = this[propertyName].bind(this)")
                    ->const("methodRef", "this._createMethodRef(this[propertyName].bind(this), propertyName)")
                    ->line("templateAttributes[propertyName + 'Ref'] = `fnRefs['\${methodRef}']`")
                    ->newLine()
                ->else("typeof this[propertyName] === 'object'")
                    ->const("obj", "this[propertyName]")
                    ->const("objectRef", "this._createObjectRef(obj, propertyName)")
                    ->line("templateAttributes[propertyName + 'Ref'] = `objRefs['\${objectRef}']`")
                    ->line("templateAttributes[propertyName + 'Encoded'] = encodeObject(obj)")
                ->end()
            ->end()
            ->line(")");
    }
}
