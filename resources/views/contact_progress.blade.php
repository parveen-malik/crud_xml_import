@extends('layout')
@section('title','Import Progress')
@section('content')
    <div class="p-4 border bg-white rounded">
        @if($progress['file_url'])
            <p>File: <a href="{{ $progress['file_url'] }}" target="_blank">Download XML</a></p>
        @endif

        <p>Processed: {{ $progress['processed'] ?? 0 }} / {{ $progress['total'] ?? 0 }}</p>
        <p>Inserted: {{ $progress['inserted'] ?? 0 }}</p>
        <p>Updated: {{ $progress['updated'] ?? 0 }}</p>
        <p>Skipped: {{ $progress['skipped'] ?? 0 }}</p>
        <p>Status: {{ $progress['status'] ?? 'Starting...' }}</p>
    </div>
@endsection
