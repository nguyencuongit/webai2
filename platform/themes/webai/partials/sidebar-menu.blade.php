@php
    $nodes = $menu_nodes->loadMissing('metadata');
    $isSubMenu = isset($is_sub_menu) && $is_sub_menu;
@endphp

<ul class="{{ $isSubMenu ? 'webai-nav-sub' : 'webai-nav-list' }}">
    @foreach ($nodes as $row)
        @if (! $isSubMenu && $loop->index === 7)
            <li class="webai-nav-section-label" aria-hidden="true">Công cụ khác</li>
        @endif

        @php
            $url = url($row->url);
            $isActive = rtrim(request()->url(), '/') === rtrim($url, '/');
            $icon = trim($row->icon_html ?: '');
        @endphp

        <li>
            <a
                class="webai-nav-item {{ $isActive ? 'is-active' : '' }}"
                href="{{ $url }}"
                title="{{ $row->title }}"
                @if ($row->target !== '_self') target="{{ $row->target }}" @endif
            >
                <span class="webai-nav-icon">
                    @if ($icon)
                        {!! $icon !!}
                    @else
                        ▣
                    @endif
                </span>
                <span>{!! BaseHelper::clean($row->title) !!}</span>
            </a>

            @if ($row->has_child)
                {!!
                    Menu::generateMenu([
                        'menu' => $menu,
                        'menu_nodes' => $row->child,
                        'view' => 'sidebar-menu',
                        'is_sub_menu' => true,
                    ])
                !!}
            @endif
        </li>
    @endforeach
</ul>
