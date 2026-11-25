@extends('tenant.layout')
@section('title', 'Gym & Fitness Dashboard')
@section('content')
<div class="page-header">
    <h1 class="page-title">Gym & Fitness Studio</h1>
    <p class="text-muted">Manage members, subscriptions, classes, and check-ins.</p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="fs-1 fw-bold text-primary">{{ $members }}</div>
                <div class="text-muted">Total Members</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="fs-1 fw-bold text-success">{{ $activeSubscriptions }}</div>
                <div class="text-muted">Active Subscriptions</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="fs-1 fw-bold text-info">{{ $todayCheckins }}</div>
                <div class="text-muted">Today's Check-ins</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="fs-1 fw-bold text-warning">{{ $upcomingClasses }}</div>
                <div class="text-muted">Upcoming Classes</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Quick Links</h5>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('tenant.gym.members') }}" class="btn btn-outline-primary">Members</a>
            <a href="{{ route('tenant.gym.plans') }}" class="btn btn-outline-primary">Plans</a>
            <a href="{{ route('tenant.gym.subscriptions') }}" class="btn btn-outline-primary">Subscriptions</a>
            <a href="{{ route('tenant.gym.checkin') }}" class="btn btn-outline-primary">Check-in</a>
            <a href="{{ route('tenant.gym.classes') }}" class="btn btn-outline-primary">Classes</a>
            <a href="{{ route('tenant.gym.trainers') }}" class="btn btn-outline-primary">Trainers</a>
        </div>
    </div>
</div>
@endsection
