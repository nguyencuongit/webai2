@php
    $depth = $depth ?? 0;
    $nodes = $menu_nodes->loadMissing('metadata');
@endphp

<ul @class(['lyly-mobile-menu', 'lyly-mobile-menu--sub' => $depth > 0])>
    @foreach ($nodes as $row)
        <li @class(['lyly-mobile-menu__item', 'has-children' => $row->has_child])>
            <div class="lyly-mobile-menu__row">
                <a
                    class="lyly-mobile-menu__link"
                    href="{{ url($row->url) }}"
                    title="{{ $row->title }}"
                    @if ($row->target !== '_self') target="{{ $row->target }}" @endif
                >
                    {!! $row->icon_html !!}
                    <span>{!! BaseHelper::clean($row->title) !!}</span>
                </a>

                @if ($row->has_child)
                    <button
                        type="button"
                        class="lyly-mobile-menu__toggle"
                        aria-label="{{ __('Toggle menu') }}"
                        aria-expanded="false"
                        data-lyly-mobile-menu-toggle
                    >
                        <x-core::icon name="ti ti-chevron-down" />
                    </button>
                @endif
            </div>

            @if ($row->has_child)
                <div class="lyly-mobile-menu__children">
                    {!!
                        Menu::generateMenu([
                            'menu' => $menu,
                            'menu_nodes' => $row->child,
                            'view' => 'lyly-mobile-menu',
                            'depth' => $depth + 1,
                        ])
                    !!}
                </div>
            @endif
        </li>
    @endforeach
</ul>
