<?php

class MethodBuilder {

    private string $output = "";
    private int $tabQuantity = 1;

    public function line(string $line): MethodBuilder {
        $this->newLine();
        $this->addTabs();
        $this->output .= $line . ";";
        return $this;
    }

    public function lines(array $lines): MethodBuilder {
        foreach ($lines as $line) {
            $this->line($line);
        }
        return $this;
    }

    public function if(string $condition): MethodBuilder {
        $this->newLine();
        $this->addTabs();
        $this->output .= "if ($condition) {";
        $this->tabQuantity++;
        return $this;
    }

    public function else(string $condition = NULL): MethodBuilder {
        $this->newLine();
        $this->addTabs($this->tabQuantity - 1);
        $this->output .= "} else ";
        if (isset($condition)) {
            $this->output .= "if ($condition) ";
        }
        $this->output .= "{";
        return $this;
    }

    public function end(): MethodBuilder {
        $this->newLine();
        $this->tabQuantity--;
        $this->addTabs();
        $this->output .= "}";
        return $this;
    }

    public function newLine(): MethodBuilder {
        $this->output .= "\n";
        return $this;
    }

    public function build(): string {
        return $this->output;
    }

    private function addTabs(int $tabQuantity = NULL): void {
        if (!isset($tabQuantity)) {
            $tabQuantity = $this->tabQuantity;
        }
        for ($i = 1; $i < $tabQuantity; $i++) {
            $this->output .= "\t";
        }
    }
}

$builder = new MethodBuilder();
echo $builder
    ->line("console.log('oi')")
    ->if("true")
        ->line("console.log('eu sou verdadeiro')")
    ->else()
        ->line("console.log('eu sou falso')")
    ->end()
    ->build();