<?php
declare(strict_types=1);

final class Validator
{
    private array $errors = [];

    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ((array) $fieldRules as $rule) {
                [$name, $parameter] = array_pad(explode(':', (string) $rule, 2), 2, null);

                if ($name !== 'required' && ($value === null || $value === '')) {
                    continue;
                }

                if (!$this->passes($name, $value, $parameter, $data, $field)) {
                    $this->errors[$field][] = $this->message($field, $name, $parameter);
                }
            }
        }

        return $this->errors === [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    private function passes(
        string $rule,
        mixed $value,
        ?string $parameter,
        array $data,
        string $field
    ): bool {
        return match ($rule) {
            'required' => $value !== null && $value !== '',
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'numeric' => is_numeric($value),
            'min' => mb_strlen((string) $value) >= (int) $parameter,
            'max' => mb_strlen((string) $value) <= (int) $parameter,
            'in' => in_array((string) $value, explode(',', (string) $parameter), true),
            'date' => strtotime((string) $value) !== false,
            'confirmed' => $value === ($data[$field . '_confirmation'] ?? null),
            default => throw new InvalidArgumentException("Unknown validation rule [{$rule}]."),
        };
    }

    private function message(string $field, string $rule, ?string $parameter): string
    {
        $label = ucwords(str_replace('_', ' ', $field));

        return match ($rule) {
            'required' => "{$label} is required.",
            'email' => "{$label} must be a valid email address.",
            'integer' => "{$label} must be an integer.",
            'numeric' => "{$label} must be numeric.",
            'min' => "{$label} must contain at least {$parameter} characters.",
            'max' => "{$label} may not contain more than {$parameter} characters.",
            'in' => "{$label} contains an invalid value.",
            'date' => "{$label} must be a valid date.",
            'confirmed' => "{$label} confirmation does not match.",
            default => "{$label} is invalid.",
        };
    }
}
