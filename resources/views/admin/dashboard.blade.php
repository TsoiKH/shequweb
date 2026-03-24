@extends('layouts.admin')

@section('title', '控制台')

@section('content')
    <div style="background: white; padding: 20px; border-radius: 8px;">
        <h2>欢迎回来，{{ Auth::guard('admin')->user()->name }}！</h2>
        <p>这是您的纯手写后台控制台。</p>
    </div>
@endsection