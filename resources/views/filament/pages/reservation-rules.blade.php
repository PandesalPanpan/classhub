@php
    $content = (string) \App\Models\Setting::get('reservation_rules_content', '');
@endphp

<div class="fi-prose">
    {!! \Illuminate\Support\Str::markdown($content, [
        'html_input' => 'strip',
        'allow_unsafe_links' => false,
    ]) !!}
</div>

