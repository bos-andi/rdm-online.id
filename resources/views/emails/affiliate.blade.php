<x-mail::message>
# {{ $heading }}

@foreach ($lines as $line)
{!! \Illuminate\Support\Str::of($line)->replace(['<', '>'], ['&lt;', '&gt;']) !!}

@endforeach
@if ($actionUrl)
<x-mail::button :url="$actionUrl">
{{ $actionLabel ?? 'Buka Panel' }}
</x-mail::button>
@endif

Salam,<br>
Tim {{ config('app.name') }}
</x-mail::message>
