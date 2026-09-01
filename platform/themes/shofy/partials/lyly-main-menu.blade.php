@php
    $depth = $depth ?? 0;
    $nodes = $menu_nodes->loadMissing('metadata');
@endphp

@if ($depth === 0)
    <ul class="lyly-main-menu">
        @foreach ($nodes as $row)
            <li class="lyly-main-menu__item">
                <a
                    class="lyly-main-menu__link"
                    href="{{ url($row->url) }}"
                    title="{{ $row->title }}"
                    @if ($row->target !== '_self') target="{{ $row->target }}" @endif
                >
                    {!! $row->icon_html !!}
                    <span>{!! BaseHelper::clean($row->title) !!}</span>
                </a>

                @if ($row->has_child)
                    <div class="lyly-main-menu__mega">
                        <div class="lyly-main-menu__mega-inner">
                            <a
                                class="lyly-main-menu__media"
                                href="{{ url($row->url) }}"
                                title="{{ $row->title }}"
                                @if ($row->target !== '_self') target="{{ $row->target }}" @endif
                            >
                                <span>{!! BaseHelper::clean($row->title) !!}</span>
                            </a>

                            <div class="lyly-main-menu__columns">
                                @foreach ($row->child->loadMissing('metadata') as $child)
                                    <div class="lyly-main-menu__column">
                                        <a
                                            class="lyly-main-menu__heading"
                                            href="{{ url($child->url) }}"
                                            title="{{ $child->title }}"
                                            @if ($child->target !== '_self') target="{{ $child->target }}" @endif
                                        >
                                            {!! $child->icon_html !!}
                                            <span>{!! BaseHelper::clean($child->title) !!}</span>
                                        </a>

                                        @if ($child->has_child)
                                            <ul class="lyly-main-menu__sub-list">
                                                @foreach ($child->child->loadMissing('metadata') as $sub)
                                                    <li>
                                                        <a
                                                            class="lyly-main-menu__sub-link"
                                                            href="{{ url($sub->url) }}"
                                                            title="{{ $sub->title }}"
                                                            @if ($sub->target !== '_self') target="{{ $sub->target }}" @endif
                                                        >
                                                            {!! $sub->icon_html !!}
                                                            <span>{!! BaseHelper::clean($sub->title) !!}</span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
@else
    <ul class="lyly-main-menu__sub-list">
        @foreach ($nodes as $row)
            <li>
                <a
                    class="lyly-main-menu__sub-link"
                    href="{{ url($row->url) }}"
                    title="{{ $row->title }}"
                    @if ($row->target !== '_self') target="{{ $row->target }}" @endif
                >
                    {!! $row->icon_html !!}
                    <span>{!! BaseHelper::clean($row->title) !!}</span>
                </a>
            </li>
        @endforeach
    </ul>
@endif
