@extends(BaseHelper::getAdminMasterLayoutTemplate())

@php
    $jsonFlags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    $payload = $task->payload ?? [];
    $result = $payload['result'] ?? [];
@endphp

@section('content')
    <x-core::card>
        <x-core::card.header>
            <x-core::card.title>Lịch sử call API #{{ $task->id }}</x-core::card.title>
        </x-core::card.header>

        <x-core::card.body>
            <x-core::datagrid>
                <x-core::datagrid.item>
                    <x-slot:title>ID</x-slot:title>
                    {{ $task->id }}
                </x-core::datagrid.item>
                <x-core::datagrid.item>
                    <x-slot:title>Task ID</x-slot:title>
                    {{ $task->task_id }}
                </x-core::datagrid.item>
                <x-core::datagrid.item>
                    <x-slot:title>Trạng thái</x-slot:title>
                    {{ $task->status }}
                </x-core::datagrid.item>
                <x-core::datagrid.item>
                    <x-slot:title>URL ảnh</x-slot:title>
                    <a href="{{ $task->url_image }}" target="_blank" rel="noopener noreferrer">{{ $task->url_image }}</a>
                </x-core::datagrid.item>
                <x-core::datagrid.item>
                    <x-slot:title>URL video đầu vào</x-slot:title>
                    <a href="{{ $task->url_video }}" target="_blank" rel="noopener noreferrer">{{ $task->url_video }}</a>
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
                <x-core::tab.item :is-active="true" id="result" label="Kết quả webhook" />
                <x-core::tab.item id="payload" label="Payload" />
            </x-core::tab>

            <x-core::tab.content>
                <x-core::tab.pane id="result" :is-active="true">
                    <pre>{{ json_encode($result, $jsonFlags) }}</pre>
                </x-core::tab.pane>
                <x-core::tab.pane id="payload">
                    <pre>{{ json_encode($payload, $jsonFlags) }}</pre>
                </x-core::tab.pane>
            </x-core::tab.content>
        </x-core::card.body>
    </x-core::card>
@stop
