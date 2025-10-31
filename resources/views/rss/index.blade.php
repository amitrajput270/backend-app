@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $title ?? 'RSS Feed' }}</h1>

    @if(isset($error))
    <div class="alert alert-danger">{{ $error }}</div>
    @else
    @if($permalink ?? false)
    <p><a href="{{ $permalink }}" target="_blank">Visit Source</a></p>
    @endif

    @if(isset($description))
    <p>{{ $description }} - {{$itemCount}}</p>
    @endif

    <div class="feed-items">
        @foreach($items as $index => $item)
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">
                    <a href="{{ $item['link'] }}" target="_blank"> {{$index + 1}}.{{ $item['title'] }}</a>
                </h5>
                @if($item['description'])
                <p class="card-text">{{ Str::limit($item['description'], 200) }}</p>
                @endif
                <small class="text-muted">
                    {{ $item['date'] }}
                    @if($item['author'] !== 'Unknown')
                    by {{ $item['author'] }}
                    @endif
                </small>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection