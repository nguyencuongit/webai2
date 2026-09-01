@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>
                {{ trans('plugins/ai-video-generator::ai-video-generator.name') }}
            </x-core::card.title>
        </x-core::card.header>

        <x-core::card.body>
            <p class="text-muted mb-0">
                {{ trans('plugins/ai-video-generator::ai-video-generator.description') }}
            </p>
        </x-core::card.body>
    </x-core::card>
@endsection
