@extends('errors.layout')

@section('code', '419')
@section('title', 'Phiên làm việc đã hết hạn')
@section('message', 'Trang đã mở quá lâu nên phiên làm việc hết hạn. Hãy tải lại trang rồi thao tác lại.')
@section('actions')
    <a href="{{ url()->previous() }}" class="btn-secondary"><x-icon name="chevron-left" class="h-4 w-4" /> Quay lại</a>
@endsection
