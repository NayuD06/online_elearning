@extends('errors.layout')

@section('code', '403')
@section('title', 'Bạn không có quyền truy cập')
@section('message', $exception->getMessage() && $exception->getMessage() !== 'This action is unauthorized.' ? $exception->getMessage() : 'Trang này chỉ dành cho tài khoản có quyền phù hợp. Nếu bạn nghĩ đây là nhầm lẫn, hãy liên hệ quản trị viên.')
