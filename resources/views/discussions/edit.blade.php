@extends('app')

@section('content')
  <h1>{{trans('messages.modify')}} <strong>"{{$discussion->name}}"</strong></h1>


  {!! Form::model($discussion, array('action' => ['GroupDiscussionController@update', $discussion->group, $discussion], 'files' => true)) !!}

  @include('discussions.form')

  <div class="form-group">
    {!! Form::submit(trans('messages.save'), ['class' => 'btn btn-primary']) !!}
  </div>


  {!! Form::close() !!}

@endsection
