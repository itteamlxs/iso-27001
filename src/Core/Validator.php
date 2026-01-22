<?php
namespace App\Core;
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private ?bool $validated = null;
    
    private array $messages = [
        'required' => 'El campo :field es obligatorio',
        'email' => 'El campo :field debe ser un email válido',
        'min' => 'El campo :field debe tener al menos :param caracteres',
        'max' => 'El campo :field no debe exceder :param caracteres',
        'numeric' => 'El campo :field debe ser numérico',
        'integer' => 'El campo :field debe ser un número entero',
        'in' => 'El campo :field debe ser uno de: :param',
    ];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function validate(): bool
    {
        if ($this->validated !== null) {
            return $this->validated;
        }

        foreach ($this->rules as $field => $rules) {
            $ruleList = is_string($rules) ? explode('|', $rules) : $rules;
            
            foreach ($ruleList as $rule) {
                if (empty($rule)) continue;
                $this->applyRule($field, $rule);
            }
        }
        
        $this->validated = empty($this->errors);
        return $this->validated;
    }

    public function fails(): bool
    {
        return !$this->validate();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        if (!$this->validate()) {
            throw new \Exception('Validation failed');
        }
        return array_intersect_key($this->data, $this->rules);
    }

    private function applyRule(string $field, string $rule): void
    {
        if (strpos($rule, ':') !== false) {
            [$ruleName, $param] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $param = null;
        }

        $method = 'validate' . str_replace('_', '', ucwords($ruleName, '_'));

        if (!method_exists($this, $method)) {
            return;
        }

        $value = $this->data[$field] ?? null;

        if (!$this->$method($field, $value, $param)) {
            $this->addError($field, $ruleName, $param);
        }
    }

    private function validateRequired(string $field, $value, $param): bool
    {
        if (is_null($value)) return false;
        if (is_string($value) && trim($value) === '') return false;
        return true;
    }

    private function validateIn(string $field, $value, $param): bool
    {
        $values = explode(',', $param);
        return in_array($value, $values, true);
    }

    private function addError(string $field, string $rule, $param = null): void
    {
        $message = $this->messages[$rule] ?? 'El campo :field no es válido';
        $message = str_replace(':field', $field, $message);
        $message = str_replace(':param', (string) $param, $message);

        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }
}
