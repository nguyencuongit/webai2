@php
    $adminLogo = theme_option('logo') ?: setting('admin_logo');
@endphp

@if ($adminLogo || config('core.base.general.logo'))
    <a href="{{ route('dashboard.index') }}">
        <img
            src="{{ $adminLogo ? RvMedia::getImageUrl($adminLogo) : url(config('core.base.general.logo')) }}"
            style="max-height: {{ setting('admin_logo_max_height', $defaultLogoHeight ?? 32) }}px; height: auto;"
            alt="{{ setting('admin_title', config('core.base.general.base_name')) }}"
            class="navbar-brand-image"
        >
    </a>
@endif
