@props(['path', 'highlightedByTorchlight' => false])
<small @class([
    'relative float-right opacity-50 hover:opacity-100 transition-opacity duration-250 not-prose hidden md:block',
    $highlightedByTorchlight ? '-top-1 right-1' : 'top-0 right-0',
])><span class="sr-only">Filepath: </span>{{ $path }}</small>