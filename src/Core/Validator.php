<?php

namespace App\Core;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $messages = [
        'required' => 'El campo :field es obligatorio',
        'email' => 'El campo :field debe ser un email válido',
        'min' => 'El campo :field debe tener al menos :param caracteres',
        'max' => 'El campo :field no debe exceder :param caracteres',
        'numeric' => 'El campo :field debe ser numérico',
        'integer' => 'El campo :field debe ser un número entero',
        'alpha' => 'El campo :field solo debe contener letras',
        'alpha_num' => 'El campo :field solo debe contener letras y números',
        'in' => 'El campo :field debe ser uno de: :param',
        'unique' => 'El :field ya existe',
        'confirmed' => 'La confirmación de :field no coincide',
        'regex' => 'El formato del campo :field no es válido',
        'url' => 'El campo :field debe ser una URL válida',
        'date' => 'El campo :field debe ser una fecha válida',
        'before' => 'El campo :field debe ser anterior a :param',
        'after' => 'El campo :field debe ser posterior a :param',
        'same' => 'El campo :field debe coincidir con :param',
        'different' => 'El campo :field debe ser diferente de :param',
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
        foreach ($this->rules as $field => $rules) {
            $ruleList = is_string($rules) ? explode('|', $rules) : $rules;
            
            foreach ($ruleList as $rule) {
                $this->applyRule($field, $rule);
            }
        }

        return empty($this->errors);
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
        if (is_null($value)) {
            return false;
        }
        
        if (is_string($value) && trim($value) === '') {
            return false;
        }
        
        return true;
    }

    private function validateEmail(string $field, $value, $param): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validateMin(string $field, $value, $param): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return mb_strlen($value) >= (int) $param;
    }

    private function validateMax(string $field, $value, $param): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return mb_strlen($value) <= (int) $param;
    }

    private function validateNumeric(string $field, $value, $param): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return is_numeric($value);
    }

    private function validateInteger(string $field, $value, $param): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    private function validateAlpha(string $field, $value, $param): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return preg_match('/^[\pL\pM]+$/u', $value);
    }

    private function validateAlphaNum(string $field, $value, $param): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return preg_match('/^[\pL\pM\pN]+$/u', $value);
    }

    private function validateIn(string $field, $value, $param): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        $values = explode(',', $param);
        return in_array($value, $values, true);
    }

    private function validateConfirmed(string $field, $value, $param): bool
    {
        $confirmField = $field . '_confirmation';
        return isset($this->data[$confirmField]) && $value === $this->data[$confirmField];
    }

    private function validateRegex(string $field, $value, $param): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return preg_match($param, $value);
    }

    private function validateUrl(string $field, $value, $param): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function validateDate(string $field, $value, $param): bool
    {
        if (is_null($value) || $value === '') {
            return true;
        }
        
        return strtotime($value) !== false;
    }

    private function validateSame(string $field, $value, $param): bool
    {
        return isset($this->data[$param]) && $value === $this->data[$param];
    }

    private function validateDifferent(string $field, $value, $param): bool
    {
        return !isset($this->data[$param]) || $value !== $this->data[$param];
    }

    private function addError(string $field, string $rule, $param = null): void
    {
        $message = $this->messages[$rule] ?? 'El campo :field no es válido';
        
        $message = str_replace(':field', $field, $message);
        
        if ($param !== null) {
            $message = str_replace(':param', $param, $message);
        }
        
        $this->errors[$field][] = $message;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return !$this->validate();
    }

    public function validated(): array
    {
        if (!$this->validate()) {
            throw new \Exception('Validation failed');
        }
        
        return array_intersect_key($this->data, $this->rules);
    }
}
