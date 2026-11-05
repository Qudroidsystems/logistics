@extends('public._layout', ['title' => 'Get a price', 'noindex' => true])
@section('content')
<style>body { background: transparent; }</style>
<div class="p-2">@include('public._quote-form')</div>
@endsection
