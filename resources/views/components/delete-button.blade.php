@props(['action', 'confirm' => 'Bạn chắc chắn muốn xoá mục này?'])

<form method="POST" action="{{ $action }}" onsubmit="return confirm(@js($confirm))" class="inline">
    @csrf
    @method('DELETE')
    <button {{ $attributes->merge(['class' => 'btn-ghost btn-sm text-rose-600 hover:bg-rose-50', 'title' => 'Xoá']) }}>
        <x-icon name="trash" class="h-4 w-4" />
    </button>
</form>
