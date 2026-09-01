@extends(BaseHelper::getAdminMasterLayoutTemplate())

@php
    $jsonFlags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
@endphp

@section('content')
    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>
                {{ trans('plugins/ai-video-generator::ai-video-generator.tasks.view', ['id' => $task->getKey()]) }}
            </x-core::card.title>
        </x-core::card.header>

        <x-core::card.body>
            <x-core::datagrid>
                <x-core::datagrid.item>
                    <x-slot:title>ID</x-slot:title>
                    {{ $task->id }}
                </x-core::datagrid.item>

                <x-core::datagrid.item>
                    <x-slot:title>{{ trans('plugins/ai-video-generator::ai-video-generator.tasks.customer') }}</x-slot:title>
                    {{ $task->customer?->email ?: ($task->customer_id ?: '-') }}
                </x-core::datagrid.item>

                <x-core::datagrid.item>
                    <x-slot:title>{{ trans('plugins/ai-video-generator::ai-video-generator.tasks.task_id') }}</x-slot:title>
                    {{ $task->task_id }}
                </x-core::datagrid.item>

                <x-core::datagrid.item>
                    <x-slot:title>{{ trans('plugins/ai-video-generator::ai-video-generator.tasks.status') }}</x-slot:title>
                    {{ $task->status }}
                </x-core::datagrid.item>

                <x-core::datagrid.item>
                    <x-slot:title>{{ trans('plugins/ai-video-generator::ai-video-generator.tasks.completed_at') }}</x-slot:title>
                    {{ $task->completed_at ?: '-' }}
                </x-core::datagrid.item>

                <x-core::datagrid.item>
                    <x-slot:title>{{ trans('core/base::tables.created_at') }}</x-slot:title>
                    {{ $task->created_at }}
                </x-core::datagrid.item>

                <x-core::datagrid.item>
                    <x-slot:title>{{ trans('core/base::tables.updated_at') }}</x-slot:title>
                    {{ $task->updated_at }}
                </x-core::datagrid.item>
            </x-core::datagrid>

            <x-core::tab class="mt-3">
                <x-core::tab.item
                    :is-active="true"
                    id="generated"
                    label="{{ trans('plugins/ai-video-generator::ai-video-generator.tasks.generated') }}"
                />
                <x-core::tab.item
                    id="has-nsfw"
                    label="{{ trans('plugins/ai-video-generator::ai-video-generator.tasks.has_nsfw') }}"
                />
                <x-core::tab.item
                    id="payload"
                    label="{{ trans('plugins/ai-video-generator::ai-video-generator.tasks.payload') }}"
                />
            </x-core::tab>

            <x-core::tab.content>
                <x-core::tab.pane
                    id="generated"
                    :is-active="true"
                >
                    <pre>{{ json_encode($task->generated, $jsonFlags) }}</pre>
                </x-core::tab.pane>

                <x-core::tab.pane id="has-nsfw">
                    <pre>{{ json_encode($task->has_nsfw, $jsonFlags) }}</pre>
                </x-core::tab.pane>

                <x-core::tab.pane id="payload">
                    <pre>{{ json_encode($task->payload, $jsonFlags) }}</pre>
                </x-core::tab.pane>
            </x-core::tab.content>
        </x-core::card.body>
    </x-core::card>
@stop
