<blockquote @class([
        'border-blue-500' => $class === 'info',
        'border-green-500' => $class === 'success',
        'border-amber-500' => $class === 'warning',
        'border-red-600' => $class === 'danger',
    ])>
    {!! $contents !!}
</blockquote>