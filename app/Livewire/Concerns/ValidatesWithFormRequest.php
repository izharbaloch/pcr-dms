<?php

namespace App\Livewire\Concerns;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

/**
 * Lets a Livewire component validate its state with a Form Request, so the rules,
 * messages and cross-field checks live in one place.
 */
trait ValidatesWithFormRequest
{
    /**
     * Validates the data with the Form Request; on failure the errors land on the component.
     *
     * @param  class-string<FormRequest>  $requestClass
     * @return array<string, mixed> the validated data
     */
    protected function validateWith(string $requestClass, array $data): array
    {
        $request = $this->formRequest($requestClass, $data);
        $request->validateResolved();

        return $request->validated();
    }

    /**
     * Validates a single field with the Form Request's rule for it, e.g. when the user leaves the field.
     *
     * @param  class-string<FormRequest>  $requestClass
     */
    protected function validateFieldWith(string $requestClass, array $data, string $field): void
    {
        $request = $this->formRequest($requestClass, $data);
        $validator = validator($data, Arr::only($request->rules(), [$field]), $request->messages(), $request->attributes());

        $this->resetErrorBag($field);

        if ($validator->fails()) {
            $this->addError($field, $validator->errors()->first($field));
        }
    }

    private function formRequest(string $requestClass, array $data): FormRequest
    {
        return $requestClass::create('/', 'POST', $data)
            ->setContainer(app())
            ->setRedirector(app('redirect'))
            ->setUserResolver(fn () => auth()->user());
    }
}
