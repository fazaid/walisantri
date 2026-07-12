@extends('errors::minimal')

@section('title', __('Locked'))
@section('code', '423')
@section('message', __($exception->getMessage() ?: 'Locked'))
