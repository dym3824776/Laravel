@if($feed_times->count() > 0)
    <ul class="list-unstyled">
        @foreach($feed_times as $status)
            @include('statuses._status',['user' =>$status->user])
        @endforeach
    </ul>
    <div>
        {!! $feed_times->render() !!}
    </div>
    @else
    <p>没有数据</p>
@endif