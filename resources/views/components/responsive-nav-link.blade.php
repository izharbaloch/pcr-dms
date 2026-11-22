@props(['active'])

<a {{ $attributes->merge(['class' => 'side-link ' . (($active ?? false) ? 'side-link-active' : '')]) }}>
    {{ $slot }}
</a>
