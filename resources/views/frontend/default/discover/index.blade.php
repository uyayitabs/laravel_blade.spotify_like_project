@extends('index')
@section('content')
    @include('homepage.nav')
    <div id="page-content">
        <div class="page-header no-separator desktop">
            <h1 data-translate-text="DISCOVER">{{ __('web.DISCOVER') }}</h1>
        </div>
        <div id="column1" class="full">
            @include('commons.slideshow', ['slides' => $discover->slides])
            <div class="content home-section">
                <div class="sub-header">
                    <h2 class="section-title">
                        <span data-translate-text="MUSIC_BY_GENRE">Music by genre</span>
                    </h2>
                </div>
                <div id="grid" class="genre">
                    @foreach ($discover->genres as $index => $genre)
                        <div class="module module-cell genre grid-item">
                            <a href="{{$genre->permalink_url}}" title="{!! $genre->name !!}">
                            <div class="img-container" style="background: url({{$genre->artwork_url}})">
                                <div class="module-inner title">
                                    {{-- <a href="{{$genre->permalink_url}}" title="{!! $genre->name !!}">--}}
                                    <span class="title">{!! $genre->name !!}</span>
                                    {{-- </a>--}}
                                </div>
                            </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="content home-section">
                <div class="sub-header">
                    <h2 class="section-title">
                        <span data-translate-text="MUSIC_BY_MOOD">Music by mood</span>
                    </h2>
                </div>
                <div id="grid" class="genre">
                    @foreach ($discover->moods as $index => $mood)
                        <div class="module module-cell genre grid-item">
                            <a href="{{$mood->permalink_url}}" title="{!! $mood->name !!}">
                            <div class="img-container" style="background: url({{$mood->artwork_url}})">
                                <div class="module-inner title">
                                    {{-- <a href="{{$mood->permalink_url}}" title="{!! $mood->name !!}">--}}
                                    <span class="title">{!! $mood->name !!}</span>
                                    {{--</a>--}}
                                </div>
                            </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
            @include('commons.channel', ['channels' => $discover->channels])
        </div>
    </div>
    {!! Advert::get('footer') !!}
@endsection