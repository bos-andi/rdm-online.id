@extends('layouts.admin')

@section('title', 'Checkout Pembayaran')

@section('content')
    <x-admin.page-header
        title="Checkout Pembayaran"
        :subtitle="$instance->madrasah_name . ' · ' . $instance->fqdn"
    />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @include('renewal.partials.checkout-form')
        </div>
        <div>
            @include('renewal.partials.admin-instance-payments')
        </div>
    </div>
@endsection

@push('scripts')
    @include('renewal.partials.checkout-scripts')
@endpush
