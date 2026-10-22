@props(['tags'])
@if($tags->isNotEmpty())
    <span {{ $attributes->merge(['class' => 'flex flex-wrap gap-1']) }}>
        @foreach($tags as $tag)
            <x-tag-badge :tag="$tag"/>
        @endforeach
    </span>
@endif
