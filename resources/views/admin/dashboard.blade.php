@php
    use Illuminate\Support\Facades\Auth;
    $authUser = Auth::user();
@endphp
<x-admin.layout>
    <x-slot name="title">Dashboard</x-slot>
    <x-slot name="heading">Dashboard</x-slot>
    {{-- <x-slot name="subheading">Test</x-slot> --}}

</x-admin.layout>





