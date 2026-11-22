@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'input-error']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-center gap-1.5"><x-icon name="warning" class="h-4 w-4 shrink-0" />{{ $message }}</li>
        @endforeach
    </ul>
@endif
