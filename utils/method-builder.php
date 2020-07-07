<?php

class JavascriptMethod {

    private string $name;
    private array $params;
    private string $body;

    function __construct(string $name, array $params, string $body) {
        $this->name = $name;
        $this->params = $params;
        $this->body = $body;
    }

    function __toString() {
        $paramsStr = "";
        foreach ($this->params as $param) {
            if ($paramsStr != "") {
                $paramsStr .= ', ';
            }
            $paramsStr .= $param;
        }

        return "$this->name($paramsStr) {\n"
            . "$this->body"
            . "}\n";
    }
}

class JavascriptMethodBuilder {

    private string $name = "";
    private array $params = [];
    private string $body = "";
    private int $tabQuantity = 2;

    function __construct(string $name) {
        $this->name = $name;
    }

    public function param(string $param): JavascriptMethodBuilder {
        array_push($this->params, $param);
        return $this;
    }

    public function params(array $params): JavascriptMethodBuilder {
        foreach ($params as $param) {
            $this->param($param);
        }
        return $this;
    }

    public function var(string $name, string $value, bool $newLine = true, bool $endLine = true, bool $addTabs = true): JavascriptMethodBuilder {
        if ($addTabs) {
            $this->addTabs();
        }
        $this->body .= "var $name = $value";
        return $this->endAndNewLine($newLine, $endLine);
    }

    public function const(string $name, string $value, bool $newLine = true, bool $endLine = true, bool $addTabs = true): JavascriptMethodBuilder {
        if ($addTabs) {
            $this->addTabs();
        }
        $this->body .= "const $name = $value";
        return $this->endAndNewLine($newLine, $endLine);
    }

    public function code(string $code): JavascriptMethodBuilder {
        $this->line($code, false, false, false);
        return $this;
    }

    public function line(string $line, bool $newLine = true, bool $endLine = true, bool $addTabs = true): JavascriptMethodBuilder {
        if ($addTabs) {
            $this->addTabs();
        }
        $this->body .= $line;
        return $this->endAndNewLine($newLine, $endLine);
    }

    public function lines(array $lines): JavascriptMethodBuilder {
        foreach ($lines as $line) {
            $this->line($line);
        }
        return $this;
    }

    public function for(string $variableName, string $source): JavascriptMethodBuilder {
        $this->addTabs();
        $this->body .= "for (let $variableName in $source) {";
        $this->tabQuantity++;
        $this->newLine();
        return $this;
    }

    public function foreach(string $source, string $variableName): JavascriptMethodBuilder {
        $this->addTabs();
        $this->body .= "$source.forEach($variableName => {";
        $this->tabQuantity++;
        $this->newLine();
        return $this;
    }

    public function if(string $condition): JavascriptMethodBuilder {
        $this->addTabs();
        $this->body .= "if ($condition) {";
        $this->tabQuantity++;
        $this->newLine();
        return $this;
    }

    public function else(string $condition = NULL): JavascriptMethodBuilder {
        $this->addTabs($this->tabQuantity - 1);
        $this->body .= "} else ";
        if (isset($condition)) {
            $this->body .= "if ($condition) ";
        }
        $this->body .= "{";
            $this->newLine();
        return $this;
    }

    public function end(): JavascriptMethodBuilder {
        $this->tabQuantity--;
        $this->addTabs();
        $this->body .= "}";
        $this->newLine();
        return $this;
    }

    public function return(string $return, bool $newLine = true, bool $endLine = true, bool $addTabs = true): JavascriptMethodBuilder {
        if ($addTabs) {
            $this->addTabs();
        }
        $this->body .= "return $return";
        return $this->endAndNewLine($newLine, $endLine);
    }

    public function endAndNewLine(bool $newLine = true, bool $endLine = true): JavascriptMethodBuilder {
        if ($endLine && !endsWith($this->body, ";")) {
            $this->body .= ";";
        }
        if ($newLine) {
            $this->newLine();
        }
        return $this;
    }

    public function phpIf(bool $condition, $truthyCallback, $falsyCallback = NULL): JavascriptMethodBuilder {
        if ($condition) {
            $truthyCallback($this);
        } else if (isset($falsyCallback)) {
            $falsyCallback($this);
        }
        return $this;
    }

    public function newLine(): JavascriptMethodBuilder {
        $this->body .= "\n";
        return $this;
    }

    public function build(): JavascriptMethod {
        return new JavascriptMethod($this->name, $this->params, $this->body);
    }

    private function addTabs(int $tabQuantity = NULL): void {
        if (!isset($tabQuantity)) {
            $tabQuantity = $this->tabQuantity;
        }
        for ($i = 1; $i < $tabQuantity; $i++) {
            $this->body .= "\t";
        }
    }
}