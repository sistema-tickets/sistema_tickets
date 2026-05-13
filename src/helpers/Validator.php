<?php

class Validator
{
    private array $errors = [];

    public function required(string $field, mixed $value): self
    {
        if ($value === null || $value === '') {
            $this->errors[$field] = "El campo $field es requerido";
        }
        return $this;
    }

    public function email(string $field, mixed $value): self
    {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "El campo $field debe ser un email válido";
        }
        return $this;
    }

    public function minLength(string $field, mixed $value, int $min): self
    {
        if ($value !== null && mb_strlen((string)$value) < $min) {
            $this->errors[$field] = "El campo $field debe tener al menos $min caracteres";
        }
        return $this;
    }

    public function maxLength(string $field, mixed $value, int $max): self
    {
        if ($value !== null && mb_strlen((string)$value) > $max) {
            $this->errors[$field] = "El campo $field no puede superar $max caracteres";
        }
        return $this;
    }

    public function inArray(string $field, mixed $value, array $allowed): self
    {
        if ($value !== null && !in_array($value, $allowed, true)) {
            $this->errors[$field] = "El valor de $field no es válido";
        }
        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
