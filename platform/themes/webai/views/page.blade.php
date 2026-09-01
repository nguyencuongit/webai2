@php
    Theme::set('pageTitle', $page->name);
@endphp

<div class="webai-page">
    <h1 class="webai-title">{{ $page->name }}</h1>

    <div class="ck-content">
        {!! apply_filters(PAGE_FILTER_FRONT_PAGE_CONTENT, BaseHelper::clean($page->content), $page) !!}
    </div>
</div>
