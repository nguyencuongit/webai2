@extends(Theme::getThemeNamespace('layouts.base'))

@section('content')
    <div class="webai-shell">
        {!! Theme::partial('mobile-header') !!}

        {!! Theme::partial('sidebar') !!}

        <main class="webai-main">
            {!! Theme::partial('page-header') !!}

            <div class="webai-content">
                {!! Theme::content() !!}
            </div>
        </main>
    </div>

@endsection
