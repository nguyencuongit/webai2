@php
    $hasOverlapContent = $shortcode->overlap_image || $shortcode->overlap_title || $shortcode->overlap_text;
@endphp

@if ($hasOverlapContent)
    <section
        class="lyly-banner-overlap"
        @if ($shortcode->overlap_image)
            style="--lyly-banner-overlap-bg: url('{{ RvMedia::url($shortcode->overlap_image) }}');"
        @endif
        data-lyly-banner-overlap-panel
    >
        <div class="lyly-banner-overlap__inner">
            <div class="lyly-banner-overlap__content">
                @if ($shortcode->overlap_title)
                    <h2>{!! BaseHelper::clean($shortcode->overlap_title) !!}</h2>
                @endif

                @if ($shortcode->overlap_text)
                    <p>{!! BaseHelper::clean(nl2br($shortcode->overlap_text)) !!}</p>
                @endif
            </div>
        </div>
    </section>
@endif
