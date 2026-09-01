@php
    $imageUrl = $post->image ? \Botble\Media\Facades\RvMedia::getImageUrl($post->image) : Theme::asset()->url('images/event-modal-ai.png');
    $description = $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->content), 220);
    $link = $post->link ?: route('public.video-lab');
@endphp

<div class="webai-event-modal" data-webai-event-modal hidden aria-hidden="true">
    <div class="webai-event-modal__backdrop" data-webai-event-close></div>
    <section class="webai-event-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="webai-event-title">
        <button class="webai-event-modal__close" type="button" aria-label="Đóng" data-webai-event-close>×</button>
        <div class="webai-event-modal__image">
            <img src="{{ $imageUrl }}" alt="{{ $post->title }}">
        </div>
        <div class="webai-event-modal__content">
            <span class="webai-event-modal__eyebrow">Ưu đãi đang diễn ra</span>
            <h2 id="webai-event-title">{{ $post->title }}</h2>
            @if ($description)<p>{{ $description }}</p>@endif
            <div class="webai-event-modal__meta">
                <span>✦ Trải nghiệm không giới hạn</span>
                <span>◷ Diễn ra trong tuần này</span>
            </div>
            <a class="webai-event-modal__action" href="{{ $link }}">Khám phá ngay</a>
        </div>
    </section>
</div>
